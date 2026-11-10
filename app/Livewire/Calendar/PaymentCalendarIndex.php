<?php

namespace App\Livewire\Calendar;

use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Models\User;
use App\Services\MoneyCalculationService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class PaymentCalendarIndex extends Component
{
    #[Url]
    public string $month = '';

    #[Url]
    public ?int $selectedUserId = null;

    #[Url]
    public string $typeFilter = 'all'; // 'all', 'schedules', 'receipts'

    public ?string $selectedDate = null;

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user || (! $user->hasPermission('payments', 'view') && ! $user->hasPermission('schedules', 'view'))) {
            abort(403, 'Unauthorized access to payment calendar.');
        }

        if (empty($this->month)) {
            $this->month = Carbon::today()->format('Y-m');
        }
    }

    public function prevMonth(): void
    {
        try {
            $this->month = Carbon::parse($this->month.'-01')->subMonth()->format('Y-m');
        } catch (\Throwable) {
            $this->month = Carbon::today()->startOfMonth()->subMonth()->format('Y-m');
        }
    }

    public function nextMonth(): void
    {
        try {
            $this->month = Carbon::parse($this->month.'-01')->addMonth()->format('Y-m');
        } catch (\Throwable) {
            $this->month = Carbon::today()->startOfMonth()->addMonth()->format('Y-m');
        }
    }

    public function goToToday(): void
    {
        $today = Carbon::today();
        $this->month = $today->format('Y-m');
        $this->selectedDate = $today->toDateString();
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
    }

    public function closeDateModal(): void
    {
        $this->selectedDate = null;
    }

    public function clearFilters(): void
    {
        $this->selectedUserId = null;
        $this->typeFilter = 'all';
    }

    #[On('payment-saved')]
    #[On('payment-deleted')]
    #[On('project-saved')]
    public function refreshCalendar(): void
    {
        // Re-renders the component and recalculates day payments
    }

    public function render(MoneyCalculationService $moneyService)
    {
        $moneyService->refreshOverdueStatus();

        $user = auth()->user();
        $role = $user->role ?? 'Sales';

        // Downline / Team filter list setup
        $accessibleUsers = collect();
        if ($role === 'Admin') {
            $accessibleUsers = User::where('status', 'Active')
                ->where('id', '!=', $user->id)
                ->orderBy('role')
                ->orderBy('name', 'asc')
                ->get();
        } elseif ($role === 'Manager' || $role === 'Coordinator') {
            $downlineIds = current_user_downline_ids(excludeSelf: true);
            $accessibleUsers = User::whereIn('id', $downlineIds)
                ->where('status', 'Active')
                ->orderBy('role')
                ->orderBy('name', 'asc')
                ->get();
        }

        // Active viewing target
        $activeViewingUser = $user;
        if ($this->selectedUserId && $accessibleUsers->contains('id', (int) $this->selectedUserId)) {
            $activeViewingUser = User::find($this->selectedUserId) ?? $user;
        }

        // Compute active month Carbon instance
        try {
            $currentMonth = Carbon::parse($this->month.'-01')->startOfMonth();
        } catch (\Throwable) {
            $currentMonth = Carbon::today()->startOfMonth();
            $this->month = $currentMonth->format('Y-m');
        }

        $gridStart = $currentMonth->copy()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $currentMonth->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $monthStart = $currentMonth->copy()->startOfMonth()->toDateString();
        $monthEnd = $currentMonth->copy()->endOfMonth()->toDateString();

        // Projects scoped to active viewing user
        $projectIds = Project::forUser($activeViewingUser)->pluck('id');

        // Query terms in calendar grid window
        $terms = PaymentTerm::query()
            ->whereIn('project_id', $projectIds)
            ->whereBetween('due_date', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->with(['project.customer', 'milestone', 'payments'])
            ->orderBy('due_date')
            ->get();

        // Query payments in calendar grid window
        $payments = Payment::query()
            ->whereIn('project_id', $projectIds)
            ->whereBetween('paid_on', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->with(['project.customer', 'customer', 'term.milestone'])
            ->orderBy('paid_on')
            ->get();

        // Monthly KPI sums (for the current month view)
        $monthTerms = $terms->filter(fn ($term) => $term->due_date && $term->due_date->format('Y-m') === $this->month);
        $monthPayments = $payments->filter(fn ($payment) => $payment->paid_on && $payment->paid_on->format('Y-m') === $this->month);

        $monthlyScheduled = (float) $monthTerms->sum('amount');
        $monthlyReceived = (float) $monthPayments->sum('amount');
        $monthlyPending = (float) $monthTerms->whereIn('status', ['Pending', 'Partial'])->sum('balance');
        $monthlyOverdue = (float) $monthTerms->where('status', 'Overdue')->sum('balance');

        // Group by Y-m-d
        $termsByDate = $terms->groupBy(fn ($term) => $term->due_date ? Carbon::parse($term->due_date)->toDateString() : '');
        $paymentsByDate = $payments->groupBy(fn ($payment) => $payment->paid_on ? Carbon::parse($payment->paid_on)->toDateString() : '');

        // Build grid days array
        $days = [];
        $cursor = $gridStart->copy();
        $todayStr = Carbon::today()->toDateString();

        while ($cursor->lte($gridEnd)) {
            $dateStr = $cursor->toDateString();
            $dayTerms = $termsByDate->get($dateStr, collect());
            $dayPayments = $paymentsByDate->get($dateStr, collect());

            $days[] = [
                'date' => $dateStr,
                'dayNumber' => $cursor->day,
                'dayOfWeek' => $cursor->dayOfWeek,
                'isCurrentMonth' => $cursor->format('Y-m') === $this->month,
                'isToday' => $dateStr === $todayStr,
                'isPast' => $dateStr < $todayStr,
                'scheduledCount' => $dayTerms->count(),
                'scheduledAmount' => (float) $dayTerms->sum('amount'),
                'scheduledBalance' => (float) $dayTerms->sum('balance'),
                'receivedCount' => $dayPayments->count(),
                'receivedAmount' => (float) $dayPayments->sum('amount'),
                'hasOverdue' => $dayTerms->contains(fn ($term) => $term->status === 'Overdue' || ($term->balance > 0 && $dateStr < $todayStr)),
                'terms' => $dayTerms,
                'payments' => $dayPayments,
            ];

            $cursor->addDay();
        }

        // Details for selected date
        $selectedDateTerms = collect();
        $selectedDatePayments = collect();
        if ($this->selectedDate) {
            $selectedDateTerms = $termsByDate->get($this->selectedDate) ?? PaymentTerm::whereIn('project_id', $projectIds)
                ->whereDate('due_date', $this->selectedDate)
                ->with(['project.customer', 'milestone'])
                ->get();

            $selectedDatePayments = $paymentsByDate->get($this->selectedDate) ?? Payment::whereIn('project_id', $projectIds)
                ->whereDate('paid_on', $this->selectedDate)
                ->with(['project.customer', 'customer', 'term.milestone'])
                ->get();
        }

        return view('livewire.calendar.payment-calendar-index', [
            'days' => $days,
            'currentMonth' => $currentMonth,
            'monthlyScheduled' => $monthlyScheduled,
            'monthlyReceived' => $monthlyReceived,
            'monthlyPending' => $monthlyPending,
            'monthlyOverdue' => $monthlyOverdue,
            'accessibleUsers' => $accessibleUsers,
            'activeViewingUser' => $activeViewingUser,
            'selectedDateTerms' => $selectedDateTerms,
            'selectedDatePayments' => $selectedDatePayments,
        ]);
    }
}
