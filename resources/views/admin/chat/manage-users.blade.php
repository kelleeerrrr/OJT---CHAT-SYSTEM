@extends('layouts.app')

@section('title', 'Manage Users')

@section('content')
<div class="max-w-6xl mx-auto py-8 px-4">
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">Manage Users</h1>
            <p class="text-sm text-gray-500 mt-1">Restrict users from chatting with admins and regular users. Restricted users can still contact the Super Admin.</p>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-xl">
            {{ session('status') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">User</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Role</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr class="border-b border-gray-100">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-semibold text-xs">
                                {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                            </div>
                            <span class="font-medium text-gray-800">{{ $user->name }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-medium
                            {{ $user->role === 'admin' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @if($user->is_chat_denied)
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                Restricted
                            </span>
                        @else
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                Active
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($user->is_chat_denied)
                            <form method="POST" action="{{ route('admin.chat.restore', $user) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-green-600 hover:text-green-700 text-xs font-medium">
                                    Restore Access
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.chat.deny', $user) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-red-600 hover:text-red-700 text-xs font-medium">
                                    Restrict Access
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection