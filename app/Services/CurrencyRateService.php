<?php

// app/Services/CurrencyRateService.php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CurrencyRateService
{
    public function getUsdInrRate(): float
    {
        return Cache::remember('usd_inr_exchange_rate', 86400, function () {
            try {
                $response = Http::timeout(4)->get('https://open.er-api.com/v6/latest/USD');
                if ($response->successful() && isset($response['rates']['INR'])) {
                    $rate = (float) $response['rates']['INR'];
                    Setting::query()->updateOrCreate(['key' => 'usd_inr_rate'], ['value' => $rate]);

                    return $rate;
                }
            } catch (\Throwable $exception) {
                // Silently fallback if network fails
            }

            return (float) Setting::getValue('usd_inr_rate', 83.0);
        });
    }
}
