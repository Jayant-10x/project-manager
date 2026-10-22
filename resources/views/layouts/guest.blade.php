<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ config('app.name', '10X Agency CRM') }}</title>

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
