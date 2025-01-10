document.addEventListener('DOMContentLoaded', function() {
    loadUsers();
    loadSongs();
    initializeEventListeners();
    setupTabNavigation();
    
    const activeSection = document.querySelector('.admin-section.active');
    if (activeSection) {
        if (activeSection.id === 'users') {
            loadUsers();
        } else if (activeSection.id === 'songs') {
            loadSongs();
        }
    }
    
    // Setup search functionality
    const userSearch = document.getElementById('userSearch');
    const songSearch = document.getElementById('songSearch');
    
    if (userSearch) {
        userSearch.addEventListener('input', debounce(function() {
            loadUsers(this.value);
        }, 300));
    }

    if (songSearch) {
        songSearch.addEventListener('input', debounce(function() {
            loadSongs(this.value);
        }, 300));
    }

    // Add global edit user function
    window.editUser = function(userId) {
        fetch(`../handlers/admin_handlers.php?action=getUserDetails&id=${userId}`)
            .then(response => response.json())
            .then(user => {
                showEditUserModal(user);
            })
            .catch(error => {
                console.error('Error loading user details:', error);
                showNotification('Error loading user details', 'error');
            });
    };
});

// Utility Functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func.apply(this, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function showEditUserModal(user) {
    // Remove any existing modals first
    const existingModal = document.querySelector('.modal');
    if (existingModal) {
        existingModal.remove();
    }

    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <h2>Edit User</h2>
            <form id="editUserForm">
                <input type="hidden" name="userId" value="${user.user_id}">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" value="${escapeHtml(user.username)}" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="${escapeHtml(user.email)}" required>
                </div>
                <div class="form-group">
                    <label>Badges</label>
                    <div>
                        <label>
                            <input type="checkbox" name="is_verified" ${user.is_verified ? 'checked' : ''}>
                            Verified
                        </label>
                        <label>
                            <input type="checkbox" name="is_helper" ${user.is_helper ? 'checked' : ''}>
                            Helper
                        </label>
                        <label>
                            <input type="checkbox" name="is_donator" ${user.is_donator ? 'checked' : ''}>
                            Donator
                        </label>
                        <label>
                            <input type="checkbox" name="is_developer" ${user.is_developer ? 'checked' : ''}>
                            Developer
                        </label>
                        <label>
                            <input type="checkbox" name="is_admin" ${user.is_admin ? 'checked' : ''}>
                            Admin
                        </label>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="button button-primary">Save Changes</button>
                    <button type="button" onclick="closeModal()" class="button">Cancel</button>
                </div>
            </form>
        </div>
    `;
    
    document.body.appendChild(modal);

    // Add event listener to form submission
    const editUserForm = document.getElementById('editUserForm');
    editUserForm.addEventListener('submit', function(e) {
        e.preventDefault();
        updateUser(new FormData(this));
    });

    // Add click event to close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
}

function updateUser(formData) {
    fetch('admin_handlers.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            closeModal();
            loadUsers();
            showNotification('User updated successfully');
        } else {
            showNotification(result.message || 'Error updating user', 'error');
        }
    })
    .catch(error => {
        console.error('Error updating user:', error);
        showNotification('Error updating user', 'error');
    });
}

function closeModal() {
    const modal = document.querySelector('.modal');
    if (modal) {
        modal.remove();
    }
}


function banUser(userId) {
    if (confirm('Are you sure you want to ban this user?')) {
        fetch('../handlers/admin_handlers.php?action=banUser', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ userId })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                loadUsers();
                showNotification('User banned successfully');
            } else {
                showNotification(result.message || 'Error banning user', 'error');
            }
        })
        .catch(error => {
            console.error('Error banning user:', error);
            showNotification('Error banning user', 'error');
        });
    }
}

function unbanUser(userId) {
    fetch('../handlers/admin_handlers.php?action=unbanUser', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ userId })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            loadUsers();
            showNotification('User unbanned successfully');
        } else {
            showNotification(result.message || 'Error unbanning user', 'error');
        }
    })
    .catch(error => {
        console.error('Error unbanning user:', error);
        showNotification('Error unbanning user', 'error');
    });
}

function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        fetch('../handlers/admin_handlers.php?action=deleteUser', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ userId })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                loadUsers();
                showNotification('User deleted successfully');
            } else {
                showNotification(result.message || 'Error deleting user', 'error');
            }
        })
        .catch(error => {
            console.error('Error deleting user:', error);
            showNotification('Error deleting user', 'error');
        });
    }
}

// Song Management Functions
function loadSongs(searchTerm = '') {
    fetch(`../handlers/admin_handlers.php?action=getSongs&search=${encodeURIComponent(searchTerm)}`)
        .then(response => response.json())
        .then(songs => {
            const songsList = document.getElementById('songsList');
            if (!songsList) return;
            
            songsList.innerHTML = '';
            songs.forEach(song => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <img src="${escapeHtml(song.image_url)}" alt="${escapeHtml(song.title)}" class="song-thumbnail">
                        ${escapeHtml(song.title)}
                    </td>
                    <td>${escapeHtml(song.artist)}</td>
                    <td>${new Date(song.upload_date).toLocaleDateString()}</td>
                    <td>
                        <button onclick="editSong(${song.id})" class="button button-primary">Edit</button>
                        <button onclick="deleteSong(${song.id})" class="button button-danger">Delete</button>
                    </td>
                `;
                songsList.appendChild(row);
            });
        })
        .catch(error => {
            console.error('Error loading songs:', error);
            showNotification('Error loading songs', 'error');
        });
}

function editSong(songId) {
    fetch(`../handlers/admin_handlers.php?action=getSongDetails&id=${songId}`)
        .then(response => response.json())
        .then(song => {
            showEditSongModal(song);
        })
        .catch(error => {
            console.error('Error loading song details:', error);
            showNotification('Error loading song details', 'error');
        });
}

function showEditSongModal(song) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <h2>Edit Song</h2>
            <form id="editSongForm">
                <input type="hidden" name="songId" value="${song.id}">
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" value="${escapeHtml(song.title)}" required>
                </div>
                <div class="form-group">
                    <label>Artist</label>
                    <input type="text" name="artist" value="${escapeHtml(song.artist)}" required>
                </div>
                <div class="form-group">
                    <label>Current Image</label>
                    <img src="${escapeHtml(song.image_url)}" alt="Current cover" class="song-cover-preview">
                </div>
                <div class="form-group">
                    <label>New Cover Image</label>
                    <input type="file" name="cover_image" accept="image/*">
                </div>
                <div class="form-actions">
                    <button type="submit" class="button button-primary">Save Changes</button>
                    <button type="button" onclick="closeModal()" class="button">Cancel</button>
                </div>
            </form>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    document.getElementById('editSongForm').addEventListener('submit', function(e) {
        e.preventDefault();
        updateSong(new FormData(this));
    });
}

function updateSong(formData) {
    fetch('../handlers/admin_handlers.php?action=updateSong', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            closeModal();
            loadSongs();
            showNotification('Song updated successfully');
        } else {
            showNotification(result.message || 'Error updating song', 'error');
        }
    })
    .catch(error => {
        console.error('Error updating song:', error);
        showNotification('Error updating song', 'error');
    });
}

function deleteSong(songId) {
    if (confirm('Are you sure you want to delete this song? This action cannot be undone.')) {
        fetch('../handlers/admin_handlers.php?action=deleteSong', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ songId })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                loadSongs();
                showNotification('Song deleted successfully');
            } else {
                showNotification(result.message || 'Error deleting song', 'error');
            }
        })
        .catch(error => {
            console.error('Error deleting song:', error);
            showNotification('Error deleting song', 'error');
        });
    }
}

function closeModal() {
    const modal = document.querySelector('.modal');
    if (modal) {
        modal.remove();
    }
}

function loadUsers(searchTerm = '') {
    fetch(`../handlers/admin_handlers.php?action=getUsers&search=${encodeURIComponent(searchTerm)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(users => {
            const usersList = document.getElementById('usersList');
            if (!usersList) return;
            
            usersList.innerHTML = '';
            if (users.error) {
                showNotification(users.error, 'error');
                return;
            }
            
            users.forEach(user => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${escapeHtml(user.username)}</td>
                    <td>${escapeHtml(user.email)}</td>
                    <td>
                        <span class="badge badge-${user.is_banned ? 'danger' : 'success'}">
                            ${user.is_banned ? 'Banned' : 'Active'}
                        </span>
                    </td>
                    <td>
                        <button onclick="editUser(${user.id})" class="button button-primary">Edit</button>
                        ${user.is_banned ? 
                            `<button onclick="unbanUser(${user.id})" class="button button-success">Unban</button>` :
                            `<button onclick="banUser(${user.id})" class="button button-danger">Ban</button>`
                        }
                        <button onclick="deleteUser(${user.id})" class="button button-danger">Delete</button>
                    </td>
                `;
                usersList.appendChild(row);
            });
        })
        .catch(error => {
            console.error('Error loading users:', error);
            showNotification('Error loading users. Please check console for details.', 'error');
        });
}

function loadSongs(searchTerm = '') {
    fetch(`../handlers/admin_handlers.php?action=getSongs&search=${encodeURIComponent(searchTerm)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(songs => {
            const songsList = document.getElementById('songsList');
            if (!songsList) return;
            
            songsList.innerHTML = '';
            if (songs.error) {
                showNotification(songs.error, 'error');
                return;
            }
            
            songs.forEach(song => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <img src="${escapeHtml(song.image_url)}" alt="${escapeHtml(song.title)}" class="song-thumbnail">
                        ${escapeHtml(song.title)}
                    </td>
                    <td>${escapeHtml(song.artist)}</td>
                    <td>${new Date(song.upload_date).toLocaleDateString()}</td>
                    <td>
                        <button onclick="editSong(${song.id})" class="button button-primary">Edit</button>
                        <button onclick="deleteSong(${song.id})" class="button button-danger">Delete</button>
                    </td>
                `;
                songsList.appendChild(row);
            });
        })
        .catch(error => {
            console.error('Error loading songs:', error);
            showNotification('Error loading songs. Please check console for details.', 'error');
        });
}

function initializeEventListeners() {
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
}