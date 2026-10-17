<?php

namespace App\Livewire\Projects;

use App\Models\Milestone;
use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class ProjectDetail extends Component
{
    public Project $project;

    public string $activeTab = 'overview';

    #[On('payment-saved')]
    public function refreshPayments(): void
    {
        $this->project->load(['payments', 'terms']);
    }

    public function mount(Project $project): void
    {
        $user = auth()->user();
        if ($user->role !== 'Admin' && $project->coordinator_id !== $user->id) {
            abort(403, 'Unauthorized access to unassigned project.');
        }

        $this->project = $project;
    }

    public function toggleMilestone(int $milestoneId): void
    {
        $milestone = Milestone::where('project_id', $this->project->id)->findOrFail($milestoneId);
        $milestone->done = ! $milestone->done;
        $milestone->done_on = $milestone->done ? now()->toDateString() : null;
        $milestone->status = $milestone->done ? 'Done' : 'Pending';
        $milestone->save();

        $this->project->load('milestones');
    }

    public function deleteTerm(int $termId): void
    {
        $term = PaymentTerm::where('project_id', $this->project->id)->findOrFail($termId);
        if ($term->received > 0) {
            $this->dispatch('toast', ['msg' => 'Payments exist for this installment. Remove payment first.', 'type' => 'err']);

            return;
        }
        $term->delete();
        $this->project->load('terms');
        $this->dispatch('project-saved');
    }

    public function deletePayment(int $paymentId): void
    {
        $pay = Payment::where('project_id', $this->project->id)->findOrFail($paymentId);
        $termId = $pay->term_id;
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

        $this->project->load(['payments', 'terms']);
        $this->dispatch('payment-deleted');
    }

    public function deleteDocument(int $docId): void
    {
        $doc = $this->project->documents()->findOrFail($docId);
        Storage::disk('public')->delete($doc->stored_as);
        $doc->delete();
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

        $this->project->delete();
        $this->redirect(route('projects.index'), navigate: true);
    }

    public function render()
    {
        $m = $this->project->money;
        $sched = (float) $m['scheduled'];
        $rec = (float) $m['received'];
        $r = $sched > 0 ? min(100, round(($rec / $sched) * 100)) : 0;

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
            'r' => $r,
            'daysLate' => $daysLate,
        ])->layoutData([
            'header' => $this->project->name,
            'subtitle' => $this->project->code.' · '.($this->project->customer?->company ?? '').($this->project->coordinator ? ' · '.$this->project->coordinator->name : ''),
        ]);
    }
}
