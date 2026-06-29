@extends('layouts.app')

@section('title', 'Admin – Chat Management')

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4"
     x-data="adminChat()">

    <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-6">Chat Management</h1>

    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">User</th>
                    <th class="px-4 py-3 text-left">Role</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Denied by</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach($users as $user)
                <tr x-data="{ denied: {{ $user->is_chat_denied ? 'true' : 'false' }} }">
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-800 dark:text-gray-100">{{ $user->name }}</p>
                        <p class="text-gray-400 text-xs">{{ $user->email }}</p>
                    </td>
                    <td class="px-4 py-3 capitalize text-gray-600 dark:text-gray-300">{{ $user->role }}</td>
                    <td class="px-4 py-3">
                        <span x-show="!denied"
                              class="inline-block px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-xs">
                            Active
                        </span>
                        <span x-show="denied"
                              class="inline-block px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-xs">
                            Denied
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">
                        @if($user->is_chat_denied && $user->chatDeniedBy)
                            {{ $user->chatDeniedBy->name ?? '—' }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right space-x-2">
                        <button x-show="!denied"
                                @click="denyUser({{ $user->id }}, $el.closest('tr')); denied = true"
                                class="text-xs px-3 py-1 rounded-lg border border-red-300 text-red-600 hover:bg-red-50 transition">
                            Deny
                        </button>
                        <button x-show="denied"
                                @click="restoreUser({{ $user->id }}, $el.closest('tr')); denied = false"
                                class="text-xs px-3 py-1 rounded-lg border border-green-300 text-green-600 hover:bg-green-50 transition">
                            Restore
                        </button>
                        <a href="{{ route('admin.chat.deny-log', $user) }}"
                           class="text-xs px-3 py-1 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 transition">
                            Log
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $users->links() }}

    {{-- Toast notification --}}
    <div x-show="toast"
         x-transition
         x-cloak
         class="fixed bottom-6 right-6 bg-gray-800 text-white text-sm px-4 py-2 rounded-xl shadow-lg">
        <span x-text="toast"></span>
    </div>
</div>
@endsection

@push('scripts')
<script>
function adminChat() {
    return {
        toast: '',

        async denyUser(userId) {
            const reason = prompt('Reason for denial (optional):') ?? '';
            const res = await fetch(`/admin/chat/${userId}/deny`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ reason }),
            });
            const json = await res.json();
            this.showToast(json.message || json.error);
        },

        async restoreUser(userId) {
            const res = await fetch(`/admin/chat/${userId}/restore`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            });
            const json = await res.json();
            this.showToast(json.message || json.error);
        },

        showToast(msg) {
            this.toast = msg;
            setTimeout(() => { this.toast = ''; }, 3500);
        },
    };
}
</script>
@endpush
