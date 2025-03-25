/**
 * Form validation for the music upload system
 */

function validateStep1() {
    const title = document.getElementById('title').value.trim();
    if (!title) {
        showValidationError('title', 'Please enter a song title');
        return false;
    }
    return true;
}

function validateStep2() {
    const useExistingAlbum = document.querySelector('input[name="use_existing_album"]:checked').value;
    if (useExistingAlbum === '0') {
        // If creating new album, validate album name
        const album = document.getElementById('album').value.trim();
        if (!album) {
            showValidationError('album', 'Please enter an album name');
            return false;
        }
    }
    return true;
}

function showValidationError(fieldId, message) {
    const field = document.getElementById(fieldId);
    field.classList.add('error');
    
    // Check if error message already exists
    let errorMsg = field.nextElementSibling;
    if (!errorMsg || !errorMsg.classList.contains('form-error')) {
        errorMsg = document.createElement('div');
        errorMsg.classList.add('form-error');
        field.parentNode.insertBefore(errorMsg, field.nextSibling);
    }
    
    errorMsg.textContent = message;
    errorMsg.style.display = 'block';
    
    // Clear error after 3 seconds
    setTimeout(() => {
        field.classList.remove('error');
        errorMsg.style.display = 'none';
    }, 3000);
}

function updateSummary() {
    document.getElementById('summary-title').textContent = document.getElementById('title').value;
    document.getElementById('summary-genre').textContent = document.getElementById('genre').value || 'Not specified';
    
    const useExistingAlbum = document.querySelector('input[name="use_existing_album"]:checked').value;
    const albumContainer = document.getElementById('summary-album-container');
    
    if (useExistingAlbum === '0') {
        // New album
        document.getElementById('summary-album').textContent = document.getElementById('album').value;
        albumContainer.style.display = '';
    } else if (useExistingAlbum === '1') {
        // Existing album
        const select = document.getElementById('existing_album_select');
        document.getElementById('summary-album').textContent = select.options[select.selectedIndex].text;
        albumContainer.style.display = '';
    } else {
        // No album
        albumContainer.style.display = 'none';
    }
    
    document.getElementById('upload-summary').style.display = 'block';
}

function showUploadError(message) {
    const progressContainer = document.querySelector('.upload-progress-container');
    progressContainer.style.display = 'none';
    
    // Create error alert
    const errorAlert = document.createElement('div');
    errorAlert.className = 'upload-alert error';
    errorAlert.innerHTML = `
        <div class="upload-alert-icon"><i class="fas fa-exclamation-circle"></i></div>
        <div class="upload-alert-content">
            <div class="upload-alert-title">Upload Failed</div>
            <p class="upload-alert-message">${message}</p>
        </div>
    `;
    
    // Add to form
    const form = document.querySelector('.upload-form');
    form.insertBefore(errorAlert, form.firstChild);
    
    // Remove after 5 seconds
    setTimeout(() => {
        errorAlert.remove();
    }, 5000);
}

function resetUploadForm() {
    const submitButton = document.getElementById('submit-button');
    const progressBar = document.getElementById('upload-progress-bar');
    const progressText = document.getElementById('upload-progress-text');
    
    submitButton.disabled = false;
    submitButton.textContent = 'Upload Song';
    progressBar.style.width = '0%';
    progressText.textContent = '0%';
}