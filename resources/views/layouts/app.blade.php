<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

@include('layouts.head')

<body class="font-sans antialiased">

    <x-loading />

    <!-- Header -->
    @include('layouts.header')

    <!-- Sidebar -->
    @include('layouts.sidebar')

    <!-- Main Content -->
    <main id="main" class="main animate__animated animate__fadeIn animate__fast">
        @hasSection('header')
            <header class="bg-white shadow-sm mb-6">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    @yield('header')
                </div>
            </header>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    @include('layouts.footer')

    @include('layouts.scripts')

</body>

</html>
