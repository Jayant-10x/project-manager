<?php

namespace App\Livewire\Users;

use App\Mail\TemporaryPasswordMail;
use App\Models\PasswordResetRequest;
use App\Models\User;
use App\Services\AuditNotificationService;
use App\Services\PasswordGeneratorService;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class UserIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $roleFilter = '';

    public string $statusFilter = '';

    public ?int $viewUserId = null;

    public ?array $tempCredentials = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'roleFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function resetUserPassword(int $id): void
    {
        abort_unless(auth()->user()->hasPermission('users', 'edit'), 403);

        $currentUser = auth()->user();
        $target = User::with('reportingTo')->findOrFail($id);

        if ($target->status !== 'Active') {
            $this->dispatch('toast', ['msg' => 'Cannot reset credentials for an inactive user. Activate the account first.', 'type' => 'err']);

            return;
        }

        $temporaryPassword = PasswordGeneratorService::generate(16);

        $target->password = $temporaryPassword;
        $target->must_change = true;
        $target->fail_count = 0;
        $target->locked_until = null;
        $target->save();

        PasswordResetRequest::where('user_id', $target->id)
            ->where('status', 'Pending')
            ->update([
                'status' => 'Resolved',
                'resolved_by' => $currentUser->id,
                'resolved_at' => now(),
            ]);

        app(AuditNotificationService::class)->log(
            $currentUser->id,
            'Reset User Password',
            'User',
            $target->id,
            '',
            "{$currentUser->role} {$currentUser->name} generated temporary password for {$target->name} ({$target->role})"
        );

        app(AuditNotificationService::class)->notify(
            $target->id,
            'Password Reset by Administrator',
            'Password Reset by Administrator',
            'Your temporary password has been generated. Please sign in and set a new password.',
            'users'
        );

        if ($target->role === 'Admin') {
            try {
                Mail::to($target->email)->send(new TemporaryPasswordMail($target, $temporaryPassword, 'Admin'));
            } catch (\Throwable) {
            }
        } elseif ($target->role === 'Manager') {
            try {
                Mail::to($target->email)->send(new TemporaryPasswordMail($target, $temporaryPassword, 'Manager'));
                $adminEmails = User::where('role', 'Admin')->where('status', 'Active')->pluck('email');
                foreach ($adminEmails as $admEmail) {
                    if ($admEmail !== $target->email) {
                        Mail::to($admEmail)->send(new TemporaryPasswordMail($target, $temporaryPassword, 'Admin'));
                    }
                }
            } catch (\Throwable) {
            }
        }

        $loginUrl = url('/login');
        $phone = preg_replace('/[^0-9]/', '', $target->phone ?? '');
        $whatsappText = "Project Manager 10X Agency Login Credentials:\nURL: {$loginUrl}\nEmail: {$target->email}\nTemporary Password: {$temporaryPassword}\nNote: Please set your personal password upon sign-in.";

        $this->tempCredentials = [
            'id' => $target->id,
            'name' => $target->name,
            'email' => $target->email,
            'phone' => $target->phone ?? '',
            'role' => $target->role,
            'designation' => $target->designation ?? $target->role,
            'supervisor' => $target->reportingTo?->name,
            'password' => $temporaryPassword,
            'loginUrl' => $loginUrl,
            'fullText' => $whatsappText,
            'whatsappUrl' => $phone ? "https://wa.me/{$phone}?text=".rawurlencode($whatsappText) : null,
            'mailtoUrl' => "mailto:{$target->email}?subject=".rawurlencode('Project Manager 10X Agency — Your Temporary Password').'&body='.rawurlencode($whatsappText),
        ];

        $this->dispatch('open-modal', 'temp-credentials-modal');
        $this->dispatch('toast', ['msg' => 'Temporary password generated successfully', 'type' => 'ok']);
    }

    public function closeTempCredentialsModal(): void
    {
        $this->tempCredentials = null;
        $this->dispatch('close-modal', 'temp-credentials-modal');
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->hasPermission('users', 'view'), 403, 'Unauthorized access to Users module.');
    }

    #[On('user-saved')]
    public function refreshUsers(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        abort_unless(auth()->user()->hasPermission('users', 'add'), 403);
        $this->dispatch('open-user-modal', id: null);
    }

    public function openEditModal(int $id): void
    {
        abort_unless(auth()->user()->hasPermission('users', 'edit'), 403);
        $this->dispatch('open-user-modal', id: $id);
    }

    public function viewUser(int $id): void
    {
        abort_unless(auth()->user()->hasPermission('users', 'view'), 403);
        $this->viewUserId = $id;
    }

    public function closeViewModal(): void
    {
        $this->viewUserId = null;
    }

    public function deleteUser(int $id): void
    {
        abort_unless(auth()->user()->hasPermission('users', 'delete'), 403);

        if (auth()->id() === $id) {
            $this->dispatch('toast', ['msg' => 'You cannot delete your own account.', 'type' => 'err']);

            return;
        }

        $target = User::findOrFail($id);

        if ($target->role === 'Admin' && $target->status === 'Active') {
            $adminCount = User::where('role', 'Admin')->where('status', 'Active')->count();
            if ($adminCount <= 1) {
                $this->dispatch('toast', ['msg' => 'Cannot delete the last active Administrator.', 'type' => 'err']);

                return;
            }
        }

        $targetName = $target->name;
        $targetRole = $target->role;

        $target->delete();

        app(AuditNotificationService::class)->log(
            auth()->id(),
            'Deleted User',
            'User',
            $id,
            '',
            "Deleted user {$targetName} ({$targetRole})"
        );

        $this->dispatch('toast', ['msg' => "User {$targetName} removed successfully.", 'type' => 'ok']);
    }

    public function toggleUserStatus(int $id): void
    {
        abort_unless(auth()->user()->hasPermission('users', 'edit'), 403);

        if (auth()->id() === $id) {
            $this->dispatch('toast', ['msg' => 'You cannot change your own account status.', 'type' => 'err']);

            return;
        }

        $target = User::findOrFail($id);

        if ($target->role === 'Admin' && $target->status === 'Active') {
            $adminCount = User::where('role', 'Admin')->where('status', 'Active')->count();
            if ($adminCount <= 1) {
                $this->dispatch('toast', ['msg' => 'Cannot deactivate the last active Administrator.', 'type' => 'err']);

                return;
            }
        }

        $newStatus = $target->status === 'Active' ? 'Inactive' : 'Active';
        $target->update(['status' => $newStatus]);

        app(AuditNotificationService::class)->log(
            auth()->id(),
            'Updated User Status',
            'User',
            $target->id,
            '',
            "Changed status of {$target->name} to {$newStatus}"
        );

        $this->dispatch('toast', ['msg' => "Status of {$target->name} changed to {$newStatus}.", 'type' => 'ok']);
    }

    public function render()
    {
        $query = User::query()
            ->with(['reportingTo', 'subordinates'])
            ->withCount(['projects', 'salesProjects', 'subordinates'])
            ->withSum('salesProjects as total_sales_value', 'value');

        if ($this->search !== '') {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('designation', 'like', $term)
                    ->orWhere('city', 'like', $term);
            });
        }

        if ($this->roleFilter !== '') {
            $query->where('role', $this->roleFilter);
        }

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        $users = $query->orderByRaw("CASE role WHEN 'Admin' THEN 1 WHEN 'Manager' THEN 2 WHEN 'Coordinator' THEN 3 WHEN 'Sales' THEN 4 ELSE 5 END")
            ->orderBy('name')
            ->paginate(12);

        $stats = [
            'total' => User::count(),
            'admins' => User::where('role', 'Admin')->count(),
            'managers' => User::where('role', 'Manager')->count(),
            'coordinators' => User::where('role', 'Coordinator')->count(),
            'sales' => User::where('role', 'Sales')->count(),
        ];

        $viewingUser = $this->viewUserId
            ? User::with(['reportingTo', 'subordinates'])
                ->withCount(['projects', 'salesProjects', 'subordinates'])
                ->withSum('salesProjects as total_sales_value', 'value')
                ->find($this->viewUserId)
            : null;

        return view('livewire.users.user-index', [
            'users' => $users,
            'stats' => $stats,
            'viewingUser' => $viewingUser,
        ])->layoutData([
            'header' => 'Users & Access',
            'subtitle' => 'System accounts, role hierarchy & credential provisioning',
        ]);
    }
}
