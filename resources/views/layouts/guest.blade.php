<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    @php
        $pageTitle = trim($__env->yieldContent('title'));
        if (empty($pageTitle)) {
            $pageTitle = $title ?? ($header ?? null);
        }
        $appName = config('app.name', 'Project Manager 10X Agency');
        if ($pageTitle && str_contains($pageTitle, $appName)) {
            $fullTitle = $pageTitle;
        } else {
            $fullTitle = $pageTitle ? "{$pageTitle} | {$appName}" : $appName;
        }
    @endphp
    <title>{{ $fullTitle }}</title>

    <link rel="icon" type="image/png" href="{{ asset('assets/logo.png') }}">

    <!-- Vite & CRM Stylesheet -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/crm-style.css') }}">
</head>
<body class="font-sans antialiased">
{{ $slot ?? '' }}
@yield('content')
</body>
</html>
