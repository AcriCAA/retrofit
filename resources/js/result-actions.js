import Alpine from 'alpinejs';

Alpine.data('resultActions', (resultId, initialStatus, resultMeta = {}) => ({
    status: initialStatus || 'new',
    isUpdating: false,
    resultMeta: resultMeta,

    init() {
        const handler = (e) => {
            if (e.detail.resultId === resultId) {
                this.status = 'dismissed';
            }
        };
        window.addEventListener('result-bulk-dismissed', handler);
        this.$cleanup(() => window.removeEventListener('result-bulk-dismissed', handler));
    },

    openDismissalChat() {
        this.$dispatch('open-dismissal-modal', {
            resultId: resultId,
            ...this.resultMeta,
        });
        // Mark dismissed locally immediately so the card hides
        this.status = 'dismissed';
    },

    async updateStatus(newStatus) {
        if (this.isUpdating) return;
        this.isUpdating = true;

        try {
            const response = await fetch(`/api/results/${resultId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ status: newStatus })
            });

            const data = await response.json();
            if (data.success) {
                this.status = data.status;
            }
        } catch (e) {
            console.error('Failed to update result status:', e);
        } finally {
            this.isUpdating = false;
        }
    }
}));
