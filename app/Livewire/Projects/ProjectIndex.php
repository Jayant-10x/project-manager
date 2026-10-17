<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ProjectIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $health = '';

    #[Url]
    public string $status = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingHealth(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'health', 'status']);
        $this->resetPage();
    }

    #[On('project-saved')]
    public function refreshProjects(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();

        $query = Project::query()
            ->forUser($user)
            ->with(['customer', 'terms', 'payments', 'milestones', 'coordinator'])
            ->when($this->search, function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', $term)
                        ->orWhere('project_code', 'like', $term)
                        ->orWhere('project_type', 'like', $term)
                        ->orWhereHas('customer', fn ($c) => $c->where('company', 'like', $term))
                        ->orWhereHas('coordinator', fn ($u) => $u->where('name', 'like', $term));
                });
            })
            ->when($this->health, fn ($q) => $q->where('health', $this->health))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->orderByRaw("CASE health WHEN 'Delayed' THEN 1 WHEN 'At Risk' THEN 2 WHEN 'On Track' THEN 3 WHEN 'Completed' THEN 4 ELSE 5 END")
            ->orderBy('delivery_date');

        $allProjects = (clone $query)->get();
        $totalDue = $allProjects->sum(fn ($p) => $p->money['due']);
        $totalOverdue = $allProjects->sum(fn ($p) => $p->money['overdue']);

        return view('livewire.projects.project-index', [
            'projects' => $query->paginate(15),
            'totalDue' => $totalDue,
            'totalOverdue' => $totalOverdue,
        ])->layoutData([
            'header' => 'Projects',
            'subtitle' => 'Timeline, milestones and delivery health',
        ]);
    }
}
