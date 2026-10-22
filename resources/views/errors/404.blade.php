@php
    $rawMessage = $exception?->getMessage();
    $hasCustomMessage = !empty($rawMessage) && !str_starts_with($rawMessage, 'The route') && !str_contains($rawMessage, 'No query results for model');

    if ($hasCustomMessage) {
        $title = 'Record Not Found';
        $heading = 'Record Not Found';
        $message = $rawMessage;
        $contextAction = null;
    } elseif (request()->is('customer*')) {
        $title = 'Customer Not Found';
        $heading = 'Customer Not Found';
        $message = 'The customer you requested does not exist or may have been deleted.';
        $contextAction = [
            'label' => 'View All Customers',
            'url' => route('customers.index'),
        ];
    } elseif (request()->is('project*')) {
        $title = 'Project Not Found';
        $heading = 'Project Not Found';
        $message = 'The project you requested does not exist or may have been deleted.';
        $contextAction = [
            'label' => 'View All Projects',
            'url' => route('projects.index'),
        ];
    } elseif (request()->is('notification*')) {
        $title = 'Notification Not Found';
        $heading = 'Notification Not Found';
        $message = 'The notification you are looking for is no longer available.';
        $contextAction = [
            'label' => 'View All Notifications',
            'url' => route('notifications.index'),
        ];
    } elseif (request()->is('document*')) {
        $title = 'Document Not Found';
        $heading = 'Document Not Found';
        $message = 'The requested document does not exist or has been removed.';
        $contextAction = [
            'label' => 'View All Documents',
            'url' => route('documents.index'),
        ];
    } elseif (request()->is('payment*')) {
        $title = 'Payment Not Found';
        $heading = 'Payment Record Not Found';
        $message = 'The payment record you requested does not exist or has been deleted.';
        $contextAction = [
            'label' => 'View All Payments',
            'url' => route('payments.index'),
        ];
    } else {
        $title = 'Page Not Found';
        $heading = 'Page Not Found';
        $message = 'The page you requested could not be found or the link may be broken.';
        $contextAction = null;
    }
@endphp

@extends('errors.layout', [
    'code' => '404',
    'title' => $title,
    'heading' => $heading,
    'message' => $message,
    'badgeClass' => 'bg-amber-50 text-amber-800 border border-amber-200',
])

@section('icon')
    <svg class="w-16 h-16 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        <line x1="11" y1="8" x2="11" y2="11"></line>
        <line x1="11" y1="14" x2="11.01" y2="14"></line>
    </svg>
@endsection

@section('actions')
    <a href="{{ route('dashboard') }}" class="btn btn-primary">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
        Return to Dashboard
    </a>
    @if($contextAction)
        <a href="{{ $contextAction['url'] }}" class="btn btn-ghost">
            {{ $contextAction['label'] }}
        </a>
    @else
        <a href="{{ route('projects.index') }}" class="btn btn-ghost">
            View All Projects
        </a>
        <a href="{{ route('customers.index') }}" class="btn btn-ghost">
            View All Customers
        </a>
    @endif
    <button onclick="window.history.back()" type="button" class="btn btn-ghost">
        Go Back
    </button>
@endsection
