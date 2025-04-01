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

// Function to handle viewing appeal details
function handleViewAppeal(btn) {
    const modal = document.getElementById('viewAppealModal');
    
    // Get data from button attributes
    const username = btn.dataset.username;
    const appealDate = btn.dataset.appealDate;
    const status = btn.dataset.status;
    const terminationReason = btn.dataset.terminationReason;
    const terminatedAt = btn.dataset.terminatedAt;
    const appealReason = btn.dataset.appealReason;
    const adminResponse = btn.dataset.adminResponse;

    // Update modal content
    modal.querySelector('#appealUsername').textContent = username;
    modal.querySelector('#appealDate').textContent = `Appeal submitted: ${appealDate}`;
    modal.querySelector('#appealStatus').textContent = status.charAt(0).toUpperCase() + status.slice(1);
    modal.querySelector('#appealStatus').className = `appeal-status status-${status}`;
    
    // display termination details
    modal.querySelector('#terminationDate').textContent = `Terminated on: ${terminatedAt}`;
    modal.querySelector('#terminationReason').innerHTML = terminationReason.replace(/\n/g, '<br>');
    
    modal.querySelector('#appealReason').innerHTML = appealReason.replace(/\n/g, '<br>');
    
    // Handle admin response section
    const adminResponseSection = modal.querySelector('#adminResponseSection');
    const adminResponseElement = modal.querySelector('#adminResponse');
    
    if (adminResponse && adminResponse.trim()) {
        adminResponseSection.style.display = 'block';
        adminResponseElement.innerHTML = adminResponse.replace(/\n/g, '<br>');
    } else {
        adminResponseSection.style.display = 'none';
    }

    // Show modal
    modal.style.display = 'flex';
}

// Add event listeners
document.addEventListener('DOMContentLoaded', function() {
    // View appeal button click handler
    document.querySelectorAll('.view-appeal-btn').forEach(btn => {
        btn.addEventListener('click', () => handleViewAppeal(btn));
    });
    
    // Close modal handlers
    document.querySelectorAll('.modal-close, .cancel-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.modal-backdrop').forEach(modal => {
                modal.style.display = 'none';
            });
        });
    });
    
    // Appeals Management
    const viewAppealModal = document.getElementById('viewAppealModal');
    const approveAppealModal = document.getElementById('approveAppealModal');
    const rejectAppealModal = document.getElementById('rejectAppealModal');
    let currentAppealId = null;

    // View Appeal
    document.querySelectorAll('.view-appeal-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const appealId = this.dataset.appealId;
            const username = this.dataset.username;
            const appealDate = this.dataset.appealDate;
            const appealReason = this.dataset.appealReason;
            const terminationReason = this.dataset.terminationReason;
            const terminatedAt = this.dataset.terminatedAt;
            const status = this.dataset.status;
            const adminResponse = this.dataset.adminResponse;
            
            // Fill modal content
            document.getElementById('appealUsername').textContent = username;
            document.getElementById('appealDate').textContent = 'Submitted: ' + appealDate;
            document.getElementById('terminationDate').textContent = 'Terminated on: ' + terminatedAt;
            document.getElementById('terminationReason').textContent = terminationReason;
            document.getElementById('appealReason').textContent = appealReason;
            
            // Set status badge
            const statusElement = document.getElementById('appealStatus');
            statusElement.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            statusElement.className = 'appeal-status status-' + status;
            
            // Admin response section
            const responseSection = document.getElementById('adminResponseSection');
            if (status === 'pending') {
                responseSection.style.display = 'none';
            } else {
                responseSection.style.display = 'block';
                document.getElementById('adminResponse').textContent = adminResponse || 'No response provided';
            }
            
            // Show modal
            viewAppealModal.style.display = 'flex';
        });
    });

    // Approve Appeal
    document.querySelectorAll('.approve-appeal-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            currentAppealId = this.dataset.appealId;
            document.getElementById('approveUsername').textContent = this.dataset.username;
            approveAppealModal.style.display = 'flex';
        });
    });

    // Reject Appeal
    document.querySelectorAll('.reject-appeal-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            currentAppealId = this.dataset.appealId;
            document.getElementById('rejectUsername').textContent = this.dataset.username;
            rejectAppealModal.style.display = 'flex';
        });
    });

    // Close modals
    document.querySelectorAll('.modal-close, .cancel-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            viewAppealModal.style.display = 'none';
            approveAppealModal.style.display = 'none';
            rejectAppealModal.style.display = 'none';
        });
    });

    // Confirm approve appeal
    document.querySelector('.confirm-approve-btn').addEventListener('click', async function() {
        if (!currentAppealId) return;
        
        const response = document.getElementById('approveResponse').value.trim();
        if (!response) {
            alert('Please provide a response for the user.');
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'process_appeal');
            formData.append('appeal_id', currentAppealId);
            formData.append('status', 'approved');
            formData.append('response', response);
            
            const fetchResponse = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
            
            const result = await fetchResponse.json();
            
            if (result.success) {
                alert(result.message);
                window.location.reload();
            } else {
                throw new Error(result.message || 'Operation failed');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        } finally {
            approveAppealModal.style.display = 'none';
        }
    });

    // Confirm reject appeal
    document.querySelector('.confirm-reject-btn').addEventListener('click', async function() {
        if (!currentAppealId) return;
        
        const response = document.getElementById('rejectResponse').value.trim();
        if (!response) {
            alert('Please provide a response for the user.');
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'process_appeal');
            formData.append('appeal_id', currentAppealId);
            formData.append('status', 'rejected');
            formData.append('response', response);
            
            const fetchResponse = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
            
            const result = await fetchResponse.json();
            
            if (result.success) {
                alert(result.message);
                window.location.reload();
            } else {
                throw new Error(result.message || 'Operation failed');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        } finally {
            rejectAppealModal.style.display = 'none';
        }
    });
});