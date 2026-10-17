<?php

namespace Tests\Feature;

use App\Livewire\Components\DynamicCounter;
use App\Models\Customer;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DynamicCounterTest extends TestCase
{
    use RefreshDatabase;

    public function test_counter_renders_customers_and_reacts_to_events(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(DynamicCounter::class, ['type' => 'customers'])
            ->assertSee('0');

        Customer::create([
            'customer_code' => 'CUS-TEST',
            'company' => 'Acme Corp',
            'status' => 'Active',
        ]);

        Livewire::actingAs($user)
            ->test(DynamicCounter::class, ['type' => 'customers'])
            ->assertSee('1')
            ->dispatch('customer-saved')
            ->assertSee('1');
    }

    public function test_counter_hides_when_zero_and_configured(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(DynamicCounter::class, ['type' => 'tasks', 'hideZero' => true])
            ->assertSee('style="display: none !important;"', false);

        Task::create([
            'title' => 'Urgent Followup',
            'priority' => 'High',
            'assigned_to' => $user->id,
            'created_by' => $user->id,
            'done' => false,
        ]);

        Livewire::actingAs($user)
            ->test(DynamicCounter::class, ['type' => 'tasks', 'hideZero' => true])
            ->assertDontSee('style="display: none !important;"', false)
            ->assertSee('1');
    }
}
