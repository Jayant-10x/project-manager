@php
    $rawMessage = $exception?->getMessage();
    $hasCustomMessage = !empty($rawMessage) && !in_array($rawMessage, ['This action is unauthorized.', 'Unauthorized.', 'Forbidden']);

    if ($hasCustomMessage) {
        $message = $rawMessage;
    } elseif (request()->is('report*')) {
        $message = 'You do not have permission to view executive financial reports.';
    } elseif (request()->is('payment*') || request()->is('schedule*')) {
        $message = 'You do not have permission to access financial ledgers or payment schedules.';
    } elseif (request()->is('customer*')) {
        $message = 'You do not have permission to view this customer record.';
    } elseif (request()->is('project*')) {
        $message = 'You do not have permission to view this project.';
    } else {
        $message = 'You do not have permission to access this resource. Please contact your manager or system administrator.';
    }
@endphp

@extends('errors.layout', [
    'code' => '403',
    'title' => 'Access Restricted',
    'heading' => 'Access Restricted',
    'message' => $message,
    'badgeClass' => 'bg-red-50 text-red-800 border border-red-200',
])

@section('icon')
    <svg class="w-16 h-16 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
        <circle cx="12" cy="16" r="1"></circle>
    </svg>
@endsection

@section('actions')
    <a href="{{ route('dashboard') }}" class="btn btn-primary">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
        Return to My Workspace
    </a>
    <button onclick="window.history.back()" type="button" class="btn btn-ghost">
        Back to Previous Page
    </button>
@endsection
