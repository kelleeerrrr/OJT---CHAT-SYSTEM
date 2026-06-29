@extends('layouts.app')

@section('title', 'Chat with ' . $partner->name)

@section('content')
<div class="flex flex-col h-screen bg-gray-100"
     x-data='chatApp(
        {{ auth()->id() }},
        {{ $partner->id }},
        @json($messages)
     )'
     x-init="init()">

    <!-- Header -->
    <div class="bg-white border-b px-4 py-3 flex items-center shadow-sm">
        <a href="{{ route('chat.index') }}"
           class="mr-3 text-gray-600 hover:text-gray-900">
            ← Back
        </a>

        <div class="flex-1">
            <h1 class="font-semibold text-gray-800">
                {{ $partner->name }}
            </h1>
            <p class="text-xs text-gray-500">
                Private Conversation
            </p>
        </div>
    </div>

    <!-- Messages -->
    <div class="flex-1 overflow-y-auto p-4 space-y-3"
         x-ref="messageList">

        <template x-if="messages.length === 0">
            <div class="text-center text-gray-500 mt-10">
                No messages yet. Start the conversation.
            </div>
        </template>

        <template x-for="message in messages"
                  :key="message.temp_id ?? message.id">

            <div class="flex"
                 :class="message.sender_id == currentUserId
                    ? 'justify-end'
                    : 'justify-start'">

                <div class="max-w-[75%] px-4 py-2 rounded-2xl shadow-sm"
                     :class="message.sender_id == currentUserId
                        ? 'bg-indigo-600 text-white rounded-br-md'
                        : 'bg-white text-gray-800 rounded-bl-md'">

                    <div x-text="message.body"
                         class="whitespace-pre-wrap break-words"></div>

                    <div class="text-[10px] mt-1 opacity-70 text-right"
                         x-text="formatTime(message.created_at)">
                    </div>

                </div>
            </div>

        </template>

    </div>

    <!-- Input -->
    <div class="bg-white border-t p-4">

        <form @submit.prevent="sendMessage()"
              class="flex gap-2">

            <textarea x-model="draft"
                      rows="1"
                      class="flex-1 border rounded-xl px-4 py-2"
                      placeholder="Type a message..."
                      @keydown.enter.prevent="sendMessage()"
                      :disabled="sending">
            </textarea>

            <button type="submit"
                    :disabled="sending || !draft.trim()"
                    class="bg-indigo-600 text-white px-5 py-2 rounded-xl">

                Send
            </button>

        </form>

    </div>
</div>

<script>
// CACHE BUST: v2.3 - Force new build
function chatApp(currentUserId, partnerId, initialMessages = []) {
    // Version 2.3 - Fixed Alpine sending property and cache issues

    return {

        currentUserId,
        partnerId,

        messages: Array.isArray(initialMessages) ? initialMessages : [],
        draft: '',
        sending: false,
        sendingCount: 0,
        _initialized: false,
        _version: '2.4', // Force cache bust
        _cacheBust: Date.now(), // Force new hash
        _debugMode: true, // Force new hash
        _forceNewHash: 'CACHE_BUST_V2_4_' + Math.random(), // Force new hash

        // =====================
        // INIT
        // =====================
        init() {
            if (this._initialized) return;
            this._initialized = true;

            console.log('[CHAT v2.3] Initializing for user', currentUserId, 'with partner', partnerId);
            console.log('[CHAT v2.3] Echo available:', typeof window.Echo !== 'undefined');

            if (typeof window.Echo === 'undefined') {
                console.error('[CHAT v2.3] Echo is not available! Real-time will not work.');
                return;
            }

            const ids = [currentUserId, partnerId].sort((a,b) => a - b);
            const channelName = `chat.${ids[0]}.${ids[1]}`;

            console.log('[CHAT v2.3] Subscribing to channel:', channelName);

            window.Echo.private(channelName)
                .subscribed(() => {
                    console.log('[CHAT v2.3] Successfully subscribed to channel:', channelName);
                })
                .error((error) => {
                    console.error('[CHAT v2.3] Channel subscription error:', error);
                })
                .listen('.message.sent', (event) => {
                    console.log('[CHAT v2.3] Received message event:', event);

                    const message = event.message || event;

                    // ✅ FIX 1: Only process messages from OTHER users
                    // For sender's own messages, API response is authoritative
                    if (message.sender_id === currentUserId) {
                        // This is our own message from Echo - ignore it
                        // The API response handler will update it
                        console.log('[CHAT v2.3] Ignoring own message from Echo, API response will handle it');
                        return;
                    }

                    // ✅ FIX 2: Prevent duplicates by real ID
                    const exists = this.messages.some(m =>
                        String(m.id) === String(message.id)
                    );

                    if (exists) {
                        console.log('[CHAT v2.3] Message already exists, skipping duplicate');
                        return;
                    }

                    // ✅ FIX 3: New message from other user - add it
                    console.log('[CHAT v2.3] Adding new message from other user:', message);
                    this.messages.push(message);

                    this.sortMessages();
                    this.scrollToBottom();
                });
        },

        // =====================
        // SEND MESSAGE
        // =====================
        async sendMessage() {
            console.log('[CHAT v2.3] sendMessage called via button or enter');

            const body = this.draft.trim();
            if (!body || this.sending) {
                console.log('[CHAT v2.3] sendMessage blocked - body:', body, 'sending:', this.sending);
                return;
            }

            this.sending = true;
            console.log('[CHAT v2.3] Sending message...');

            const tempId =
                'temp-' + Date.now() + '-' + Math.random().toString(16).slice(2);

            const tempMessage = {
                id: tempId,
                temp_id: tempId,
                sender_id: currentUserId,
                receiver_id: partnerId,
                body,
                created_at: new Date().toISOString()
            };

            this.messages.push(tempMessage);

            this.sortMessages();
            this.scrollToBottom();

            this.draft = '';

            try {

                const res = await fetch('{{ route('chat.send') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        receiver_id: partnerId,
                        body,
                        temp_id: tempId
                    })
                });

                const json = await res.json();

                if (!res.ok) {
                    console.error('[CHAT v2.3] Send failed:', json);
                    this.messages = this.messages.filter(m => m.temp_id !== tempId);
                    this.draft = body;
                    this.sending = false;
                    return;
                }

                console.log('[CHAT v2.3] Send successful, replacing temp message');
                // ✅ Replace temp message with real message from API response
                const msgIndex = this.messages.findIndex(m => m.temp_id === tempId);

                if (msgIndex !== -1) {
                    this.messages[msgIndex] = {
                        ...this.messages[msgIndex],
                        id: json.message.id,
                        body: json.message.body,
                        created_at: json.message.created_at,
                        temp_id: null
                    };
                }

                this.sortMessages();
                this.scrollToBottom();

            } catch (e) {
                console.error('[CHAT v2.3] Send error:', e);
                this.messages = this.messages.filter(m => m.temp_id !== tempId);
                this.draft = body;
            }

            this.sending = false;
        },

        // =====================
        // HELPERS
        // =====================
        sortMessages() {
            // ✅ FIX: Sort by created_at first, then by id as fallback
            // This ensures correct ordering even when timestamps are identical
            this.messages.sort((a, b) => {
                const dateA = new Date(a.created_at).getTime();
                const dateB = new Date(b.created_at).getTime();

                if (dateA !== dateB) {
                    return dateA - dateB;
                }

                // Fallback to id comparison (handles same timestamp)
                // For temp messages, use string comparison
                const idA = String(a.id);
                const idB = String(b.id);

                // If both are real IDs (numeric), compare as numbers
                if (!idA.startsWith('temp-') && !idB.startsWith('temp-')) {
                    return parseInt(idA) - parseInt(idB);
                }

                // Otherwise compare as strings
                return idA.localeCompare(idB);
            });
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const el = this.$refs.messageList;
                if (el) el.scrollTop = el.scrollHeight;
            });
        },

        formatTime(time) {
            if (!time) return '';
            return new Date(time).toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    };
}
</script>
@endsection