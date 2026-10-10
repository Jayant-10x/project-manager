<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SalesPerson;
use App\Services\AuditNotificationService;
use App\Services\CodeGeneratorService;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class CustomerController extends Controller
{
    public function __construct(
        protected CodeGeneratorService $codeService,
        protected AuditNotificationService $auditService,
        protected DocumentService $documentService
    ) {}

    public function save(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->isCoordinator()) {
            return response()->json(['ok' => false, 'error' => 'Only a Coordinator can add or edit customers.']);
        }

        $input = $request->input('data', []);
        $company = trim((string) ($input['company'] ?? ''));
        if (empty($company)) {
            return response()->json(['ok' => false, 'error' => 'Company name is required.']);
        }

        $email = strtolower(trim((string) ($input['email'] ?? '')));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['ok' => false, 'error' => 'Enter a valid email address.']);
        }

        $payload = [
            'company' => $company,
            'contact_name' => $input['contactName'] ?? null,
            'email' => $email ?: null,
            'phone' => $input['phone'] ?? null,
            'alt_phone' => $input['altPhone'] ?? null,
            'city' => $input['city'] ?? null,
            'state' => $input['state'] ?? null,
            'address' => $input['address'] ?? null,
            'gstin' => isset($input['gstin']) ? strtoupper(trim($input['gstin'])) : null,
            'pan' => isset($input['pan']) ? strtoupper(trim($input['pan'])) : null,
            'billing_name' => $input['billingName'] ?? null,
            'billing_addr' => $input['billingAddr'] ?? null,
            'source' => $input['source'] ?? null,
            'industry' => $input['industry'] ?? null,
            'onboarded_by' => $input['onboardedBy'] ?? null,
            'status' => in_array($input['status'] ?? '', ['Active', 'On Hold', 'Completed', 'Churned'], true) ? $input['status'] : 'Active',
            'internal_notes' => $input['internalNotes'] ?? null,
        ];

        $id = (int) ($input['id'] ?? 0);
        if ($id > 0) {
            $customer = Customer::query()->find($id);
            if (!$customer) {
                return response()->json(['ok' => false, 'error' => 'Customer not found.']);
            }

            $customer->update($payload);
            $this->auditService->log($user->name, 'update', 'customer', $id, $customer->code, 'Customer updated — ' . $company);

            return response()->json(['ok' => true, 'id' => $id, 'code' => $customer->code, 'created' => false]);
        }

        $existing = Customer::query()->whereRaw('LOWER(company) = ?', [strtolower($company)])->first();
        if ($existing) {
            return response()->json(['ok' => false, 'error' => 'A customer with this name already exists (' . $existing->code . ').']);
        }

        $code = $this->codeService->generate('customer', 'CUS-');
        $payload['code'] = $code;
        $payload['created_by'] = $user->name;

        $customer = Customer::query()->create($payload);

        $this->auditService->log($user->name, 'create', 'customer', $customer->id, $code, 'Customer onboarded — ' . $company);
        $this->auditService->notifyCoordinators('customer', 'New customer onboarded', "{$company} ({$code})", 'customer', $customer->id);

        return response()->json(['ok' => true, 'id' => $customer->id, 'code' => $code, 'created' => true]);
    }

    public function delete(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->isAdmin()) {
            return response()->json(['ok' => false, 'error' => 'Only an Admin can delete customers.']);
        }

        $id = (int) $request->input('id', 0);
        $customer = Customer::query()->with(['projects', 'documents'])->find($id);
        if (!$customer) {
            return response()->json(['ok' => false, 'error' => 'Customer not found.']);
        }

        $projectCount = $customer->projects()->count();
        if ($projectCount > 0 && !$request->boolean('force')) {
            return response()->json([
                'ok' => false,
                'error' => "{$customer->company} has {$projectCount} project(s). Deleting the customer will also delete those projects, their payments and documents.",
                'needForce' => true,
            ]);
        }

        // Delete associated documents physical files
        foreach ($customer->documents as $doc) {
            $this->documentService->deleteDocument($doc);
        }

        foreach ($customer->projects as $proj) {
            foreach ($proj->documents as $doc) {
                $this->documentService->deleteDocument($doc);
            }
        }

        $customer->delete();
        $this->auditService->log($user->name, 'delete', 'customer', $id, $customer->code, 'Customer deleted — ' . $customer->company);

        return response()->json(['ok' => true]);
    }

    public function bulkImport(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->isCoordinator()) {
            return response()->json(['ok' => false, 'error' => 'You do not have permission to do this.']);
        }

        $rows = $request->input('rows', []);
        $spName = trim((string) $request->input('salesPerson', ''));

        if (!is_array($rows) || empty($rows)) {
            return response()->json(['ok' => false, 'error' => 'No rows found.']);
        }

        if (count($rows) > 1000) {
            return response()->json(['ok' => false, 'error' => 'You can import at most 1000 rows at a time.']);
        }

        if (!empty($spName)) {
            SalesPerson::query()->firstOrCreate(['name' => $spName], ['status' => 'Active']);
        }

        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                $skipped++;
                continue;
            }

            $company = trim((string) ($row['company'] ?? ''));
            if (empty($company)) {
                $skipped++;
                continue;
            }

            if (Customer::query()->whereRaw('LOWER(company) = ?', [strtolower($company)])->exists()) {
                $skipped++;
                continue;
            }

            try {
                $code = $this->codeService->generate('customer', 'CUS-');
                $email = strtolower(trim((string) ($row['email'] ?? '')));

                Customer::query()->create([
                    'code' => $code,
                    'company' => $company,
                    'contact_name' => $row['contactName'] ?? null,
                    'email' => filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null,
                    'phone' => $row['phone'] ?? null,
                    'city' => $row['city'] ?? null,
                    'state' => $row['state'] ?? null,
                    'gstin' => !empty($row['gstin']) ? strtoupper(trim($row['gstin'])) : null,
                    'source' => $row['source'] ?? null,
                    'industry' => $row['industry'] ?? null,
                    'onboarded_by' => $row['onboardedBy'] ?? $spName,
                    'status' => 'Active',
                    'created_by' => $user->name,
                ]);
                $created++;
            } catch (Throwable $e) {
                $errors[] = 'Row ' . ($index + 1) . ': ' . $e->getMessage();
            }
        }

        $this->auditService->log($user->name, 'import', 'customer', null, '', "{$created} customers imported ({$skipped} skipped)");

        return response()->json([
            'ok' => true,
            'made' => $created,
            'skipped' => $skipped,
            'errors' => array_slice($errors, 0, 10),
        ]);
    }
}
