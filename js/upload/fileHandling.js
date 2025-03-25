/**
 * File handling for song and cover art uploads
 */

function initFileHandlers() {
    // Cover art preview
    const coverArtInput = document.getElementById('cover_art');
    const coverPreview = document.getElementById('cover_preview');
    
    coverArtInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                coverPreview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        } else {
            coverPreview.src = 'defaults/default-cover.jpg';
        }
    });
    
    // Song file upload handling
    const songFileInput = document.getElementById('song_file');
    const dropZone = document.getElementById('song-upload-drop');
    const songFileName = document.getElementById('song-file-name');
    const songFileSize = document.getElementById('song-file-size');
    
    // Handle file selection
    songFileInput.addEventListener('change', function() {
        handleSongFile(this.files[0]);
    });
    
    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    // Highlight drop zone when item is dragged over it
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });
    
    function highlight() {
        dropZone.classList.add('highlight');
    }
    
    function unhighlight() {
        dropZone.classList.remove('highlight');
    }
    
    // Handle dropped files
    dropZone.addEventListener('drop', function(e) {
        const dt = e.dataTransfer;
        const file = dt.files[0];
        handleSongFile(file);
        
        // Set the file to the input
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        songFileInput.files = dataTransfer.files;
    });
    
    // Click on drop zone to trigger file input
    dropZone.addEventListener('click', function() {
        songFileInput.click();
    });
}

function handleSongFile(file) {
    if (!file) return;
    
    const dropZone = document.getElementById('song-upload-drop');
    const songFileName = document.getElementById('song-file-name');
    const songFileSize = document.getElementById('song-file-size');
    
    // Check file type
    const fileType = file.type;
    const validTypes = uploadConfig.validSongTypes;
    
    if (!validTypes.includes(fileType)) {
        showValidationError('song_file', 'Please upload only MP3 or WAV files');
        return;
    }
    
    // Check file size
    const maxSize = uploadConfig.maxSongSize;
    if (file.size > maxSize) {
        showValidationError('song_file', 'File is too large. Maximum size is ' + (maxSize / (1024 * 1024)) + 'MB');
        return;
    }
    
    // Update UI
    songFileName.textContent = file.name;
    const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
    songFileSize.textContent = sizeMB + ' MB';
    
    // Change drop zone style to show file is selected
    dropZone.classList.add('file-selected');
    dropZone.innerHTML = '<i class="fas fa-music"></i><span>' + file.name + '</span>';
}

// Upload form submission with progress tracking
function initUploadSubmission() {
    const uploadForm = document.getElementById('upload-form');
    const progressBar = document.getElementById('upload-progress-bar');
    const progressText = document.getElementById('upload-progress-text');
    const progressContainer = document.querySelector('.upload-progress-container');
    const submitButton = document.getElementById('submit-button');
    const songFileInput = document.getElementById('song_file');
    
    uploadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate song file
        if (!songFileInput.files || !songFileInput.files[0]) {
            showValidationError('song_file', 'Please select a song file');
            return;
        }
        
        // Show progress bar
        progressContainer.style.display = 'block';
        submitButton.disabled = true;
        submitButton.textContent = 'Uploading...';
        
        // Create FormData object
        const formData = new FormData(this);
        
        // Create XMLHttpRequest
        const xhr = new XMLHttpRequest();
        xhr.open('POST', uploadConfig.handlerUrl, true);
        
        // Track upload progress
        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = percent + '%';
                progressText.textContent = percent + '%';
            }
        };
        
        // Handle response
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Successful upload
                        progressBar.style.width = '100%';
                        progressText.textContent = 'Upload complete!';
                        
                        // Redirect to success page or show success message
                        setTimeout(() => {
                            window.location.href = 'upload_success.php?song_id=' + response.song_id;
                        }, 1000);
                    } else {
                        // Error
                        showUploadError(response.message);
                        resetUploadForm();
                    }
                } catch (e) {
                    showUploadError('Invalid response from server');
                    resetUploadForm();
                }
            } else {
                showUploadError('Server error: ' + xhr.status);
                resetUploadForm();
            }
        };
        
        // Handle error
        xhr.onerror = function() {
            showUploadError('Network error occurred');
            resetUploadForm();
        };
        
        // Submit form
        xhr.send(formData);
    });
}