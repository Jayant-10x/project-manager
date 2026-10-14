<?php

namespace App\Livewire\Audit;

use App\Models\ActivityLog;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class AuditIndex extends Component {
    use WithPagination;

    public function render() {
        $logs = ActivityLog::with('user')
            ->orderByDesc('id')
            ->paginate(30);

        return view('livewire.audit.audit-index', [
            'logs' => $logs,
        ])->layoutData([
            'header' => 'Activity Log',
            'subtitle' => 'Who changed what, and when',
        ]);
    }
}
