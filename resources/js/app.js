import './bootstrap'

import Alpine from 'alpinejs'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

// =======================
// Echo (Reverb setup)
// =======================
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST ?? '127.0.0.1',
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 9000,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
})

// =======================
// Format time
// =======================
window.formatTime = function (datetime) {
    if (!datetime) return ''

    return new Date(datetime).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit'
    })
}

// =======================
// Alpine Chat Component - v2.4 cache bust
// =======================
window.chatApp = (currentUserId, partnerId, initialMessages = []) => ({
    currentUserId,
    partnerId,

    messages: Array.isArray(initialMessages) ? initialMessages : [],
    draft: '',
    errorMsg: '',
    accessDenied: false,
    sending: false, // Added for compatibility with Blade view

    sendingCount: 0, // 🔥 IMPORTANT (replaces sending lock)

    init() {
        if (this._init) return
        this._init = true

        console.log('[CHAT v2.4] INIT - User:', this.currentUserId, 'Partner:', this.partnerId)

        this.scrollToBottom()

        const ids = [this.currentUserId, this.partnerId].sort((a, b) => a - b)
        const channelName = `chat.${ids[0]}.${ids[1]}`

        console.log('[CHAT v2.4] SUBSCRIBING TO', channelName)

        window.Echo.private(channelName)
            .subscribed(() => console.log('[CHAT v2.4] CHANNEL SUBSCRIBED:', channelName))
            .error(err => console.error('[CHAT v2.4] CHANNEL ERROR:', err))
            .listen('.message.sent', (event) => {
                console.log('[CHAT v2.4] RECEIVED MESSAGE EVENT:', event)

                const message = event.message ?? event

                // 🔥 Ignore own messages (sender's messages are handled by API response)
                if (message.sender_id === this.currentUserId) {
                    console.log('[CHAT v2.4] Ignoring own message from Echo')
                    return
                }

                // 🔥 prevent duplicates (VERY IMPORTANT)
                const exists = this.messages.some(m =>
                    String(m.id) === String(message.id)
                )

                if (exists) {
                    console.log('[CHAT v2.4] Message already exists, skipping')
                    return
                }

                console.log('[CHAT v2.4] Adding new message from other user:', message)
                this.messages.push(message)

                // 🔥 always keep correct order
                this.messages.sort((a, b) =>
                    new Date(a.created_at) - new Date(b.created_at)
                )

                this.$nextTick(() => this.scrollToBottom())
            })

        window.Echo.private(`user.${this.currentUserId}.notifications`)
            .listen('.chat.access.changed', (event) => {
                console.log('[CHAT v2.4] Chat access changed:', event)
                this.accessDenied = event.action === 'denied'
            })
    },

    // =======================
    // SEND MESSAGE (FIXED)
    // =======================
    async sendMessage() {

        const body = this.draft.trim()
        if (!body || this.accessDenied) return

        this.sendingCount++
        this.errorMsg = ''

        const tempId = 'temp-' + crypto.randomUUID()

        const tempMessage = {
            id: tempId,
            sender_id: this.currentUserId,
            receiver_id: this.partnerId,
            body,
            created_at: new Date().toISOString(),
            _temp: true
        }

        this.messages.push(tempMessage)

        this.draft = ''
        this.scrollToBottom()

        try {
            const res = await fetch('/chat/send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    receiver_id: this.partnerId,
                    body
                })
            })

            const json = await res.json()

            if (!res.ok) {
                this.messages = this.messages.filter(m => m.id !== tempId)
                this.draft = body
                this.errorMsg = json.error || 'Failed to send'
                return
            }

            const index = this.messages.findIndex(m => m.id === tempId)

            if (index !== -1) {
                this.messages[index] = json.message
            } else {
                this.messages.push(json.message)
            }

            // 🔥 enforce order after update
            this.messages.sort((a, b) =>
                new Date(a.created_at) - new Date(b.created_at)
            )

        } catch (e) {
            this.messages = this.messages.filter(m => m.id !== tempId)
            this.draft = body
            this.errorMsg = 'Network error'
        }

        this.sendingCount--
    },

    // =======================
    // SCROLL FIXED
    // =======================
    scrollToBottom() {
        this.$nextTick(() => {
            const el = this.$refs.messageList
            if (el) el.scrollTop = el.scrollHeight
        })
    }
})

// =======================
// START ALPINE
// =======================
window.Alpine = Alpine
Alpine.start()