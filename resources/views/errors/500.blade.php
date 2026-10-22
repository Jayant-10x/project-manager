@extends('errors.layout', [
    'code' => '500',
    'title' => 'Server Error',
    'heading' => 'Unexpected Server Error',
    'message' => 'An unexpected server error occurred while processing your request. Please try again or return to your dashboard.',
    'badgeClass' => 'bg-red-50 text-red-800 border border-red-200',
])

@section('icon')
    <svg class="w-16 h-16 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
        <line x1="12" y1="9" x2="12" y2="13"></line>
        <line x1="12" y1="17" x2="12.01" y2="17"></line>
    </svg>
@endsection

@section('actions')
    <button onclick="window.location.reload()" type="button" class="btn btn-primary">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
        Reload Page
    </button>
    <a href="{{ route('dashboard') }}" class="btn btn-ghost">
        Return to Dashboard
    </a>
@endsection
