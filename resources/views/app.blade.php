<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $appConfig['APP_NAME'] }} CRM</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo.png') }}">
    <link rel="stylesheet" href="{{ asset('css/crm-style.css') }}">
</head>
<body>
<div id="root">
    <div class="loader">
        <div class="spinner"></div>
        <div>Loading CRM...</div>
    </div>
</div>
<noscript>
    <p style="padding:24px;font-family:sans-serif">Please enable JavaScript to use the CRM.</p>
</noscript>

<script>
    window.PJT_CONFIG = @json($appConfig);
</script>
<script src="{{ asset('js/crm-app.js') }}" defer></script>
</body>
</html>
