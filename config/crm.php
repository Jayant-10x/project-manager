<?php

return [
    'app_name' => env('APP_NAME', '10X Agency | Project Coordinator'),
    'app_short' => env('CRM_APP_SHORT', 'PJT CRM'),
    'app_url' => env('APP_URL', 'https://project.the10xagency.in'),
    'session_ttl' => (int) env('CRM_SESSION_TTL', 60 * 60 * 24 * 7),
    'reset_ttl' => (int) env('CRM_RESET_TTL', 60 * 60 * 2),
    'max_login_fails' => (int) env('CRM_MAX_LOGIN_FAILS', 5),
    'lock_minutes' => (int) env('CRM_LOCK_MINUTES', 30),
    'payment_alert_days' => (int) env('CRM_PAYMENT_ALERT_DAYS', 7),
    'amc_alert_days' => (int) env('CRM_AMC_ALERT_DAYS', 7),
    'followup_alert_days' => (int) env('CRM_FOLLOWUP_ALERT_DAYS', 1),
    'max_upload_kb' => (int) env('CRM_MAX_UPLOAD_KB', 10240),
    'allowed_extensions' => explode(',', env('CRM_ALLOWED_UPLOAD_EXT', 'pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,csv,txt,ppt,pptx,zip')),
];
