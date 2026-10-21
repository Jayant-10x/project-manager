<?php

namespace App\Livewire\Projects;

use App\Models\Milestone;
use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Services\AuditNotificationService;
use Carbon\Carbon;
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
                || ($project->sales_person_id && in_array($project->sales_person_id, $downlineIds, true));
        } elseif ($user->role === 'Sales') {
            $allowed = ($project->sales_person_id === $user->id);
        } else {
            $allowed = ($project->coordinator_id === $user->id);
        }

        if (! $allowed) {
            abort(403, 'Unauthorized access to unassigned project.');
        }

        if ($user->role === 'Sales' && in_array($this->activeTab, ['schedule', 'payments'])) {
            $this->activeTab = 'overview';
        }

        $this->project = $project;
    }

    public function updatedActiveTab(string $value): void
    {
        if (auth()->user()?->role === 'Sales' && in_array($value, ['schedule', 'payments'])) {
            $this->activeTab = 'overview';
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
    }

    public function deleteTerm(int $termId): void
    {
        if (auth()->user()?->role === 'Sales') {
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
        if (auth()->user()?->role === 'Sales') {
            abort(403, 'Unauthorized.');
        }

        $pay = Payment::where('project_id', $this->project->id)->findOrFail($paymentId);
        $termId = $pay->term_id;
        $payCode = $pay->code;
        $payAmt = $pay->amount;
        $pay->delete();

        if ($termId) {
            $term = PaymentTerm::find($termId);
            if ($term) {
                $remPaid = (float) Payment::where('term_id', $termId)->sum('amount');
                $remTds = (float) Payment::where('term_id', $termId)->sum('tds');
                $term->received = $remPaid;
                $term->tds = $remTds;
                $term->status = ($remPaid + $remTds >= $term->amount - 0.01) ? 'Paid' : ($remPaid > 0 ? 'Partial' : 'Pending');
                $term->save();
            }
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
        if (Storage::disk('uploads')->exists($doc->stored_as)) {
            Storage::disk('uploads')->delete($doc->stored_as);
        }
        Storage::disk('public')->delete($doc->stored_as);
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

        foreach ($this->project->documents as $doc) {
            Storage::disk('public')->delete($doc->stored_as);
        }

        $name = $this->project->name;
        $code = $this->project->code;
        $id = $this->project->id;

        $this->project->delete();

        app(AuditNotificationService::class)->log(
            auth()->id(),
            'Deleted Project',
            'Project',
            $id,
            $code,
            "Deleted project {$name} and associated records"
        );

        $this->redirect(route('projects.index'), navigate: true);
    }

    public function render()
    {
        $money = $this->project->money;
        $sched = (float) $money['scheduled'];
        $rec = (float) $money['received'];
        $collectionRate = $sched > 0 ? min(100, round(($rec / $sched) * 100)) : 0;

        $today = Carbon::today()->toDateString();
        $daysLate = 0;
        if ($this->project->delivery_date && $this->project->status !== 'Delivered') {
            $diff = Carbon::parse($today)->diffInDays(Carbon::parse($this->project->delivery_date), false);
            if ($diff < 0) {
                $daysLate = abs($diff);
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
