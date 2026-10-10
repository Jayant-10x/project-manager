<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class AppViewController extends Controller {
    public function index(): View {
        $appConfig = [
            'API_URL' => '/api.php',
            'APP_NAME' => config('crm.app_name'),
            'APP_SHORT' => config('crm.app_short'),
            'POLL_MS' => 60000,
            'MAX_UPLOAD_KB' => config('crm.max_upload_kb'),
            'ALLOWED_EXT' => implode(',', config('crm.allowed_extensions')),
        ];

        return view('app', compact('appConfig'));
    }
}
