document.addEventListener('DOMContentLoaded', function() {
    // For badge assignment/removal
    document.querySelectorAll('.badge-form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const userId = form.querySelector('[name="user_id"]').value;
            const badgeId = form.querySelector('[name="badge_id"]').value;
            const action = form.querySelector('[name="assign_badge"]') ? 'assign_badge' : 'remove_badge';
            const badgeList = document.getElementById(`badge-list-${userId}`);

            // Add loading state
            form.classList.add('loading');

            try {
                const formData = new FormData();
                formData.append('ajax', '1');
                formData.append('user_id', userId);
                formData.append('badge_id', badgeId);
                formData.append(action, '1');

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    badgeList.textContent = result.badges || 'None';
                    badgeList.classList.add('badge-update-animation');
                    setTimeout(() => {
                        badgeList.classList.remove('badge-update-animation');
                    }, 1000);
                } else {
                    throw new Error(result.error || 'Operation failed');
                }
            } catch (error) {
                alert(error.message);
            } finally {
                form.classList.remove('loading');
            }
        });
    });

    // User termination
    const terminateModal = document.getElementById('terminateModal');
    const restoreModal = document.getElementById('restoreModal');
    let currentUserId = null;

    // Setup terminate buttons
    document.querySelectorAll('.terminate-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            currentUserId = btn.dataset.userId;
            document.getElementById('terminateUsername').textContent = btn.dataset.username;
            document.getElementById('terminationReason').value = '';
            terminateModal.style.display = 'flex';
        });
    });

    // Setup restore buttons
    document.querySelectorAll('.restore-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            currentUserId = btn.dataset.userId;
            document.getElementById('restoreUsername').textContent = btn.dataset.username;
            restoreModal.style.display = 'flex';
        });
    });

    // Close modals
    document.querySelectorAll('.modal-close, .cancel-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            terminateModal.style.display = 'none';
            restoreModal.style.display = 'none';
        });
    });

    // Confirm terminate
    document.querySelector('.confirm-terminate-btn').addEventListener('click', async () => {
        if (!currentUserId) return;

        const reason = document.getElementById('terminationReason').value.trim();
        if (!reason) {
            alert('Please provide a reason for termination.');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'terminate_account');
            formData.append('user_id', currentUserId);
            formData.append('reason', reason);

            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                alert(result.message);
                window.location.reload();
            } else {
                throw new Error(result.message || 'Operation failed');
            }
        } catch (error) {
            alert(error.message);
        } finally {
            terminateModal.style.display = 'none';
        }
    });

    // Confirm restore
    document.querySelector('.confirm-restore-btn').addEventListener('click', async () => {
        if (!currentUserId) return;

        try {
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'restore_account');
            formData.append('user_id', currentUserId);

            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                alert(result.message);
                window.location.reload();
            } else {
                throw new Error(result.message || 'Operation failed');
            }
        } catch (error) {
            alert(error.message);
        } finally {
            restoreModal.style.display = 'none';
        }
    });

    // Toggle admin status
    document.querySelectorAll('.toggle-admin-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const userId = btn.dataset.userId;
            const username = btn.dataset.username;
            const isAdmin = btn.dataset.isAdmin === '1';
            
            if (!confirm(`Are you sure you want to ${isAdmin ? 'remove admin status from' : 'make admin'} ${username}?`)) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('ajax', '1');
                formData.append('action', 'toggle_admin');
                formData.append('user_id', userId);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    window.location.reload();
                } else {
                    throw new Error(result.message || 'Operation failed');
                }
            } catch (error) {
                alert(error.message);
            }
        });
    });

    // Toggle verification status
    document.querySelectorAll('.toggle-verify-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const userId = btn.dataset.userId;
            const username = btn.dataset.username;
            const isVerified = btn.dataset.isVerified === '1';
            
            if (!confirm(`Are you sure you want to ${isVerified ? 'remove verification from' : 'verify'} ${username}?`)) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('ajax', '1');
                formData.append('action', 'toggle_verification');
                formData.append('user_id', userId);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    window.location.reload();
                } else {
                    throw new Error(result.message || 'Operation failed');
                }
            } catch (error) {
                alert(error.message);
            }
        });
    });
    
    // Handle restore from deletion queue
    const restoreDeletionBtns = document.querySelectorAll('.restore-deletion-btn');
    restoreDeletionBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const username = this.getAttribute('data-username');
            
            if (confirm(`Are you sure you want to restore the account for ${username}?`)) {
                const formData = new FormData();
                formData.append('action', 'restore_from_deletion');
                formData.append('user_id', userId);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while restoring the account');
                });
            }
        });
    });
});