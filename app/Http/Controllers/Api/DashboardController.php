<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Followup;
use App\Models\Milestone;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Models\SalesPerson;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use App\Services\MoneyCalculationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller {
    public function __construct(
        protected MoneyCalculationService $moneyService
    ) {
    }

    public function load(Request $request): JsonResponse {
        $user = $request->user();
        $this->moneyService->refreshOverdueStatus();

        $users = User::query()
            ->orderByRaw('FIELD(role, "Admin", "Coordinator")')
            ->orderBy('name')
            ->get();

        $sales = SalesPerson::query()->orderBy('status')->orderBy('name')->get();
        $customers = Customer::query()->orderBy('company')->get();
        $projects = Project::query()->with('customer')
            ->orderByRaw('FIELD(health, "Delayed", "At Risk", "On Track", "Completed")')
            ->orderByRaw('delivery_date IS NULL')
            ->orderBy('delivery_date')
            ->get();

        $terms = PaymentTerm::query()->orderBy('project_id')->orderBy('seq')->get();
        $payments = Payment::query()->with(['customer', 'project'])->orderByDesc('paid_on')->orderByDesc('id')->get();
        $documents = Document::query()->orderByDesc('id')->get();
        $followups = Followup::query()->with('customer')->orderByDesc('happened_on')->limit(200)->get();
        $milestones = Milestone::query()->orderBy('project_id')->orderBy('sort_order')->orderBy('id')->get();
        $tasks = Task::query()->with(['customer', 'project'])->orderBy('done')->orderByRaw('due_date IS NULL')->orderBy('due_date')->get();

        $notifications = Notification::query()
            ->where('user_email', $user->email)
            ->orderByDesc('id')
            ->limit(60)
            ->get();

        $unreadCount = $notifications->where('is_read', false)->count();

        $auditLogs = AuditLog::query()->orderByDesc('id')->limit(120)->get();

        // Project money maps
        $byProject = [];
        foreach ($terms as $t) {
            $pid = (int)$t->project_id;
            if (!isset($byProject[$pid])) {
                $byProject[$pid] = ['scheduled' => 0.0, 'received' => 0.0, 'tds' => 0.0, 'overdue' => 0.0, 'next_due' => null];
            }
            if ($t->status !== 'Cancelled') $byProject[$pid]['scheduled'] += (float)$t->amount;
            if ($t->status === 'Overdue') $byProject[$pid]['overdue'] += (float)$t->amount;
            if (!$byProject[$pid]['next_due'] && in_array($t->status, ['Pending', 'Partial', 'Overdue'], true) && $t->due_date) {
                $byProject[$pid]['next_due'] = $t->due_date->toDateString();
            }
        }

        foreach ($payments as $p) {
            $pid = (int)$p->project_id;
            if (!isset($byProject[$pid])) {
                $byProject[$pid] = ['scheduled' => 0.0, 'received' => 0.0, 'tds' => 0.0, 'overdue' => 0.0, 'next_due' => null];
            }
            $byProject[$pid]['received'] += (float)$p->amount;
            $byProject[$pid]['tds'] += (float)$p->tds;
        }

        $normProjects = $projects->map(function ($p) use ($byProject) {
            $pid = (int)$p->id;
            $m = $byProject[$pid] ?? ['scheduled' => 0.0, 'received' => 0.0, 'tds' => 0.0, 'overdue' => 0.0, 'next_due' => null];
            $m['due'] = round(max(0, $m['scheduled'] - $m['received'] - $m['tds']), 2);

            return [
                'id' => (int)$p->id,
                'code' => $p->code,
                'customerId' => (int)$p->customer_id,
                'customer' => $p->customer?->company ?? '',
                'customerCode' => $p->customer?->code ?? '',
                'name' => $p->name,
                'description' => $p->description ?? '',
                'type' => $p->project_type ?? '',
                'coordinator' => $p->coordinator ?? '',
                'value' => (float)$p->value,
                'cost' => (float)$p->cost,
                'startDate' => $p->start_date ? $p->start_date->toDateString() : '',
                'deliveryDate' => $p->delivery_date ? $p->delivery_date->toDateString() : '',
                'actualEnd' => $p->actual_end ? $p->actual_end->toDateString() : '',
                'status' => $p->status,
                'health' => $p->health,
                'delayReason' => $p->delay_reason ?? '',
                'amcRequired' => (int)$p->amc_required,
                'amcStart' => $p->amc_start ? $p->amc_start->toDateString() : '',
                'amcEnd' => $p->amc_end ? $p->amc_end->toDateString() : '',
                'amcAmount' => (float)$p->amc_amount,
                'renewAlertDays' => (int)$p->renew_alert_days,
                'handoverDone' => (int)$p->handover_done,
                'handoverNotes' => $p->handover_notes ?? '',
                'notes' => $p->notes ?? '',
                'money' => $m,
            ];
        });

        return response()->json([
            'ok' => true,
            'data' => [
                'users' => $users->map(fn($u) => [
                    'id' => (int)$u->id, 'name' => $u->name, 'email' => $u->email, 'phone' => $u->phone,
                    'role' => $u->role, 'designation' => $u->designation, 'city' => $u->city,
                    'status' => $u->status, 'online' => (int)$u->online,
                    'lastSeen' => $u->last_seen ? $u->last_seen->format('Y-m-d H:i') : '',
                    'joined' => $u->joined ? $u->joined->toDateString() : ''
                ]),
                'sales' => $sales->map(fn($s) => [
                    'id' => (int)$s->id, 'name' => $s->name, 'phone' => $s->phone, 'email' => $s->email,
                    'target' => (float)$s->target, 'status' => $s->status
                ]),
                'customers' => $customers->map(fn($c) => [
                    'id' => (int)$c->id, 'code' => $c->code, 'company' => $c->company,
                    'contactName' => $c->contact_name ?? '', 'email' => $c->email ?? '',
                    'phone' => $c->phone ?? '', 'altPhone' => $c->alt_phone ?? '',
                    'city' => $c->city ?? '', 'state' => $c->state ?? '', 'address' => $c->address ?? '',
                    'gstin' => $c->gstin ?? '', 'pan' => $c->pan ?? '',
                    'billingName' => $c->billing_name ?? '', 'billingAddr' => $c->billing_addr ?? '',
                    'source' => $c->source ?? '', 'industry' => $c->industry ?? '',
                    'onboardedBy' => $c->onboarded_by ?? '', 'status' => $c->status,
                    'internalNotes' => $c->internal_notes ?? '', 'createdBy' => $c->created_by ?? '',
                    'createdAt' => $c->created_at ? $c->created_at->format('Y-m-d H:i') : '',
                ]),
                'projects' => $normProjects,
                'terms' => $terms->map(fn($t) => [
                    'id' => (int)$t->id, 'projectId' => (int)$t->project_id, 'seq' => (int)$t->seq,
                    'label' => $t->label, 'amount' => (float)$t->amount, 'percent' => (float)$t->percent,
                    'trigger' => $t->trigger_type, 'milestoneId' => $t->milestone_id ? (int)$t->milestone_id : null,
                    'dueDate' => $t->due_date ? $t->due_date->toDateString() : '', 'graceDays' => (int)$t->grace_days,
                    'status' => $t->status, 'received' => (float)$t->received, 'tds' => (float)$t->tds,
                    'balance' => round(max(0, $t->amount - $t->received - $t->tds), 2), 'notes' => $t->notes ?? '',
                ]),
                'payments' => $payments->map(fn($p) => [
                    'id' => (int)$p->id, 'code' => $p->code, 'projectId' => (int)$p->project_id,
                    'customerId' => (int)$p->customer_id, 'termId' => $p->term_id ? (int)$p->term_id : null,
                    'customer' => $p->customer?->company ?? '', 'projectCode' => $p->project?->code ?? '',
                    'projectName' => $p->project?->name ?? '', 'amount' => (float)$p->amount, 'tds' => (float)$p->tds,
                    'paidOn' => $p->paid_on->toDateString(), 'mode' => $p->mode, 'reference' => $p->reference ?? '',
                    'bank' => $p->bank ?? '', 'note' => $p->note ?? '', 'enteredBy' => $p->entered_by ?? ''
                ]),
                'documents' => $documents->map(fn($d) => [
                    'id' => (int)$d->id, 'customerId' => $d->customer_id ? (int)$d->customer_id : null,
                    'projectId' => $d->project_id ? (int)$d->project_id : null,
                    'category' => $d->category, 'title' => $d->title, 'name' => $d->file_name,
                    'mime' => $d->mime, 'sizeKb' => (int)$d->size_kb, 'by' => $d->uploaded_by,
                    'at' => $d->created_at ? $d->created_at->format('Y-m-d H:i') : '',
                    'url' => asset('storage/' . rawurlencode($d->stored_as)),
                    'isImage' => str_starts_with((string)$d->mime, 'image/'),
                    'isPdf' => str_contains((string)$d->mime, 'pdf'),
                ]),
                'followups' => $followups->map(fn($f) => [
                    'id' => (int)$f->id, 'customerId' => (int)$f->customer_id,
                    'projectId' => $f->project_id ? (int)$f->project_id : null,
                    'customer' => $f->customer?->company ?? '', 'kind' => $f->kind,
                    'summary' => $f->summary, 'detail' => $f->detail ?? '',
                    'happenedOn' => $f->happened_on->format('Y-m-d H:i'),
                    'nextDate' => $f->next_date ? $f->next_date->toDateString() : '',
                    'nextNote' => $f->next_note ?? '', 'by' => $f->created_by ?? ''
                ]),
                'milestones' => $milestones->map(fn($m) => [
                    'id' => (int)$m->id, 'projectId' => (int)$m->project_id, 'name' => $m->name,
                    'dueDate' => $m->due_date ? $m->due_date->toDateString() : '',
                    'done' => (int)$m->done, 'doneOn' => $m->done_on ? $m->done_on->toDateString() : '',
                    'status' => $m->status, 'sortOrder' => (int)$m->sort_order, 'notes' => $m->notes ?? ''
                ]),
                'tasks' => $tasks->map(fn($t) => [
                    'id' => (int)$t->id, 'projectId' => $t->project_id ? (int)$t->project_id : null,
                    'customerId' => $t->customer_id ? (int)$t->customer_id : null,
                    'customer' => $t->customer?->company ?? '', 'projectCode' => $t->project?->code ?? '',
                    'title' => $t->title, 'dueDate' => $t->due_date ? $t->due_date->toDateString() : '',
                    'done' => (int)$t->done, 'priority' => $t->priority, 'assignedTo' => $t->assigned_to ?? ''
                ]),
                'notifications' => $notifications->map(fn($n) => [
                    'id' => (int)$n->id, 'kind' => $n->kind, 'title' => $n->title, 'body' => $n->body,
                    'linkType' => $n->link_type ?? '', 'linkId' => $n->link_id ? (int)$n->link_id : null,
                    'read' => (int)$n->is_read, 'at' => $n->created_at->format('Y-m-d H:i')
                ]),
                'audit' => $auditLogs->map(fn($a) => [
                    'who' => $a->who, 'action' => $a->action, 'entity' => $a->entity,
                    'entity_code' => $a->entity_code, 'detail' => $a->detail,
                    'at' => $a->at_time ? $a->at_time->format('Y-m-d H:i') : ''
                ]),
                'unread' => $unreadCount,
                'me' => [
                    'id' => (int)$user->id, 'name' => $user->name, 'email' => $user->email,
                    'role' => $user->role, 'designation' => $user->designation, 'mustChange' => (int)$user->must_change
                ],
                'settings' => [
                    'paymentAlertDays' => (int)Setting::getValue('payment_alert_days', config('crm.payment_alert_days')),
                    'amcAlertDays' => (int)Setting::getValue('amc_alert_days', config('crm.amc_alert_days')),
                    'followupAlertDays' => (int)Setting::getValue('followup_alert_days', config('crm.followup_alert_days')),
                    'companyName' => Setting::getValue('company_name', '10X Agency'),
                ],
                'meta' => [
                    'serverTime' => Carbon::now()->toIso8601String(),
                    'today' => Carbon::today()->toDateString()
                ]
            ]
        ]);
    }

    public function dashboard(Request $request): JsonResponse {
        $this->moneyService->refreshOverdueStatus();
        $today = Carbon::today()->toDateString();
        $alertDays = (int)Setting::getValue('payment_alert_days', config('crm.payment_alert_days'));
        $soonDate = Carbon::today()->addDays($alertDays)->toDateString();

        $sched = (float)PaymentTerm::query()->where('status', '<>', 'Cancelled')->sum('amount');
        $rec = (float)Payment::query()->sum('amount');
        $tds = (float)Payment::query()->sum('tds');
        $over = (float)PaymentTerm::query()->where('status', 'Overdue')->selectRaw('SUM(amount - received - tds) as val')->value('val');
        $month = (float)Payment::query()->where('paid_on', '>=', Carbon::today()->startOfMonth()->toDateString())->sum('amount');

        $pHealth = ['On Track' => 0, 'At Risk' => 0, 'Delayed' => 0, 'Completed' => 0];
        $healthCounts = Project::query()->select('health', DB::raw('COUNT(*) as count'))->groupBy('health')->get();
        foreach ($healthCounts as $hc) {
            if (isset($pHealth[$hc->health])) {
                $pHealth[$hc->health] = (int)$hc->count;
            }
        }

        $upcoming = PaymentTerm::query()->with(['project.customer'])
            ->whereIn('status', ['Pending', 'Partial'])
            ->whereNotNull('due_date')
            ->where('due_date', '<=', $soonDate)
            ->orderBy('due_date')
            ->limit(40)
            ->get();

        $overdue = PaymentTerm::query()->with(['project.customer'])
            ->where('status', 'Overdue')
            ->orderBy('due_date')
            ->limit(40)
            ->get();

        $followupAlertDays = (int)Setting::getValue('followup_alert_days', config('crm.followup_alert_days'));
        $fupDue = Followup::query()->with('customer')
            ->whereNotNull('next_date')
            ->where('next_date', '<=', Carbon::today()->addDays($followupAlertDays)->toDateString())
            ->orderBy('next_date')
            ->limit(40)
            ->get();

        $amc = Project::query()->with('customer')
            ->where('amc_required', true)
            ->whereNotNull('amc_end')
            ->whereRaw('amc_end <= DATE_ADD(CURDATE(), INTERVAL renew_alert_days DAY)')
            ->orderBy('amc_end')
            ->limit(20)
            ->get();

        $late = Project::query()->with('customer')
            ->whereIn('health', ['Delayed', 'At Risk'])
            ->orderBy('delivery_date')
            ->limit(20)
            ->get();

        $handover = Project::query()->with('customer')
            ->where('status', 'Delivered')
            ->where('handover_done', false)
            ->limit(20)
            ->get();

        $sales = DB::table('customers as c')
            ->leftJoin('projects as p', 'p.customer_id', '=', 'c.id')
            ->where('c.onboarded_by', '<>', '')
            ->selectRaw('c.onboarded_by as name, COUNT(DISTINCT c.id) as customers, COUNT(DISTINCT p.id) as projects, COALESCE(SUM(p.value), 0) as value')
            ->groupBy('c.onboarded_by')
            ->orderByDesc('value')
            ->get();

        $collections = [];
        $collRows = DB::table('payments as pay')
            ->join('customers as c', 'c.id', '=', 'pay.customer_id')
            ->where('c.onboarded_by', '<>', '')
            ->selectRaw('c.onboarded_by as sp, COALESCE(SUM(pay.amount), 0) as received')
            ->groupBy('c.onboarded_by')
            ->get();
        foreach ($collRows as $cr) {
            $collections[$cr->sp] = (float)$cr->received;
        }

        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $carbonMonth = Carbon::now()->subMonths($i);
            $from = $carbonMonth->copy()->startOfMonth()->toDateString();
            $to = $carbonMonth->copy()->endOfMonth()->toDateString();
            $amt = (float)Payment::query()->whereBetween('paid_on', [$from, $to])->sum('amount');
            $trend[] = ['label' => $carbonMonth->format('M'), 'value' => $amt];
        }

        $age = ['0-30' => 0.0, '31-60' => 0.0, '61-90' => 0.0, '90+' => 0.0];
        $overdueTerms = PaymentTerm::query()->where('status', 'Overdue')->get();
        foreach ($overdueTerms as $ot) {
            $bal = max(0, $ot->amount - $ot->received - $ot->tds);
            $days = $ot->due_date ? Carbon::parse($ot->due_date)->diffInDays(Carbon::today()) : 0;
            if ($days <= 30) $age['0-30'] += $bal;
            elseif ($days <= 60) $age['31-60'] += $bal;
            elseif ($days <= 90) $age['61-90'] += $bal;
            else $age['90+'] += $bal;
        }

        $fmtTerm = function ($t) use ($today) {
            return [
                'id' => (int)$t->id,
                'label' => $t->label,
                'amount' => (float)$t->amount,
                'dueDate' => $t->due_date ? $t->due_date->toDateString() : '',
                'status' => $t->status,
                'balance' => round(max(0, (float)$t->amount - (float)$t->received - (float)$t->tds), 2),
                'projectCode' => $t->project?->code ?? '',
                'projectName' => $t->project?->name ?? '',
                'company' => $t->project?->customer?->company ?? '',
                'customerId' => (int)$t->project?->customer_id,
                'projectId' => (int)$t->project_id,
                'days' => $t->due_date ? Carbon::today()->diffInDays(Carbon::parse($t->due_date), false) : null,
            ];
        };

        return response()->json([
            'ok' => true,
            'data' => [
                'money' => [
                    'scheduled' => round($sched, 2),
                    'received' => round($rec, 2),
                    'tds' => round($tds, 2),
                    'due' => round(max(0, $sched - $rec - $tds), 2),
                    'overdue' => round($over ?? 0, 2),
                    'thisMonth' => round($month, 2),
                    'collectionRate' => $sched > 0 ? round($rec / $sched * 100, 1) : 0,
                ],
                'projects' => $pHealth,
                'counts' => [
                    'customers' => Customer::query()->count(),
                    'projects' => Project::query()->count(),
                    'openTasks' => Task::query()->where('done', false)->count(),
                    'docs' => Document::query()->count(),
                ],
                'upcoming' => $upcoming->map($fmtTerm),
                'overdue' => $overdue->map($fmtTerm),
                'followups' => $fupDue->map(fn($f) => [
                    'id' => (int)$f->id,
                    'company' => $f->customer?->company ?? '',
                    'kind' => $f->kind,
                    'nextDate' => $f->next_date ? $f->next_date->toDateString() : '',
                    'nextNote' => $f->next_note ?: $f->summary,
                    'customerId' => (int)$f->customer_id,
                ]),
                'amc' => $amc->map(fn($p) => [
                    'id' => (int)$p->id,
                    'code' => $p->code,
                    'name' => $p->name,
                    'company' => $p->customer?->company ?? '',
                    'amcEnd' => $p->amc_end ? $p->amc_end->toDateString() : '',
                    'amcAmount' => (float)$p->amc_amount,
                    'days' => $p->amc_end ? Carbon::today()->diffInDays(Carbon::parse($p->amc_end), false) : 0,
                ]),
                'late' => $late->map(fn($p) => [
                    'id' => (int)$p->id,
                    'code' => $p->code,
                    'name' => $p->name,
                    'company' => $p->customer?->company ?? '',
                    'health' => $p->health,
                    'deliveryDate' => $p->delivery_date ? $p->delivery_date->toDateString() : '',
                    'delayReason' => $p->delay_reason,
                    'days' => $p->delivery_date ? Carbon::parse($p->delivery_date)->diffInDays(Carbon::today(), false) : null,
                ]),
                'handover' => $handover->map(fn($p) => [
                    'id' => (int)$p->id,
                    'code' => $p->code,
                    'name' => $p->name,
                    'company' => $p->customer?->company ?? '',
                ]),
                'sales' => $sales->map(fn($s) => [
                    'name' => $s->name,
                    'customers' => (int)$s->customers,
                    'projects' => (int)$s->projects,
                    'value' => (float)$s->value,
                    'received' => $collections[$s->name] ?? 0.0,
                ]),
                'trend' => $trend,
                'ageing' => array_map(fn($k, $v) => ['label' => $k, 'value' => round($v, 2)], array_keys($age), $age),
                'today' => $today,
            ]
        ]);
    }

    public function search(Request $request): JsonResponse {
        $query = trim((string)$request->input('q', ''));
        if (mb_strlen($query) < 2) {
            return response()->json(['ok' => true, 'results' => []]);
        }

        $like = '%' . $query . '%';
        $results = [];

        $customers = Customer::query()
            ->where(fn($q) => $q->where('company', 'LIKE', $like)
                ->orWhere('contact_name', 'LIKE', $like)
                ->orWhere('code', 'LIKE', $like)
                ->orWhere('phone', 'LIKE', $like)
                ->orWhere('city', 'LIKE', $like))
            ->orderBy('company')
            ->limit(10)
            ->get();

        foreach ($customers as $c) {
            $sub = $c->code . ($c->contact_name ? ' · ' . $c->contact_name : '') . ($c->city ? ' · ' . $c->city : '');
            $results[] = [
                'type' => 'customer',
                'id' => (int)$c->id,
                'title' => $c->company,
                'sub' => $sub,
            ];
        }

        $projects = Project::query()->with('customer')
            ->where(fn($q) => $q->where('name', 'LIKE', $like)
                ->orWhere('code', 'LIKE', $like)
                ->orWhere('project_type', 'LIKE', $like)
                ->orWhereHas('customer', fn($cq) => $cq->where('company', 'LIKE', $like)))
            ->orderBy('code')
            ->limit(10)
            ->get();

        foreach ($projects as $p) {
            $sub = $p->code . ' · ' . ($p->customer?->company ?? '') . ' · ' . $p->status;
            $results[] = [
                'type' => 'project',
                'id' => (int)$p->id,
                'title' => $p->name,
                'sub' => $sub,
                'badge' => $p->health,
            ];
        }

        $sales = SalesPerson::query()->where('name', 'LIKE', $like)->limit(5)->get();
        foreach ($sales as $s) {
            $results[] = [
                'type' => 'sales',
                'id' => (int)$s->id,
                'title' => $s->name,
                'sub' => 'Sales person',
            ];
        }

        return response()->json(['ok' => true, 'results' => $results]);
    }
}
