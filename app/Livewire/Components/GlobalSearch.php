<?php

namespace App\Livewire\Components;

use App\Models\Customer;
use App\Models\Project;
use Livewire\Component;

class GlobalSearch extends Component
{
    public string $query = '';

    public function render()
    {
        $results = [];

        if (mb_strlen(trim($this->query)) >= 2) {
            $user = auth()->user();
            $term = '%'.trim($this->query).'%';

            if ($user && $user->hasPermission('customers', 'view') && ! $user->isTechPerson()) {
                $customers = Customer::query()
                    ->forUser($user)
                    ->where(function ($searchQuery) use ($term) {
                        $searchQuery->where('company', 'like', $term)
                            ->orWhere('customer_code', 'like', $term)
                            ->orWhere('contact_name', 'like', $term);
                    })
                    ->limit(5)
                    ->get();

                foreach ($customers as $customer) {
                    $results[] = ['type' => 'Customer', 'title' => $customer->display_name, 'sub' => $customer->customer_code, 'url' => route('customers.show', $customer)];
                }
            }

            if ($user && $user->hasPermission('projects', 'view')) {
                $projects = Project::query()
                    ->forUser($user)
                    ->where(function ($searchQuery) use ($term) {
                        $searchQuery->where('name', 'like', $term)
                            ->orWhere('project_code', 'like', $term);
                    })
                    ->limit(5)
                    ->get();

                foreach ($projects as $project) {
                    $results[] = ['type' => 'Project', 'title' => $project->name, 'sub' => $project->project_code.' ('.$project->health.')', 'url' => route('projects.show', $project)];
                }
            }
        }

        return view('livewire.components.global-search', compact('results'));
    }
}
