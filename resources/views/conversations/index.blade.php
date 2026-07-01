@extends('layouts.app')

@section('title', 'Chat Requests')

@section('content')
<div class="pagetitle">
    <h1>{{ __('Chat Requests') }}</h1>
</div>

<section class="section">
    <div class="card">
        <div class="card-body">
            @if($conversations->isEmpty())
                <div class="text-center py-8">
                    <p class="text-gray-500">{{ __('No pending chat requests.') }}</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{{ __('Sender') }}</th>
                                <th>{{ __('Receiver') }}</th>
                                <th>{{ __('First Message') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($conversations as $conversation)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="avatar avatar-primary">
                                            {{ mb_strtoupper(mb_substr($conversation['user']->name, 0, 1)) }}
                                        </div>
                                        <span class="font-medium">{{ $conversation['user']->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="avatar avatar-success">
                                            {{ mb_strtoupper(mb_substr($conversation['partner']->name, 0, 1)) }}
                                        </div>
                                        <span class="font-medium">{{ $conversation['partner']->name }}</span>
                                    </div>
                                </td>
                                <td class="max-w-xs truncate">
                                    {{ $conversation['first_message'] ?? __('No message') }}
                                </td>
                                <td>
                                    {{ \Carbon\Carbon::parse($conversation['created_at'])->format('M d, Y g:i A') }}
                                </td>
                                <td>
                                    <div class="flex gap-2">
                                        <button 
                                            onclick="updateConversation({{ $conversation['id'] }}, 'accept')"
                                            class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded text-xs font-medium transition-colors">
                                            {{ __('Accept') }}
                                        </button>
                                        <button 
                                            onclick="updateConversation({{ $conversation['id'] }}, 'reject')"
                                            class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-medium transition-colors">
                                            {{ __('Reject') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</section>

<script>
function updateConversation(conversationId, action) {
    fetch(`/conversations/${conversationId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ action: action })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status) {
            if (action === 'accept' && data.redirect_url) {
                window.location.href = data.redirect_url;
            } else {
                location.reload();
            }
        } else if (data.error) {
            alert(data.error);
        }
    })
    .catch(error => {
        alert('An error occurred. Please try again.');
    });
}
</script>
@endsection
