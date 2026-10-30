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
                    ->where(function ($q) use ($term) {
                        $q->where('company', 'like', $term)
                            ->orWhere('customer_code', 'like', $term)
                            ->orWhere('contact_name', 'like', $term);
                    })
                    ->limit(5)
                    ->get();

                foreach ($customers as $c) {
                    $results[] = ['type' => 'Customer', 'title' => $c->display_name, 'sub' => $c->customer_code, 'url' => route('customers.show', $c)];
                }
            }

            if ($user && $user->hasPermission('projects', 'view')) {
                $projects = Project::query()
                    ->forUser($user)
                    ->where(function ($q) use ($term) {
                        $q->where('name', 'like', $term)
                            ->orWhere('project_code', 'like', $term);
                    })
                    ->limit(5)
                    ->get();

                foreach ($projects as $p) {
                    $results[] = ['type' => 'Project', 'title' => $p->name, 'sub' => $p->project_code.' ('.$p->health.')', 'url' => route('projects.show', $p)];
                }
            }
        }

        return view('livewire.components.global-search', compact('results'));
    }
}
