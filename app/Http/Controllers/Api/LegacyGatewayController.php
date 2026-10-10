<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LegacyGatewayController extends Controller {
    public function dispatch(
        Request                $request,
        AuthController         $auth,
        DashboardController    $dash,
        CustomerController     $cust,
        ProjectController      $proj,
        PaymentController      $pay,
        MilestoneController    $ms,
        DocumentController     $doc,
        FollowupController     $fu,
        TaskController         $task,
        SalesPersonController  $sales,
        UserController         $userCtrl,
        NotificationController $notif,
        SettingController      $setting,
        ExportController       $export
    ): Response {
        $action = (string)$request->input('action', $request->query('action', ''));

        // Public actions
        switch ($action) {
            case 'ping':
                return $auth->ping();
            case 'login':
                return $auth->login($request);
            case 'logout':
                return $auth->logout($request);
            case 'forgotPassoword':
                return $auth->forgotPassword($request);
            case 'resetPassword':
                return $auth->resetPassword($request);
        }

        /*if ($action === 'ping') return $auth->ping();
        if ($action === 'login') return $auth->login($request);
        if ($action === 'forgotPassword') return $auth->forgotPassword($request);
        if ($action === 'checkReset') return $auth->checkReset($request);
        if ($action === 'resetPassword') return $auth->resetPassword($request);*/

        // Authenticate token for all protected actions
        $token = $request->input('token')
            ?: $request->header('X-CRM-Token')
                ?: $request->query('token');

        if (!$token) {
            return response()->json([
                'ok' => false,
                'error' => 'AUTH',
                'message' => 'Your session has expired. Please sign in again.'
            ], 401);
        }

        $user = User::query()->where('token', $token)->where('status', 'Active')->first();
        $ttl = config('crm.session_ttl', 604800);

        if (!$user || ($user->token_at && Carbon::parse($user->token_at)->diffInSeconds(Carbon::now()) > $ttl)) {
            return response()->json([
                'ok' => false,
                'error' => 'AUTH',
                'message' => 'Your session has expired. Please sign in again.'
            ], 401);
        }

        $request->setUserResolver(fn() => $user);
        $user->update(['online' => true, 'last_seen' => Carbon::now()]);

        return match ($action) {
            'me' => $auth->me($request),
            'load' => $dash->load($request),
            'logout' => $auth->logout($request),
            'changePassword' => $auth->changePassword($request),
            'dashboard' => $dash->dashboard($request),
            'search' => $dash->search($request),
            'audit' => $setting->auditFeed(),

            'saveCustomer' => $cust->save($request),
            'deleteCustomer' => $cust->delete($request),
            'bulkImport' => $cust->bulkImport($request),

            'saveProject' => $proj->save($request),
            'deleteProject' => $proj->delete($request),

            'saveTerms' => $pay->saveTerms($request),
            'generateRecurring' => $pay->generateRecurring($request),
            'deleteTerm' => $pay->deleteTerm($request),

            'savePayment' => $pay->savePayment($request),
            'deletePayment' => $pay->deletePayment($request),

            'saveMilestones' => $ms->saveMilestones($request),

            'uploadDocument' => $doc->upload($request),
            'deleteDocument' => $doc->delete($request),

            'saveFollowup' => $fu->save($request),
            'deleteFollowup' => $fu->delete($request),

            'saveTask' => $task->save($request),
            'toggleTask' => $task->toggle($request),
            'deleteTask' => $task->delete($request),

            'saveSales' => $sales->save($request),
            'deleteSales' => $sales->delete($request),

            'saveUser' => $userCtrl->save($request),
            'deleteUser' => $userCtrl->delete($request),

            'notifRead' => $notif->markAsRead($request),
            'saveSettings' => $setting->save($request),
            'export' => $export->export($request),

            default => response()->json(['ok' => false, 'error' => 'Unknown action: ' . $action]),
        };
    }
}
