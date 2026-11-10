<?php

namespace App\Livewire\Team;

use App\Mail\TemporaryPasswordMail;
use App\Models\PasswordResetRequest;
use App\Models\User;
use App\Services\AuditNotificationService;
use App\Services\PasswordGeneratorService;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class TeamIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $roleFilter = '';

    public ?int $viewMemberId = null;

    public ?array $tempCredentials = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'roleFilter' => ['except' => ''],
    ];

    public function resetUserPassword(int $id): void
    {
        $currentUser = auth()->user();
        $downlineIds = current_user_downline_ids();

        if (! in_array($id, $downlineIds, true) && $currentUser->role !== 'Admin') {
            $this->dispatch('toast', ['msg' => 'Unauthorized to reset credentials for this member.', 'type' => 'err']);

            return;
        }

        $user = User::with('reportingTo')->findOrFail($id);

        if ($user->status !== 'Active') {
            $this->dispatch('toast', ['msg' => 'Cannot reset credentials for an inactive member. Activate their account first.', 'type' => 'err']);

            return;
        }

        $temporaryPassword = PasswordGeneratorService::generate(16);

        $user->password = $temporaryPassword;
        $user->must_change = true;
        $user->fail_count = 0;
        $user->locked_until = null;
        $user->save();

        PasswordResetRequest::where('user_id', $user->id)
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
            $user->id,
            '',
            "{$currentUser->role} {$currentUser->name} generated temporary password for team member {$user->name}"
        );

        app(AuditNotificationService::class)->notify(
            $user->id,
            'Password Reset by Manager',
            'Password Reset by Manager',
            'Your temporary credentials were reset by your team manager. Please log in and change your password.',
            'team'
        );

        // Role-differentiated email
        if ($user->role === 'Admin') {
            try {
                Mail::to($user->email)->send(new TemporaryPasswordMail($user, $temporaryPassword, 'Admin'));
            } catch (\Throwable) {
            }
        } elseif ($user->role === 'Manager') {
            try {
                Mail::to($user->email)->send(new TemporaryPasswordMail($user, $temporaryPassword, 'Manager'));
                $adminEmails = User::where('role', 'Admin')->where('status', 'Active')->pluck('email');
                foreach ($adminEmails as $adminEmail) {
                    if ($adminEmail !== $user->email) {
                        Mail::to($adminEmail)->send(new TemporaryPasswordMail($user, $temporaryPassword, 'Admin'));
                    }
                }
            } catch (\Throwable) {
            }
        }

        $loginUrl = url('/login');
        $phone = preg_replace('/[^0-9]/', '', $user->phone ?? '');
        $whatsappText = "Project Manager 10X Agency Login Credentials:\nURL: {$loginUrl}\nEmail: {$user->email}\nTemporary Password: {$temporaryPassword}\nNote: Please set your personal password upon signing in.";

        $this->tempCredentials = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? '',
            'role' => $user->role,
            'designation' => $user->designation ?? $user->role,
            'supervisor' => $user->reportingTo?->name,
            'password' => $temporaryPassword,
            'loginUrl' => $loginUrl,
            'fullText' => $whatsappText,
            'whatsappUrl' => $phone ? "https://wa.me/{$phone}?text=".rawurlencode($whatsappText) : null,
            'mailtoUrl' => "mailto:{$user->email}?subject=".rawurlencode('Project Manager 10X Agency — Your Temporary Password').'&body='.rawurlencode($whatsappText),
        ];

        $this->dispatch('open-modal', 'temp-credentials-modal');
        $this->dispatch('toast', ['msg' => 'Temporary credentials generated successfully', 'type' => 'ok']);
    }

    public function closeTempCredentialsModal(): void
    {
        $this->tempCredentials = null;
        $this->dispatch('close-modal', 'temp-credentials-modal');
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->hasPermission('team', 'view'), 403, 'Unauthorized access to Team Members module.');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function viewMember(int $id): void
    {
        abort_unless(auth()->user()->hasPermission('team', 'view'), 403);
        $this->viewMemberId = $id;
    }

    public function closeViewModal(): void
    {
        $this->viewMemberId = null;
    }

    public function render()
    {
        $currentUser = auth()->user();

        // Downline resolution: Manager sees all team members down the hierarchy; Coordinator sees subordinates
        $teamMemberIds = current_user_downline_ids(excludeSelf: true);

        $query = User::query()
            ->whereIn('id', $teamMemberIds)
            ->with(['reportingTo', 'subordinates'])
            ->withCount(['projects', 'salesProjects', 'techProjects', 'subordinates'])
            ->withSum('salesProjects as total_sales_value', 'value');

        if ($this->search !== '') {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($searchQuery) use ($term) {
                $searchQuery->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('designation', 'like', $term)
                    ->orWhere('city', 'like', $term);
            });
        }

        if ($this->roleFilter !== '') {
            $query->where('role', $this->roleFilter);
        }

        $members = $query->orderByRaw("CASE role WHEN 'Manager' THEN 1 WHEN 'Coordinator' THEN 2 WHEN 'TechPerson' THEN 3 WHEN 'Sales' THEN 4 ELSE 5 END")
            ->orderBy('name')
            ->paginate(12);

        $techCount = User::whereIn('id', $teamMemberIds)->where('role', 'TechPerson')->count();

        $stats = [
            'total' => count($teamMemberIds),
            'coordinators' => User::whereIn('id', $teamMemberIds)->where('role', 'Coordinator')->count(),
            'tech_persons' => $techCount,
            'sales' => User::whereIn('id', $teamMemberIds)->where('role', 'Sales')->count(),
        ];

        $viewingMember = $this->viewMemberId && in_array($this->viewMemberId, $teamMemberIds, true)
            ? User::with(['reportingTo', 'subordinates'])
                ->withCount(['projects', 'salesProjects', 'techProjects', 'subordinates'])
                ->withSum('salesProjects as total_sales_value', 'value')
                ->find($this->viewMemberId)
            : null;

        $pendingResetRequests = PasswordResetRequest::with('user')
            ->where('status', 'Pending')
            ->whereIn('user_id', $teamMemberIds)
            ->get();

        return view('livewire.team.team-index', [
            'members' => $members,
            'stats' => $stats,
            'pendingResetRequests' => $pendingResetRequests,
            'viewingMember' => $viewingMember,
            'currentUser' => $currentUser,
        ])->layoutData([
            'header' => 'Team Members',
            'subtitle' => 'Team directory, reporting hierarchy and member profiles',
        ]);
    }
}
