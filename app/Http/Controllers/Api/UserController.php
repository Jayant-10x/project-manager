<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Followup;
use App\Models\Project;
use App\Models\SalesPerson;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use App\Services\AuditNotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller {
    public function __construct(protected AuditNotificationService $auditService) {
    }

    public function save(Request $request): JsonResponse {
        $user = $request->user();
        if (!$user->isAdmin()) return response()->json(['ok' => false, 'error' => 'Only an Admin can manage users.']);

        $input = $request->input('data', []);
        $name = trim((string)($input['name'] ?? ''));
        $email = strtolower(trim((string)($input['email'] ?? '')));

        if (empty($name)) return response()->json(['ok' => false, 'error' => 'Name is required.']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return response()->json(['ok' => false, 'error' => 'Enter a valid email address.']);

        $pass = (string)($input['password'] ?? '');
        $id = (int)($input['id'] ?? 0);

        if ($id > 0) {
            $targetUser = User::query()->find($id);
            if (!$targetUser) return response()->json(['ok' => false, 'error' => 'User not found.']);

            if (User::query()->whereRaw('LOWER(email) = ?', [$email])->where('id', '<>', $id)->exists()) {
                return response()->json(['ok' => false, 'error' => 'This email is already used by another user.']);
            }

            if (!empty($pass)) {
                if ($prob = $this->checkPassword($pass)) return response()->json(['ok' => false, 'error' => $prob]);
            }

            $newRole = in_array($input['role'] ?? '', ['Admin', 'Coordinator'], true) ? $input['role'] : 'Coordinator';
            $newStatus = in_array($input['status'] ?? '', ['Active', 'Inactive'], true) ? $input['status'] : 'Active';

            if ($targetUser->role === 'Admin' && $targetUser->status === 'Active' && ($newRole !== 'Admin' || $newStatus !== 'Active')) {
                $adminsCount = User::query()->where('role', 'Admin')->where('status', 'Active')->count();
                if ($adminsCount <= 1) {
                    return response()->json(['ok' => false, 'error' => 'This is the last active Admin. Make another user Admin first.']);
                }
            }

            $updateData = [
                'name' => $name,
                'email' => $email,
                'phone' => $input['phone'] ?? null,
                'role' => $newRole,
                'designation' => $input['designation'] ?? null,
                'city' => $input['city'] ?? null,
                'status' => $newStatus,
            ];

            if ($newStatus !== 'Active') {
                $updateData['token'] = null;
                $updateData['online'] = false;
            }

            if (!empty($pass)) {
                $updateData['pass_hash'] = Hash::make($pass);
                $updateData['token'] = null;
                $updateData['must_change'] = true;
                $updateData['fail_count'] = 0;
                $updateData['locked_until'] = null;
            }

            $targetUser->update($updateData);
            $this->auditService->log($user->name, 'update', 'user', $id, '', "User updated — {$name}");

            return response()->json(['ok' => true, 'id' => $id, 'created' => false, 'passwordSet' => !empty($pass)]);
        }

        if (User::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            return response()->json(['ok' => false, 'error' => 'This email is already registered.']);
        }

        if (empty($pass)) $pass = 'Welcome@10x';
        if ($prob = $this->checkPassword($pass)) return response()->json(['ok' => false, 'error' => 'Password: ' . $prob]);

        $newUser = User::query()->create([
            'name' => $name,
            'email' => $email,
            'phone' => $input['phone'] ?? null,
            'role' => in_array($input['role'] ?? '', ['Admin', 'Coordinator'], true) ? $input['role'] : 'Coordinator',
            'designation' => $input['designation'] ?? null,
            'city' => $input['city'] ?? null,
            'status' => in_array($input['status'] ?? '', ['Active', 'Inactive'], true) ? $input['status'] : 'Active',
            'joined' => !empty($input['joined']) ? $input['joined'] : null,
            'pass_hash' => Hash::make($pass),
            'must_change' => true,
        ]);

        $this->auditService->log($user->name, 'create', 'user', $newUser->id, '', "User added — {$name}");

        return response()->json(['ok' => true, 'id' => $newUser->id, 'created' => true, 'tempPassword' => $pass]);
    }

    public function delete(Request $request): JsonResponse {
        $user = $request->user();
        if (!$user->isAdmin()) return response()->json(['ok' => false, 'error' => 'Only an Admin can remove this.']);

        $id = (int)$request->input('id', 0);
        $targetUser = User::query()->find($id);
        if (!$targetUser) return response()->json(['ok' => false, 'error' => 'User not found.']);

        if ((int)$targetUser->id === (int)$user->id) {
            return response()->json(['ok' => false, 'error' => 'You cannot delete your own account.']);
        }

        if ($targetUser->role === 'Admin') {
            $adminsCount = User::query()->where('role', 'Admin')->where('status', 'Active')->count();
            if ($adminsCount <= 1) {
                return response()->json(['ok' => false, 'error' => 'You cannot delete the last active Admin.']);
            }
        }

        $targetUser->delete();
        $this->auditService->log($user->name, 'delete', 'user', $id, '', "User removed — {$targetUser->name}");

        return response()->json(['ok' => true]);
    }

    private function checkPassword(string $pw): ?string {
        if (strlen($pw) < 8) return 'Password must be at least 8 characters.';
        if (!preg_match('/[A-Z]/', $pw)) return 'Password must contain a capital letter.';
        if (!preg_match('/[a-z]/', $pw)) return 'Password must contain a lowercase letter.';
        if (!preg_match('/[0-9]/', $pw)) return 'Password must contain a number.';
        return null;
    }
}
