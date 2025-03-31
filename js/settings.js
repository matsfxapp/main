document.addEventListener('DOMContentLoaded', function() {
    // Create a reusable modal function
    function createModal(options) {
        // Remove any existing modal
        const existingModal = document.getElementById('custom-modal');
        if (existingModal) {
            existingModal.remove();
        }

        // Create modal container
        const modal = document.createElement('div');
        modal.id = 'custom-modal';
        modal.className = 'custom-modal';
        modal.innerHTML = `
            <div class="custom-modal-content">
                <div class="custom-modal-header">
                    <h3>${options.title}</h3>
                    <button class="custom-modal-close">&times;</button>
                </div>
                <div class="custom-modal-body">
                    <p>${options.message}</p>
                    ${options.inputField || ''}
                </div>
                <div class="custom-modal-footer">
                    <button class="custom-modal-cancel">${options.cancelText || 'Cancel'}</button>
                    <button class="custom-modal-confirm">${options.confirmText || 'Confirm'}</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Close button functionality
        const closeBtn = modal.querySelector('.custom-modal-close');
        const cancelBtn = modal.querySelector('.custom-modal-cancel');
        
        const closeModal = () => {
            modal.classList.add('fade-out');
            setTimeout(() => modal.remove(), 300);
        };

        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);

        // Confirm button functionality
        const confirmBtn = modal.querySelector('.custom-modal-confirm');
        confirmBtn.addEventListener('click', () => {
            const inputField = modal.querySelector('input');
            const inputValue = inputField ? inputField.value : null;
            
            if (options.onConfirm) {
                const result = options.onConfirm(inputValue);
                if (result !== false) {
                    closeModal();
                }
            }
        });

        // Show modal with fade in
        requestAnimationFrame(() => {
            modal.classList.add('show');
        });

        return modal;
    }

    // Delete account functionality
    const deleteAccountBtn = document.getElementById('delete-account-btn');
    if (deleteAccountBtn) {
        deleteAccountBtn.addEventListener('click', function() {
            createModal({
                title: 'Delete Account',
                message: `Are you ABSOLUTELY sure you want to delete your account? 
                    This will permanently delete ALL your data, including:
                    • Uploaded songs
                    • Profile information
                    • Song likes and play history
                    • Account credentials

                    This action CANNOT be undone!`,
                confirmText: 'Delete Account',
                cancelText: 'Cancel',
                onConfirm: () => {
                    // Simple direct form submission - no username confirmation
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'handlers/delete_account.php';
                    document.body.appendChild(form);
                    form.submit();
                    return true;
                }
            });
        });
    }
    
    // Download data
    const downloadDataBtn = document.getElementById('download-data-btn');
    if (downloadDataBtn) {
        downloadDataBtn.addEventListener('click', function() {
            createModal({
                title: 'Download Personal Data',
                message: 'You are about to download a complete copy of your personal data. This includes your profile, uploaded songs, likes, and play history.',
                confirmText: 'Download JSON',
                cancelText: 'Cancel',
                onConfirm: () => {
                    window.location.href = 'handlers/export_data.php?format=json';
                    return true;
                }
            });
        });
    }
    
    // Export as JSON
    const exportJsonBtn = document.getElementById('export-json-btn');
    if (exportJsonBtn) {
        exportJsonBtn.addEventListener('click', function() {
            createModal({
                title: 'Export Data as JSON',
                message: 'Export your data in a machine-readable JSON format. This is useful for backup or transferring to other services.',
                confirmText: 'Export JSON',
                cancelText: 'Cancel',
                onConfirm: () => {
                    window.location.href = 'handlers/export_data.php?format=json';
                    return true;
                }
            });
        });
    }
    
    // Export as CSV
    const exportCsvBtn = document.getElementById('export-csv-btn');
    if (exportCsvBtn) {
        exportCsvBtn.addEventListener('click', function() {
            createModal({
                title: 'Export Data as CSV',
                message: 'Export your data in a spreadsheet-friendly CSV format. This is great for use in Excel or other data analysis tools.',
                confirmText: 'Export CSV',
                cancelText: 'Cancel',
                onConfirm: () => {
                    window.location.href = 'handlers/export_data.php?format=csv';
                    return true;
                }
            });
        });
    }

    // Opt-out toggle
    const optOutAnalytics = document.getElementById('opt_out_analytics');
    if (optOutAnalytics) {
        // Check current opt-out state from localStorage
        const isOptedOut = localStorage.getItem('optOutAnalytics') === 'true';
        optOutAnalytics.checked = isOptedOut;

        optOutAnalytics.addEventListener('change', function() {
            createModal({
                title: 'Analytics Preferences',
                message: this.checked 
                    ? 'By opting out, we will not collect any anonymous usage data. This helps us improve matSFX, but we respect your choice.' 
                    : 'By opting in, you help us improve matSFX by sharing anonymous usage data.',
                confirmText: 'Save Preferences',
                cancelText: 'Cancel',
                onConfirm: () => {
                    localStorage.setItem('optOutAnalytics', this.checked.toString());
                    return true;
                }
            });
        });
    }
});