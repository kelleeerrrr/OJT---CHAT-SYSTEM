@extends('layouts.app')

@section('title', 'Messages')

@section('content')

<div class="max-w-5xl mx-auto py-8 px-4">

<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">
            Messages
        </h1>
        <p class="text-gray-500">
            Select a user to start a conversation
        </p>
    </div>

    <div class="text-right">
        <p class="font-semibold text-gray-800">
            {{ auth()->user()->name }}
        </p>
        <p class="text-sm text-gray-500">
            {{ auth()->user()->email }}
        </p>
    </div>
</div>

@if(session('error'))
    <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-xl">
        {{ session('error') }}
    </div>
@endif

<form method="GET" action="{{ route('chat.index') }}" class="mb-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="flex-1">
            <label for="user-search" class="block text-sm font-medium text-gray-700 mb-2">
                Search users
            </label>
            <input
                id="user-search"
                type="search"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search by name, email, or role"
                class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
        </div>

        <div class="flex gap-2">
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition">
                Search
            </button>

            @if(request()->filled('search'))
                <a href="{{ route('chat.index') }}" class="px-5 py-2.5 rounded-xl bg-gray-100 text-gray-700 font-medium hover:bg-gray-200 transition">
                    Clear
                </a>
            @endif
        </div>
    </div>
</form>

@if(auth()->user()->isChatDenied())
    <div class="mb-6 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-xl">
        Your account has been restricted. Contact the super admin to restore access.
    </div>
@endif

<div class="bg-white shadow rounded-2xl overflow-hidden">

    <div class="px-6 py-4 border-b bg-gray-50">
        <h2 class="font-semibold text-gray-700">
            Registered Users
        </h2>
    </div>

    @forelse($users as $user)
        @php
            $canChatWithUser = ! auth()->user()->isChatDenied() || $user->isSuperAdmin();
        @endphp

        @if($canChatWithUser)
        <a href="{{ route('chat.show', $user) }}"
           class="flex items-center justify-between px-6 py-4 border-b hover:bg-gray-50 transition">
        @else
        <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50 opacity-70 cursor-not-allowed">
        @endif

            <div class="flex items-center gap-4">

                <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>

                <div>
                    <p class="font-semibold text-gray-800">
                        {{ $user->name }}
                    </p>

                    <p class="text-sm text-gray-500">
                        {{ $user->email }}
                    </p>
                </div>

            </div>

            <div>
                @if($canChatWithUser)
                    <span class="px-4 py-2 {{ $user->isSuperAdmin() && auth()->user()->isChatDenied() ? 'bg-amber-600' : 'bg-indigo-600' }} text-white text-sm rounded-lg">
                        {{ $user->isSuperAdmin() && auth()->user()->isChatDenied() ? 'Contact Super Admin' : 'Chat' }}
                    </span>
                @else
                    <span class="px-4 py-2 bg-gray-300 text-gray-600 text-sm rounded-lg">
                        Restricted
                    </span>
                @endif
            </div>

        @if($canChatWithUser)
        </a>
        @else
        </div>
        @endif

    @empty

        <div class="p-10 text-center text-gray-500">
            {{ request()->filled('search') ? 'No users matched your search.' : 'No other registered users found.' }}
        </div>

    @endforelse

</div>

</div>

@endsection
