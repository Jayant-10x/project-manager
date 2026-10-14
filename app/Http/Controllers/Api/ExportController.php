<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MoneyCalculationService;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends Controller {
    public function __construct(
        protected ReportExportService     $exportService,
        protected MoneyCalculationService $moneyService
    ) {
    }

    public function export(Request $request): Response {
        $user = $request->user();
        if (!$user->isCoordinator()) {
            return response()->json(['ok' => false, 'error' => 'You do not have permission to do this.'], 403);
        }

        $this->moneyService->refreshOverdueStatus();
        $kind = (string)$request->input('type', 'customers');

        return $this->exportService->exportCsv($kind);
    }
}
