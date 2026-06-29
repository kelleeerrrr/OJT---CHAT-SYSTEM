@extends('layouts.app')

@section('title', 'Admin - Chat Management')

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4">
    <h1 class="text-xl font-semibold text-gray-800 mb-6">Admin - Chat Management</h1>

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
                @foreach(\App\Models\User::where('id', '!=', auth()->id())->get() as $user)
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
                            {{ $user->role === 'superadmin' ? 'bg-purple-100 text-purple-700' : 
                               ($user->role === 'admin' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700') }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @if($user->is_chat_denied)
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                Denied
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
                            <form method="POST" action="{{ route('admin.chat.deny', $user) }}" class="inline" onsubmit="return confirm('Deny chat access for {{ $user->name }}?');">
                                @csrf
                                <button type="submit" class="text-red-600 hover:text-red-700 text-xs font-medium">
                                    Deny Access
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
