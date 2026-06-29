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

@if(auth()->user()->isChatDenied())
    <div class="mb-6 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-xl">
        Your chat access has been suspended by an administrator.
    </div>
@endif

<div class="bg-white shadow rounded-2xl overflow-hidden">

    <div class="px-6 py-4 border-b bg-gray-50">
        <h2 class="font-semibold text-gray-700">
            Registered Users
        </h2>
    </div>

    @forelse($users as $user)

        <a href="{{ route('chat.show', $user) }}"
           class="flex items-center justify-between px-6 py-4 border-b hover:bg-gray-50 transition">

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
                <span class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg">
                    Chat
                </span>
            </div>

        </a>

    @empty

        <div class="p-10 text-center text-gray-500">
            No other registered users found.
        </div>

    @endforelse

</div>

</div>
@endsection
