<?php

use App\Http\Controllers\Api\NotificationPollController;
use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Livewire\Audit\AuditIndex;
use App\Livewire\Auth\ForcePasswordChange;
use App\Livewire\Calendar\PaymentCalendarIndex;
use App\Livewire\Customers\CustomerDetail;
use App\Livewire\Customers\CustomerIndex;
use App\Livewire\Dashboard\DashboardIndex;
use App\Livewire\Documents\DocumentIndex;
use App\Livewire\Followups\FollowupIndex;
use App\Livewire\Notifications\NotificationDetail;
use App\Livewire\Notifications\NotificationIndex;
use App\Livewire\Payments\PaymentIndex;
use App\Livewire\Projects\ProjectDetail;
use App\Livewire\Projects\ProjectIndex;
use App\Livewire\Reports\ReportIndex;
use App\Livewire\Sales\SalesIndex;
use App\Livewire\Schedule\ScheduleIndex;
use App\Livewire\Settings\SettingIndex;
use App\Livewire\Tasks\TaskIndex;
use App\Livewire\Team\TeamIndex;
use App\Livewire\Today\TodayWorkIndex;
use App\Livewire\Users\UserIndex;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::redirect('/', '/dashboard');

// Dedicated change-password route
Route::middleware(['auth'])->group(function () {
    Route::get('/change-password', ForcePasswordChange::class)->name('password.change');
});

// Primary CRM authenticated route group
Route::middleware(['auth', 'verified', EnsurePasswordIsChanged::class])->group(function () {
    Route::get('/dashboard', DashboardIndex::class)->name('dashboard');

    // Hierarchy & Team Governance
    Route::get('/users', UserIndex::class)->name('users.index');
    Route::get('/team', TeamIndex::class)->name('team.index');
    Route::get('/settings', SettingIndex::class)->name('settings.index');

    // Clients & Projects
    Route::get('/customers', CustomerIndex::class)->name('customers.index');
    Route::get('/customer/{customer}', CustomerDetail::class)->name('customers.show');
    Route::get('/projects', ProjectIndex::class)->name('projects.index');
    Route::get('/project/{project}', ProjectDetail::class)->name('projects.show');

    // Document Download
    Route::get('/documents/{document}/download', [DocumentDownloadController::class, 'download'])->name('documents.download');

    // Module 3, 4 & 5: Money & Operational Queue
    Route::get('/schedules', ScheduleIndex::class)->name('schedule.index');
    Route::get('/payments', PaymentIndex::class)->name('payments.index');
    Route::get('/calendar', PaymentCalendarIndex::class)->name('calendar.index');
    Route::get('/today-work', TodayWorkIndex::class)->name('today.index');

    // Reports & Analytics
    Route::get('/reports', ReportIndex::class)->name('reports.index');

    // System - Notifications & Audit Logs
    Route::get('/notifications', NotificationIndex::class)->name('notifications.index');
    Route::get('/notifications/{notification}', NotificationDetail::class)->name('notifications.show');
    Route::get('/activity-log', AuditIndex::class)->name('audit.index');

    // User Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/my-tasks', TaskIndex::class)->name('tasks.index');
    Route::get('/documents', DocumentIndex::class)->name('documents.index');
    Route::get('/follow-ups', FollowupIndex::class)->name('followups.index');
    Route::get('/sales-team', SalesIndex::class)->name('sales.index');

    // Lightweight notification polling route
    Route::get('/api/notifications/poll', [NotificationPollController::class, 'check'])
        ->name('api.notifications.poll');
});

// Direct file access route ensuring zero 403/symlink failures
Route::get('/storage/{filename}', function (string $filename) {
    if (file_exists(public_path('uploads/'.$filename))) {
        return response()->file(public_path('uploads/'.$filename));
    }
    if (Storage::disk('public')->exists($filename)) {
        return response()->file(Storage::disk('public')->path($filename));
    }
    abort(404);
})->where('filename', '.*')->name('storage.file');

require __DIR__.'/auth.php';

// Fallback route ensuring unmatched URLs execute the web middleware group with session/auth initialized
Route::fallback(function () {
    abort(404);
});
