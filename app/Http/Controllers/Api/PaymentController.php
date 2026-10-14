<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Milestone;
use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Services\AuditNotificationService;
use App\Services\CodeGeneratorService;
use App\Services\MoneyCalculationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller {
    public function __construct(
        protected CodeGeneratorService     $codeService,
        protected MoneyCalculationService  $moneyService,
        protected AuditNotificationService $auditService
    ) {
    }

    public function savePayment(Request $request): JsonResponse {
        $user = $request->user();
        if (!$user->isCoordinator()) {
            return response()->json(['ok' => false, 'error' => 'You do not have permission to do this.']);
        }

        $input = $request->input('data', []);
        $projectId = (int)($input['projectId'] ?? 0);
        $amount = (float)($input['amount'] ?? 0.0);
        $paidOn = !empty($input['paidOn']) ? $input['paidOn'] : Carbon::today()->toDateString();

        if ($projectId <= 0 || $amount <= 0) {
            return response()->json(['ok' => false, 'error' => 'Enter a valid project and payment amount.']);
        }

        $project = Project::query()->find($projectId);
        if (!$project) {
            return response()->json(['ok' => false, 'error' => 'Project not found.']);
        }

        $termId = (int)($input['termId'] ?? 0);
        if ($termId > 0) {
            $validTerm = PaymentTerm::query()->where('id', $termId)->where('project_id', $projectId)->exists();
            if (!$validTerm) {
                return response()->json(['ok' => false, 'error' => 'This installment does not belong to the selected project.']);
            }
        }

        $code = $this->codeService->generate('payment', 'RCP-');
        $mode = in_array($input['mode'] ?? '', ['Cash', 'UPI', 'NEFT', 'RTGS', 'Cheque', 'Card', 'Other'], true) ? $input['mode'] : 'NEFT';

        $payment = Payment::query()->create([
            'code' => $code,
            'project_id' => $projectId,
            'customer_id' => $project->customer_id,
            'term_id' => $termId > 0 ? $termId : null,
            'amount' => $amount,
            'tds' => (float)($input['tds'] ?? 0.0),
            'paid_on' => $paidOn,
            'mode' => $mode,
            'reference' => $input['reference'] ?? null,
            'bank' => $input['bank'] ?? null,
            'note' => $input['note'] ?? null,
            'entered_by' => $user->name,
        ]);

        if ($termId > 0) {
            $this->moneyService->recomputeTerm($termId);
        }

        $moneySummary = $this->moneyService->calculateProjectMoney($projectId);
        $this->auditService->log(
            $user->name, 'payment', 'payment', $payment->id, $code,
            '₹' . number_format($amount) . ' received via ' . $mode .
            (!empty($input['reference']) ? ' (' . substr($input['reference'], 0, 40) . ')' : '') .
            ' — balance ₹' . number_format($moneySummary['due'])
        );

        $this->auditService->notifyCoordinators(
            'payment', 'Payment received',
            '₹' . number_format($amount) . ' — ' . $project->name . ' (balance ₹' . number_format($moneySummary['due']) . ')',
            'project', $projectId
        );

        return response()->json([
            'ok' => true,
            'id' => $payment->id,
            'code' => $code,
            'money' => $moneySummary,
        ]);
    }

    public function deletePayment(Request $request): JsonResponse {
        $user = $request->user();
        if (!$user->isCoordinator()) {
            return response()->json(['ok' => false, 'error' => 'You do not have permission to do this.']);
        }

        $id = (int)$request->input('id', 0);
        $payment = Payment::query()->find($id);
        if (!$payment) {
            return response()->json(['ok' => false, 'error' => 'Payment not found.']);
        }

        if (!$user->isAdmin() && $payment->entered_by !== $user->name) {
            return response()->json(['ok' => false, 'error' => 'This entry was made by someone else. Only an Admin can remove it.']);
        }

        $termId = $payment->term_id;
        $amount = (float)$payment->amount;
        $code = $payment->code;

        $payment->delete();

        if ($termId) {
            $this->moneyService->recomputeTerm($termId);
        }

        $this->auditService->log($user->name, 'delete', 'payment', $id, $code, 'Payment entry removed — ₹' . number_format($amount));

        return response()->json(['ok' => true]);
    }

    public function saveTerms(Request $request): JsonResponse {
        $user = $request->user();
        if (!$user->isCoordinator()) {
            return response()->json(['ok' => false, 'error' => 'You do not have permission to do this.']);
        }

        $projectId = (int)$request->input('projectId', 0);
        $list = $request->input('terms', []);
        $project = Project::query()->find($projectId);

        if (!$project || !is_array($list)) {
            return response()->json(['ok' => false, 'error' => 'Project and schedule are required.']);
        }

        DB::transaction(function () use ($projectId, $list, $project, $user, &$createdCount) {
            $keptTerms = PaymentTerm::query()
                ->where('project_id', $projectId)
                ->where(function ($q) {
                    $q->whereIn('status', ['Paid', 'Partial', 'Cancelled'])
                        ->orWhere('received', '>', 0)
                        ->orWhereHas('payments');
                })
                ->get();

            $keepIds = $keptTerms->pluck('id')->all();
            $maxKeptSeq = $keptTerms->max('seq') ?? 0;

            if (!empty($keepIds)) {
                PaymentTerm::query()->where('project_id', $projectId)->whereNotIn('id', $keepIds)->delete();
            } else {
                PaymentTerm::query()->where('project_id', $projectId)->delete();
            }

            $validMilestones = Milestone::query()->where('project_id', $projectId)->pluck('id', 'id')->all();
            $totalVal = (float)$project->value;
            $seq = $maxKeptSeq;
            $createdCount = 0;

            foreach ($list as $termData) {
                if (!is_array($termData)) continue;
                if (!empty($termData['id']) && in_array((int)$termData['id'], $keepIds, true)) continue;

                $amt = (float)($termData['amount'] ?? 0);
                if ($amt <= 0) continue;

                $seq++;
                $createdCount++;
                $trigger = in_array($termData['trigger'] ?? '', ['fixed', 'milestone', 'recurring'], true) ? $termData['trigger'] : 'fixed';
                $msId = (int)($termData['milestoneId'] ?? 0);
                if (!isset($validMilestones[$msId])) $msId = null;

                $dueDate = !empty($termData['dueDate']) ? $termData['dueDate'] : null;
                $graceDays = max(0, (int)($termData['graceDays'] ?? 0));
                $pct = $totalVal > 0 ? round($amt / $totalVal * 100, 2) : 0;
                $label = !empty($termData['label']) ? trim($termData['label']) : "Installment {$seq}";

                $isOverdue = $dueDate && Carbon::parse($dueDate)->addDays($graceDays)->isPast();

                PaymentTerm::query()->create([
                    'project_id' => $projectId,
                    'seq' => $seq,
                    'label' => $label,
                    'amount' => $amt,
                    'percent' => $pct,
                    'trigger_type' => $trigger,
                    'milestone_id' => $msId,
                    'due_date' => $dueDate,
                    'grace_days' => $graceDays,
                    'status' => $isOverdue ? 'Overdue' : 'Pending',
                    'notes' => $termData['notes'] ?? null,
                ]);
            }

            $this->auditService->log($user->name, 'update', 'terms', $projectId, $project->project_code, "Payment schedule updated ({$createdCount} editable installments)");
        });

        return response()->json(['ok' => true, 'count' => $createdCount]);
    }

    public function generateRecurring(Request $request): JsonResponse {
        $user = $request->user();
        if (!$user->isCoordinator()) {
            return response()->json(['ok' => false, 'error' => 'You do not have permission to do this.']);
        }

        $projectId = (int)$request->input('projectId', 0);
        $amount = (float)$request->input('amount', 0);
        $months = max(1, min(60, (int)$request->input('months', 6)));
        $startDate = !empty($request->input('startDate')) ? $request->input('startDate') : Carbon::today()->toDateString();
        $label = trim((string)$request->input('label', 'Monthly retainer'));

        $project = Project::query()->find($projectId);
        if (!$project || $amount <= 0) {
            return response()->json(['ok' => false, 'error' => 'Valid project and amount are required.']);
        }

        $maxSeq = (int)PaymentTerm::query()->where('project_id', $projectId)->max('seq');
        $totalVal = (float)$project->value;
        $created = 0;

        for ($i = 0; $i < $months; $i++) {
            $maxSeq++;
            $base = Carbon::parse($startDate)->startOfMonth()->addMonths($i);
            $targetDay = (int)substr($startDate, 8, 2);
            $day = min($targetDay, $base->daysInMonth);
            $calculatedDate = $base->copy()->day($day)->toDateString();
            $installmentLabel = "{$label} — " . Carbon::parse($calculatedDate)->format('M Y');

            PaymentTerm::query()->create([
                'project_id' => $projectId,
                'seq' => $maxSeq,
                'label' => $installmentLabel,
                'amount' => $amount,
                'percent' => $totalVal > 0 ? round($amount / $totalVal * 100, 2) : 0,
                'trigger_type' => 'recurring',
                'milestone_id' => null,
                'due_date' => $calculatedDate,
                'grace_days' => 0,
                'status' => Carbon::parse($calculatedDate)->isPast() ? 'Overdue' : 'Pending',
                'notes' => 'Auto-generated',
            ]);
            $created++;
        }

        $this->auditService->log($user->name, 'update', 'terms', $projectId, $project->project_code, "{$created} recurring installments generated");

        return response()->json(['ok' => true, 'count' => $created]);
    }

    public function deleteTerm(Request $request): JsonResponse {
        $user = $request->user();
        if (!$user->isCoordinator()) {
            return response()->json(['ok' => false, 'error' => 'You do not have permission to do this.']);
        }

        $id = (int)$request->input('id', 0);
        $term = PaymentTerm::query()->with('project')->find($id);
        if (!$term) {
            return response()->json(['ok' => false, 'error' => 'Installment not found.']);
        }

        if ((float)$term->received > 0) {
            return response()->json(['ok' => false, 'error' => 'A payment has already been recorded against this installment. Remove that payment first.']);
        }

        $label = $term->label;
        $code = $term->project?->project_code ?? '';

        $term->delete();
        $this->auditService->log($user->name, 'delete', 'term', $id, $code, 'Installment removed — ' . $label);

        return response()->json(['ok' => true]);
    }
}
