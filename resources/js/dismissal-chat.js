import Alpine from 'alpinejs';

Alpine.data('dismissalChat', () => ({
    isOpen: false,
    result: {},
    conversationId: null,
    messages: [],
    message: '',
    isSending: false,
    isStarting: false,
    isRefined: false,
    refinementSummary: '',
    error: null,

    open(resultData) {
        this.result = resultData;
        this.isOpen = true;
        this.conversationId = null;
        this.messages = [];
        this.message = '';
        this.isRefined = false;
        this.refinementSummary = '';
        this.error = null;
        this.startChat();
    },

    close() {
        this.isOpen = false;
    },

    async startChat() {
        this.isStarting = true;
        try {
            const response = await fetch(`/api/results/${this.result.resultId}/dismiss-chat/start`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                },
            });

            const data = await response.json();
            if (data.success) {
                this.conversationId = data.conversation_id;
                this.messages.push(data.message);
                this.scrollToBottom();
            } else {
                this.error = data.error || 'Failed to start conversation.';
            }
        } catch (e) {
            this.error = 'Something went wrong. Please try again.';
        } finally {
            this.isStarting = false;
        }
    },

    async sendMessage() {
        if (this.isSending || !this.message.trim() || !this.conversationId) return;

        const userMessage = this.message.trim();
        this.message = '';
        this.error = null;
        this.isSending = true;

        this.messages.push({
            id: 'temp-' + Date.now(),
            role: 'user',
            content: userMessage,
            created_at: new Date().toISOString(),
        });
        this.scrollToBottom();

        try {
            const response = await fetch(`/api/dismiss-chat/${this.conversationId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                },
                body: JSON.stringify({ message: userMessage }),
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Failed to send message.');
            }

            this.messages = this.messages.filter(m => !String(m.id).startsWith('temp-'));
            this.messages.push(data.message);
            this.scrollToBottom();

            if (data.criteria_refined) {
                this.isRefined = true;
                this.refinementSummary = data.refinement_summary;
            }
        } catch (e) {
            this.error = e.message;
            this.messages = this.messages.filter(m => !String(m.id).startsWith('temp-'));
            this.message = userMessage;
        } finally {
            this.isSending = false;
        }
    },

    handleKeydown(event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            this.sendMessage();
        }
    },

    scrollToBottom() {
        this.$nextTick(() => {
            const container = this.$refs.messagesContainer;
            if (container) container.scrollTop = container.scrollHeight;
        });
    },

    getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    },

    formatMessage(content) {
        let formatted = content;
        formatted = formatted.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        formatted = formatted.replace(/\*([^*]+)\*/g, '<em>$1</em>');
        formatted = formatted.replace(/\n/g, '<br>');
        return formatted;
    },

    formatPrice(price) {
        if (!price) return '';
        return '$' + parseFloat(price).toFixed(2);
    },
}));
