@extends('layouts.app')

@section('title', 'Admin - Chat Management')

@section('content')
<div style="max-width:860px; margin:0 auto; padding:2rem 1.5rem; font-family:inherit;">

    {{-- Page header --}}
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem;">
        <h1 style="font-size:17px; font-weight:500; color:#111;">Chat management</h1>
    </div>

    {{-- Global toggle (superadmin only) --}}
    @if(auth()->user()->isSuperAdmin())
    <div id="globalBar" style="background:#fff; border:0.5px solid #e5e5e5; border-radius:12px; padding:14px 18px; margin-bottom:1.5rem; display:flex; align-items:center; justify-content:space-between;">
        <div>
            <p style="font-size:14px; font-weight:500; color:#111; margin:0;">Global chat status</p>
            <p style="font-size:12px; color:#999; margin:3px 0 0;">Enable or disable chat for all users</p>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <span id="globalStatusPill" style="font-size:12px; font-weight:500; padding:3px 10px; border-radius:99px;">—</span>
            <button id="globalToggleBtn" onclick="toggleChat()" style="font-size:12px; font-weight:500; padding:6px 14px; border-radius:8px; border:0.5px solid #ccc; cursor:pointer; background:#f5f5f5; color:#333;">
                Loading…
            </button>
        </div>
    </div>
    @endif

    {{-- ── Admins section ── --}}
    @php
        $admins = \App\Models\User::whereIn('role', ['admin', 'superadmin'])
            ->where('id', '!=', auth()->id())
            ->withCount(['messages as unread_count' => fn($q) => $q->where('is_read', false)])
            ->get();
    @endphp

    @if($admins->isNotEmpty())
    <p style="font-size:11px; font-weight:500; color:#999; text-transform:uppercase; letter-spacing:.06em; margin:0 0 8px 2px;">Admins</p>
    <div style="background:#fff; border:0.5px solid #e5e5e5; border-radius:12px; overflow:hidden; margin-bottom:1.25rem;">
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead>
                <tr style="background:#fafafa; border-bottom:0.5px solid #e5e5e5;">
                    <th style="padding:10px 14px; text-align:left; font-size:11px; font-weight:500; color:#999; width:32%;">User</th>
                    <th style="padding:10px 14px; text-align:left; font-size:11px; font-weight:500; color:#999; width:14%;">Role</th>
                    <th style="padding:10px 14px; text-align:left; font-size:11px; font-weight:500; color:#999; width:14%;">Status</th>
                    <th style="padding:10px 14px; text-align:left; font-size:11px; font-weight:500; color:#999; width:20%;">Messages</th>
                    <th style="padding:10px 14px; text-align:left; font-size:11px; font-weight:500; color:#999;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($admins as $user)
                @php
                    $initials = mb_strtoupper(mb_substr($user->name, 0, 1));
                    $avatarBg   = $user->role === 'superadmin' ? '#EEEDFE' : '#E6F1FB';
                    $avatarColor= $user->role === 'superadmin' ? '#3C3489' : '#0C447C';
                    $roleBg     = $user->role === 'superadmin' ? '#EEEDFE' : '#E6F1FB';
                    $roleColor  = $user->role === 'superadmin' ? '#3C3489' : '#0C447C';
                    $unread     = $user->unread_count ?? 0;
                @endphp
                <tr style="border-bottom:0.5px solid #f0f0f0; {{ $loop->last ? 'border-bottom:none;' : '' }}">
                    {{-- Avatar + name --}}
                    <td style="padding:11px 14px;">
                        <div style="display:flex; align-items:center; gap:9px;">
                            <div style="width:30px; height:30px; border-radius:50%; background:{{ $avatarBg }}; color:{{ $avatarColor }}; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:500; flex-shrink:0;">
                                {{ $initials }}
                            </div>
                            <span style="font-weight:500; color:#111;">{{ $user->name }}</span>
                        </div>
                    </td>
                    {{-- Role badge --}}
                    <td style="padding:11px 14px;">
                        <span style="font-size:11px; font-weight:500; padding:2px 9px; border-radius:99px; background:{{ $roleBg }}; color:{{ $roleColor }};">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    {{-- Status --}}
                    <td style="padding:11px 14px;">
                        @if($user->is_chat_denied)
                            <span style="font-size:11px; font-weight:500; padding:2px 9px; border-radius:99px; background:#fef2f2; color:#b91c1c; display:inline-flex; align-items:center; gap:5px;">
                                <span style="width:6px; height:6px; border-radius:50%; background:#ef4444; display:inline-block;"></span>Denied
                            </span>
                        @else
                            <span style="font-size:11px; font-weight:500; padding:2px 9px; border-radius:99px; background:#f0fdf4; color:#15803d; display:inline-flex; align-items:center; gap:5px;">
                                <span style="width:6px; height:6px; border-radius:50%; background:#22c55e; display:inline-block;"></span>Active
                            </span>
                        @endif
                    </td>
                    {{-- Messages --}}
                    <td style="padding:11px 14px;">
                        @if($unread > 0)
                            <span style="font-size:11px; font-weight:500; padding:2px 9px; border-radius:99px; background:#eff6ff; color:#1d4ed8;">
                                {{ $unread }} unread
                            </span>
                        @else
                            <span style="font-size:12px; color:#aaa;">All read</span>
                        @endif
                    </td>
                    {{-- Actions --}}
                    <td style="padding:11px 14px;">
                        <div style="display:flex; align-items:center; gap:6px;">
                            @if($user->is_chat_denied)
                                <form method="POST" action="{{ route('admin.chat.restore', $user) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" style="font-size:12px; font-weight:500; padding:4px 10px; border-radius:8px; border:0.5px solid #bbf7d0; color:#15803d; background:transparent; cursor:pointer;">
                                        Restore access
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.chat.deny', $user) }}" style="display:inline;" onsubmit="return confirm('Deny chat access for {{ $user->name }}?');">
                                    @csrf
                                    <button type="submit" style="font-size:12px; font-weight:500; padding:4px 10px; border-radius:8px; border:0.5px solid #fecaca; color:#b91c1c; background:transparent; cursor:pointer;">
                                        Deny access
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('admin.chat.view', $user) }}" style="font-size:12px; font-weight:500; padding:4px 10px; border-radius:8px; border:0.5px solid #bfdbfe; color:#1d4ed8; background:transparent; text-decoration:none;">
                                View chats
                            </a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- ── Users section ── --}}
    @php
        $users = \App\Models\User::where('role', 'user')
            ->where('id', '!=', auth()->id())
            ->withCount(['messages as unread_count' => fn($q) => $q->where('is_read', false)])
            ->get();
    @endphp

    <p style="font-size:11px; font-weight:500; color:#999; text-transform:uppercase; letter-spacing:.06em; margin:0 0 8px 2px;">Users</p>
    <div style="background:#fff; border:0.5px solid #e5e5e5; border-radius:12px; overflow:hidden;">
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead>
                <tr style="background:#fafafa; border-bottom:0.5px solid #e5e5e5;">
                    <th style="padding:10px 14px; text-align:left; font-size:11px; font-weight:500; color:#999; width:32%;">User</th>
                    <th style="padding:10px 14px; text-align:left; font-size:11px; font-weight:500; color:#999; width:14%;">Role</th>
                    <th style="padding:10px 14px; text-align:left; font-size:11px; font-weight:500; color:#999; width:14%;">Status</th>
                    <th style="padding:10px 14px; text-align:left; font-size:11px; font-weight:500; color:#999; width:20%;">Messages</th>
                    <th style="padding:10px 14px; text-align:left; font-size:11px; font-weight:500; color:#999;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                @php
                    $initials = mb_strtoupper(mb_substr($user->name, 0, 1));
                    $unread   = $user->unread_count ?? 0;
                @endphp
                <tr style="border-bottom:0.5px solid #f0f0f0; {{ $loop->last ? 'border-bottom:none;' : '' }}">
                    {{-- Avatar + name --}}
                    <td style="padding:11px 14px;">
                        <div style="display:flex; align-items:center; gap:9px;">
                            <div style="width:30px; height:30px; border-radius:50%; background:#f5f5f5; color:#555; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:500; flex-shrink:0; border:0.5px solid #e5e5e5;">
                                {{ $initials }}
                            </div>
                            <span style="font-weight:500; color:#111;">{{ $user->name }}</span>
                        </div>
                    </td>
                    {{-- Role --}}
                    <td style="padding:11px 14px;">
                        <span style="font-size:11px; font-weight:500; padding:2px 9px; border-radius:99px; background:#f5f5f5; color:#555; border:0.5px solid #e5e5e5;">
                            User
                        </span>
                    </td>
                    {{-- Status --}}
                    <td style="padding:11px 14px;">
                        @if($user->is_chat_denied)
                            <span style="font-size:11px; font-weight:500; padding:2px 9px; border-radius:99px; background:#fef2f2; color:#b91c1c; display:inline-flex; align-items:center; gap:5px;">
                                <span style="width:6px; height:6px; border-radius:50%; background:#ef4444; display:inline-block;"></span>Denied
                            </span>
                        @else
                            <span style="font-size:11px; font-weight:500; padding:2px 9px; border-radius:99px; background:#f0fdf4; color:#15803d; display:inline-flex; align-items:center; gap:5px;">
                                <span style="width:6px; height:6px; border-radius:50%; background:#22c55e; display:inline-block;"></span>Active
                            </span>
                        @endif
                    </td>
                    {{-- Messages --}}
                    <td style="padding:11px 14px;">
                        @if($unread > 0)
                            <span style="font-size:11px; font-weight:500; padding:2px 9px; border-radius:99px; background:#eff6ff; color:#1d4ed8;">
                                {{ $unread }} unread
                            </span>
                        @else
                            <span style="font-size:12px; color:#aaa;">All read</span>
                        @endif
                    </td>
                    {{-- Actions --}}
                    <td style="padding:11px 14px;">
                        <div style="display:flex; align-items:center; gap:6px;">
                            @if($user->is_chat_denied)
                                <form method="POST" action="{{ route('admin.chat.restore', $user) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" style="font-size:12px; font-weight:500; padding:4px 10px; border-radius:8px; border:0.5px solid #bbf7d0; color:#15803d; background:transparent; cursor:pointer;">
                                        Restore access
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.chat.deny', $user) }}" style="display:inline;" onsubmit="return confirm('Deny chat access for {{ $user->name }}?');">
                                    @csrf
                                    <button type="submit" style="font-size:12px; font-weight:500; padding:4px 10px; border-radius:8px; border:0.5px solid #fecaca; color:#b91c1c; background:transparent; cursor:pointer;">
                                        Deny access
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('admin.chat.view', $user) }}" style="font-size:12px; font-weight:500; padding:4px 10px; border-radius:8px; border:0.5px solid #bfdbfe; color:#1d4ed8; background:transparent; text-decoration:none;">
                                View chats
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="padding:24px 14px; text-align:center; color:#aaa; font-size:13px;">No users found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

@if(auth()->user()->isSuperAdmin())
<script>
let chatEnabled = false;

async function loadChatStatus() {
    try {
        const res = await fetch('{{ route('settings.status') }}');
        const data = await res.json();
        chatEnabled = data.chat_enabled;
        updateChatUI();
    } catch (e) {
        console.error('Failed to load chat status:', e);
    }
}

async function toggleChat() {
    try {
        const res = await fetch('{{ route('settings.toggle-chat') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        const data = await res.json();
        chatEnabled = data.chat_enabled;
        updateChatUI();
    } catch (e) {
        alert('Failed to toggle chat. Please try again.');
    }
}

function updateChatUI() {
    const pill = document.getElementById('globalStatusPill');
    const btn  = document.getElementById('globalToggleBtn');
    if (chatEnabled) {
        pill.textContent = 'Enabled';
        pill.style.cssText = 'font-size:12px; font-weight:500; padding:3px 10px; border-radius:99px; background:#f0fdf4; color:#15803d;';
        btn.textContent = 'Disable chat';
        btn.style.cssText = 'font-size:12px; font-weight:500; padding:6px 14px; border-radius:8px; border:0.5px solid #fecaca; cursor:pointer; background:transparent; color:#b91c1c;';
    } else {
        pill.textContent = 'Disabled';
        pill.style.cssText = 'font-size:12px; font-weight:500; padding:3px 10px; border-radius:99px; background:#fef2f2; color:#b91c1c;';
        btn.textContent = 'Enable chat';
        btn.style.cssText = 'font-size:12px; font-weight:500; padding:6px 14px; border-radius:8px; border:0.5px solid #bbf7d0; cursor:pointer; background:transparent; color:#15803d;';
    }
}

loadChatStatus();
</script>
@endif
@endsection