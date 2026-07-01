<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

@include('layouts.head')

<body class="font-sans text-gray-900 antialiased">
    <div class="auth-container">
        <div class="auth-wrapper">
            <div class="auth-card animate__animated animate__fadeInUp animate__fast">
                <!-- Logo -->
                <div class="auth-logo text-center mb-6">
                    <a href="{{ route('welcome') }}">
                        <img src="{{ asset('assets/img/bsu-neu-logo.png') }}" alt="Logo" class="h-16 w-auto mx-auto">
                    </a>
                </div>

                <!-- Card Content -->
                <div class="auth-card-body">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>

    @include('layouts.scripts')
</body>
</html>
