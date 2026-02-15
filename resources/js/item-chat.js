import Alpine from 'alpinejs';

Alpine.data('itemChat', () => ({
    // State
    isLoading: false,
    isSending: false,
    message: '',
    conversationId: null,
    messages: [],
    error: null,
    sessionExpired: false,
    searchCreated: null,

    // Image upload
    imageFile: null,
    imagePreview: null,
    isDragging: false,

    // Lifecycle
    init() {
        this.$watch('imageFile', (file) => {
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => this.imagePreview = e.target.result;
                reader.readAsDataURL(file);
            }
        });
    },

    // Image handling
    handleDrop(event) {
        this.isDragging = false;
        const files = event.dataTransfer.files;
        if (files.length > 0 && files[0].type.startsWith('image/')) {
            this.imageFile = files[0];
        }
    },

    handleFileSelect(event) {
        const file = event.target.files[0];
        if (file && file.type.startsWith('image/')) {
            this.imageFile = file;
        }
    },

    removeImage() {
        this.imageFile = null;
        this.imagePreview = null;
    },

    // Chat methods
    async sendMessage() {
        if (this.isSending) return;

        // First message requires an image
        if (!this.conversationId && !this.imageFile) {
            this.error = 'Please upload a photo of the item you\'re looking for.';
            return;
        }

        const userMessage = this.message.trim() || (
            !this.conversationId
                ? 'I uploaded a photo of an item I\'m looking for. Can you help me identify it and set up a search?'
                : ''
        );

        if (!userMessage && this.conversationId) return;

        this.error = null;
        this.isSending = true;

        // Add user message to UI
        this.messages.push({
            id: 'temp-' + Date.now(),
            role: 'user',
            content: userMessage,
            created_at: new Date().toISOString()
        });
        this.message = '';
        this.scrollToBottom();

        try {
            let response;

            if (!this.conversationId) {
                // First message — multipart with image
                const formData = new FormData();
                formData.append('image', this.imageFile);
                if (userMessage !== 'I uploaded a photo of an item I\'m looking for. Can you help me identify it and set up a search?') {
                    formData.append('message', userMessage);
                }

                const categorySelect = document.getElementById('category_id');
                if (categorySelect?.value) {
                    formData.append('category_id', categorySelect.value);
                }

                response = await fetch('/api/chat/start', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.getCsrfToken()
                    },
                    body: formData
                });
            } else {
                // Follow-up message — JSON
                response = await fetch(`/api/chat/${this.conversationId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.getCsrfToken()
                    },
                    body: JSON.stringify({ message: userMessage })
                });
            }

            if (response.status === 419 || response.status === 401) {
                this.sessionExpired = true;
                throw new Error('Your session has expired. Please refresh the page and try again.');
            }

            let data;
            try {
                data = await response.json();
            } catch (parseError) {
                throw new Error('Unexpected server response. Please refresh the page and try again.');
            }

            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Failed to send message');
            }

            this.conversationId = data.conversation_id;
            this.messages.push(data.message);

            if (data.search_request) {
                this.searchCreated = data.search_request;
            }

            this.scrollToBottom();
        } catch (e) {
            this.error = e.message;
            this.messages = this.messages.filter(m => !String(m.id).startsWith('temp-'));
            this.message = userMessage;
        } finally {
            this.isSending = false;
        }
    },

    getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    },

    // UI helpers
    scrollToBottom() {
        this.$nextTick(() => {
            const container = this.$refs.messagesContainer;
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        });
    },

    handleKeydown(event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            this.sendMessage();
        }
    },

    formatMessage(content) {
        let formatted = content;
        formatted = formatted.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        formatted = formatted.replace(/\*([^*]+)\*/g, '<em>$1</em>');
        formatted = formatted.replace(/`([^`]+)`/g, '<code class="px-1 py-0.5 bg-gray-100 rounded text-sm font-mono">$1</code>');
        formatted = formatted.replace(/\n/g, '<br>');
        return formatted;
    },

    formatTime(isoString) {
        const date = new Date(isoString);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
}));
