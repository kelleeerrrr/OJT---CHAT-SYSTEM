<!-- Sidebar -->
<aside class="sidebar text-white">
    <nav class="p-4">
        <!-- Messages -->
        <a href="{{ route('chat.index') }}" 
           class="flex items-center gap-3 px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('chat.*') ? 'bg-white text-primary' : 'hover:bg-gray-700' }} transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
            </svg>
            <span>Messages</span>
        </a>

        <!-- Chat Requests (Admin only) -->
        @if(auth()->user()->isAdmin())
            @php
                $pendingCount = \App\Models\Conversation::pending()
                    ->where('partner_id', auth()->id())
                    ->count();
            @endphp
            <a href="{{ route('conversations.index') }}" 
               class="flex items-center gap-3 px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('conversations.index') ? 'bg-white text-primary' : 'hover:bg-gray-700' }} transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <span>Chat Requests</span>
                @if($pendingCount > 0)
                    <span class="bg-accent text-white text-xs px-2 py-0.5 rounded-full ml-auto">
                        {{ $pendingCount }}
                    </span>
                @endif
            </a>
        @endif

        <!-- Admin Section (Super Admin only) -->
        @if(auth()->user()->isSuperAdmin())
            <div class="mt-6 mb-2">
                <h3 class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Admin</h3>
            </div>


            <a href="{{ route('admin.chat.index') }}" 
               class="flex items-center gap-3 px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('admin.chat.index') ? 'bg-white text-primary' : 'hover:bg-gray-700' }} transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span>Chat Management</span>
            </a>
        @endif

        <!-- Profile Section -->
        <div class="mt-6 mb-2">
            <h3 class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Account</h3>
        </div>

        <a href="{{ route('profile.edit') }}" 
           class="flex items-center gap-3 px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('profile.*') ? 'bg-white text-primary' : 'hover:bg-gray-700' }} transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
            <span>Profile</span>
        </a>
    </nav>
</aside>
