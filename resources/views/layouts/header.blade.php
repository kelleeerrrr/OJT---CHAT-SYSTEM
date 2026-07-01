<!-- Header -->
<header class="header flex items-center justify-between px-4 sm:px-6">
    <!-- Logo and Sidebar Toggle -->
    <div class="flex items-center gap-4">
        <button class="sidebar-toggle text-white hover:text-gray-200 transition" aria-label="Toggle sidebar">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
            <img src="{{ asset('assets/img/bsu-neu-logo.png') }}" alt="Logo" class="h-8 w-auto">
        </a>
    </div>

        <!-- Profile Dropdown -->
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" 
                    class="flex items-center gap-2 text-white hover:text-gray-200 transition">
                <span class="hidden sm:block">{{ Auth::user()->name }}</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <!-- Dropdown Menu -->
            <div x-show="open" 
                 @click.away="open = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50"
                 style="display: none;">
                
                <a href="{{ route('profile.edit') }}" 
                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    Profile
                </a>

                <a href="{{ route('chat.index') }}" 
                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    Messages
                </a>

                <!-- Dark Mode Toggle -->
                <button @click="document.body.classList.toggle('dark'); localStorage.setItem('darkMode', document.body.classList.contains('dark'))"
                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>
                    Dark Mode
                </button>

                <hr class="my-1">

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" 
                            class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        Log Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
