@extends('errors.layout', [
    'code' => '419',
    'title' => 'Session Expired',
    'heading' => 'Page Session Expired',
    'message' => 'Your security token or session has expired due to inactivity. Please reload the page or sign in again to continue.',
    'badgeClass' => 'bg-blue-50 text-blue-800 border border-blue-200',
])

@section('icon')
    <svg class="w-16 h-16 text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"></circle>
        <polyline points="12 6 12 12 16 14"></polyline>
    </svg>
@endsection

@section('actions')
    <button onclick="window.location.reload()" type="button" class="btn btn-primary">
        Reload Page
    </button>
    <a href="{{ route('login') }}" class="btn btn-ghost">
        Sign In Again
    </a>
@endsection
