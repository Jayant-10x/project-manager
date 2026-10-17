<?php

namespace Tests\Feature;

use App\Livewire\Tasks\TaskIndex;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaskModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_tasks_page_renders_add_task_modal_trigger(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TaskIndex::class)
            ->assertSee('Add Task')
            ->assertSee('Add New Task')
            ->assertSee('add-task-modal');
    }

    public function test_submitting_task_modal_persists_task_and_dispatches_events(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TaskIndex::class)
            ->set('title', 'Follow up on NDA document')
            ->set('priority', 'High')
            ->call('addTask')
            ->assertDispatched('task-saved')
            ->assertDispatched('close-modal', 'add-task-modal')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('tasks', [
            'title' => 'Follow up on NDA document',
            'priority' => 'High',
            'assigned_to' => $user->id,
            'done' => false,
        ]);
    }

    public function test_toggling_task_done_updates_state_and_renders_correct_checkbox(): void
    {
        $user = User::factory()->create();
        $task = Task::create([
            'title' => 'Initial Task',
            'priority' => 'Medium',
            'assigned_to' => $user->id,
            'created_by' => $user->id,
            'done' => false,
        ]);

        Livewire::actingAs($user)
            ->test(TaskIndex::class)
            ->assertSeeHtml('wire:key="task-chk-'.$task->id.'-open"')
            ->assertDontSeeHtml('wire:key="task-chk-'.$task->id.'-done"')
            ->call('toggleDone', $task->id)
            ->assertDispatched('task-updated')
            ->assertSeeHtml('wire:key="task-chk-'.$task->id.'-done"');

        $this->assertTrue((bool) $task->fresh()->done);
    }
}
