import Alpine from 'alpinejs';

Alpine.data('resultActions', (resultId, initialStatus) => ({
    status: initialStatus || 'new',
    isUpdating: false,

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
