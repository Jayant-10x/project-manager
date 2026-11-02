<?php

namespace App\Livewire\Projects;

use App\Models\Milestone;
use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Services\AuditNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class ProjectDetail extends Component
{
    public Project $project;

    #[Url(as: 'tab')]
    public string $activeTab = 'overview';

    #[On('payment-saved')]
    #[On('project-saved')]
    #[On('document-saved')]
    public function refreshProjectData(): void
    {
        $this->project->refresh();
        $this->project->load(['payments', 'terms', 'milestones', 'documents']);
    }

    public function mount(Project $project): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        $allowed = false;
        if ($user->role === 'Admin') {
            $allowed = true;
        } elseif ($user->role === 'Manager') {
            $downlineIds = $user->getDownlineUserIds();
            $allowed = ($project->coordinator_id && in_array($project->coordinator_id, $downlineIds, true))
                || ($project->sales_person_id && in_array($project->sales_person_id, $downlineIds, true))
                || ($project->tech_person_id && in_array($project->tech_person_id, $downlineIds, true))
                || ($project->created_by && in_array($project->created_by, $downlineIds, true));
        } elseif ($user->role === 'Sales') {
            $allowed = ($project->sales_person_id === $user->id || $project->created_by === $user->id);
        } elseif ($user->isTechPerson()) {
            $allowed = ($project->tech_person_id === $user->id);
        } else {
            $allowed = ($project->coordinator_id === $user->id);
        }

        if (! $allowed) {
            abort(403, 'Unauthorized access to unassigned project.');
        }

        if ((! $user->hasPermission('schedules', 'view') || $user->role === 'Sales') && $this->activeTab === 'schedule') {
            $this->activeTab = 'overview';
        }

        if ((! $user->hasPermission('payments', 'view') || $user->role === 'Sales') && $this->activeTab === 'payments') {
            $this->activeTab = 'overview';
        }

        $this->project = $project;
    }

    public function updatedActiveTab(string $value): void
    {
        $user = auth()->user();
        if ($user) {
            if ((! $user->hasPermission('schedules', 'view') || $user->role === 'Sales') && $value === 'schedule') {
                $this->activeTab = 'overview';
            }
            if ((! $user->hasPermission('payments', 'view') || $user->role === 'Sales') && $value === 'payments') {
                $this->activeTab = 'overview';
            }
        }
    }

    public function toggleMilestone(int $milestoneId): void
    {
        $milestone = Milestone::where('project_id', $this->project->id)->findOrFail($milestoneId);
        $oldStatus = $milestone->status;
        $milestone->done = ! $milestone->done;
        $milestone->done_on = $milestone->done ? now()->toDateString() : null;
        $milestone->status = $milestone->done ? 'Done' : 'Pending';
        $milestone->save();

        app(AuditNotificationService::class)->log(
            auth()->id(),
            'Toggled Milestone',
            'Milestone',
            $milestone->id,
            $this->project->code,
            "Milestone '{$milestone->name}' status changed: {$oldStatus} -> {$milestone->status}"
        );

        $this->project->load('milestones');

        if ($milestone->done && auth()->user()?->hasPermission('payments', 'add')) {
            $pendingTerm = PaymentTerm::where('project_id', $this->project->id)
                ->where('status', '!=', 'Paid')
                ->orderBy('seq')
                ->first();

            if ($pendingTerm) {
                $balance = max(0, (float) $pendingTerm->amount - (float) $pendingTerm->received - (float) $pendingTerm->tds);
                $this->dispatch('prompt-milestone-payment', [
                    'milestoneName' => $milestone->name,
                    'termLabel' => $pendingTerm->label,
                    'termSeq' => $pendingTerm->seq,
                    'balance' => $balance,
                    'projectId' => $this->project->id,
                    'termId' => $pendingTerm->id,
                ]);
            }
        }
    }

    public function deleteTerm(int $termId): void
    {
        if (! auth()->user()?->hasPermission('schedules', 'delete')) {
            abort(403, 'Unauthorized.');
        }

        $term = PaymentTerm::where('project_id', $this->project->id)->findOrFail($termId);
        if ($term->received > 0) {
            $this->dispatch('toast', ['msg' => 'Payments exist for this installment. Remove payment first.', 'type' => 'err']);

            return;
        }
        $label = $term->label;
        $amt = $term->amount;
        $term->delete();

        app(AuditNotificationService::class)->log(
            auth()->id(),
            'Deleted Installment',
            'PaymentTerm',
            $termId,
            $this->project->code,
            "Deleted installment '{$label}' of ₹".number_format($amt, 2)." from project {$this->project->name}"
        );

        $this->project->load('terms');
        $this->dispatch('project-saved');
    }

    public function deletePayment(int $paymentId): void
    {
        if (! auth()->user()?->hasPermission('payments', 'delete')) {
            abort(403, 'Unauthorized.');
        }

        $pay = Payment::where('project_id', $this->project->id)->findOrFail($paymentId);
        $termId = $pay->term_id;
        $payCode = $pay->code;
        $payAmt = $pay->amount;
        $pay->delete();

        if ($termId) {
            $term = PaymentTerm::find($termId);
            $term?->recalculateStatus();
        }

        app(AuditNotificationService::class)->log(
            auth()->id(),
            'Deleted Payment',
            'Payment',
            $paymentId,
            $payCode ?: '',
            "Deleted payment receipt {$payCode} of ₹".number_format($payAmt, 2)." from project {$this->project->name}"
        );

        $this->project->load(['payments', 'terms']);
        $this->dispatch('payment-deleted');
    }

    public function deleteDocument(int $docId): void
    {
        $doc = $this->project->documents()->findOrFail($docId);
        $docName = $doc->file_name;
        if (Storage::disk('documents')->exists($doc->stored_as)) {
            Storage::disk('documents')->delete($doc->stored_as);
        }
        if (Storage::disk('public')->exists($doc->stored_as)) {
            Storage::disk('public')->delete($doc->stored_as);
        }
        $doc->delete();

        app(AuditNotificationService::class)->log(
            auth()->id(),
            'Deleted Document',
            'Document',
            $docId,
            $this->project->code,
            "Deleted document '{$docName}' from project {$this->project->name}"
        );

        $this->project->load('documents');
    }

    public function deleteProject(): void
    {
        if (auth()->user()->role !== 'Admin') {
            abort(403, 'Only administrators can delete projects.');
        }

        $name = $this->project->name;
        $code = $this->project->code;
        $id = $this->project->id;

        $filesToDelete = $this->project->documents->pluck('stored_as')->filter()->values()->all();
        $auditService = app(AuditNotificationService::class);

        DB::transaction(function () use ($id, $code, $name, $auditService, $filesToDelete) {
            $auditService->notifyProjectStakeholders(
                $this->project,
                'Project Deleted',
                "Project Deleted: {$name}",
                "Project {$name} ({$code}) was deleted by ".auth()->user()->name.'.',
                'project',
                $id
            );

            $this->project->delete();

            $auditService->log(
                auth()->id(),
                'Deleted Project',
                'Project',
                $id,
                $code,
                "Deleted project {$name} and associated records"
            );

            DB::afterCommit(function () use ($filesToDelete) {
                foreach ($filesToDelete as $storedAs) {
                    if (Storage::disk('documents')->exists($storedAs)) {
                        Storage::disk('documents')->delete($storedAs);
                    }
                    if (Storage::disk('public')->exists($storedAs)) {
                        Storage::disk('public')->delete($storedAs);
                    }
                }
            });
        });

        $this->redirect(route('projects.index'), navigate: true);
    }

    public function approveProject(): void
    {
        $user = auth()->user();
        if (! $this->project->canBeApprovedBy($user)) {
            abort(403, 'Unauthorized to approve this project.');
        }

        $this->project->update([
            'approval_status' => 'Approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        $auditService = app(AuditNotificationService::class);

        $auditService->log(
            $user->id,
            'Approved Project',
            'Project',
            $this->project->id,
            $this->project->code,
            "Approved project {$this->project->name}"
        );

        $creatorId = $this->project->created_by ?: $this->project->sales_person_id;
        if ($creatorId && (int) $creatorId !== (int) $user->id) {
            $auditService->notify(
                $creatorId,
                'Project Approved',
                "Project Approved: {$this->project->name}",
                "Your submitted project {$this->project->name} ({$this->project->code}) was approved by {$user->name}.",
                'project',
                $this->project->id
            );
        }

        $this->refreshProjectData();
        $this->dispatch('toast', ['msg' => 'Project approved successfully.', 'type' => 'ok']);
    }

    public function rejectProject(?string $reason = null): void
    {
        $user = auth()->user();
        if (! $this->project->canBeApprovedBy($user)) {
            abort(403, 'Unauthorized to reject this project.');
        }

        $cleanReason = trim((string) $reason);

        $this->project->update([
            'approval_status' => 'Rejected',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'rejection_reason' => $cleanReason !== '' ? $cleanReason : null,
        ]);

        $auditService = app(AuditNotificationService::class);
        $reasonText = $cleanReason !== '' ? " (Reason: {$cleanReason})" : '';

        $auditService->log(
            $user->id,
            'Rejected Project',
            'Project',
            $this->project->id,
            $this->project->code,
            "Rejected project {$this->project->name}{$reasonText}"
        );

        $creatorId = $this->project->created_by ?: $this->project->sales_person_id;
        if ($creatorId && (int) $creatorId !== (int) $user->id) {
            $auditService->notify(
                $creatorId,
                'Project Rejected',
                "Project Rejected: {$this->project->name}",
                "Your submitted project {$this->project->name} ({$this->project->code}) was rejected by {$user->name}.".($cleanReason !== '' ? " Reason: {$cleanReason}" : ''),
                'project',
                $this->project->id
            );
        }

        $this->refreshProjectData();
        $this->dispatch('toast', ['msg' => 'Project rejected.', 'type' => 'warning']);
    }

    public function render()
    {
        $money = $this->project->money;
        $sched = (float) $money['scheduled'];
        $rec = (float) $money['received'];
        $collectionRate = $sched > 0 ? min(100, round(($rec / $sched) * 100)) : 0;

        $today = Carbon::today()->startOfDay();
        $daysLate = 0;
        if ($this->project->delivery_date && $this->project->status !== 'Delivered') {
            $diff = $today->diffInDays(Carbon::parse($this->project->delivery_date)->startOfDay(), false);
            if ($diff < 0) {
                $daysLate = (int) round(abs($diff));
            }
        }

        return view('livewire.projects.project-detail', [
            'terms' => $this->project->terms()->orderBy('seq')->get(),
            'milestones' => $this->project->milestones()->orderBy('sort_order')->get(),
            'payments' => $this->project->payments()->orderByDesc('paid_on')->get(),
            'documents' => $this->project->documents()->orderByDesc('id')->get(),
            'r' => $collectionRate,
            'daysLate' => $daysLate,
        ])->layoutData([
            'header' => $this->project->name,
            'subtitle' => $this->project->code.' · '.($this->project->customer?->display_name ?? '').($this->project->coordinator ? ' · '.$this->project->coordinator->name : ''),
        ]);
    }
}
