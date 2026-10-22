@extends('errors.layout', [
    'code' => '503',
    'title' => 'Maintenance',
    'heading' => 'Temporarily Unavailable',
    'message' => 'The system is undergoing routine maintenance or updates. Please check back in a few minutes.',
    'badgeClass' => 'bg-amber-50 text-amber-800 border border-amber-200',
])

@section('icon')
    <svg class="w-16 h-16 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
    </svg>
@endsection

@section('actions')
    <button onclick="window.location.reload()" type="button" class="btn btn-primary">
        Check Again
    </button>
@endsection
