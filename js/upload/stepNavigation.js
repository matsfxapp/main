/**
 * Step navigation for the multi-step upload form
 */

let currentStep = 0;
const steps = document.querySelectorAll('.form-step');
const progressSteps = document.querySelectorAll('.upload-step');

function initStepNavigation() {
    const nextButtons = document.querySelectorAll('.next-step');
    const prevButtons = document.querySelectorAll('.prev-step');
    
    nextButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Validate current step
            if (currentStep === 0) {
                if (!validateStep1()) return;
            } else if (currentStep === 1) {
                if (!validateStep2()) return;
                updateSummary();
            }
            
            // Hide current step, show next step
            steps[currentStep].classList.remove('active');
            currentStep++;
            if (currentStep >= steps.length) currentStep = steps.length - 1;
            steps[currentStep].classList.add('active');
            
            // Update progress steps
            updateProgressSteps();
        });
    });
    
    prevButtons.forEach(button => {
        button.addEventListener('click', function() {
            steps[currentStep].classList.remove('active');
            currentStep--;
            if (currentStep < 0) currentStep = 0;
            steps[currentStep].classList.add('active');
            
            // Update progress steps
            updateProgressSteps();
        });
    });
}

function updateProgressSteps() {
    progressSteps.forEach((step, index) => {
        step.classList.remove('active', 'completed');
        if (index === currentStep) {
            step.classList.add('active');
        } else if (index < currentStep) {
            step.classList.add('completed');
        }
    });
}

// Initialize album options
function initAlbumOptions() {
    const albumOptions = document.querySelectorAll('input[name="use_existing_album"]');
    const newAlbumForm = document.getElementById('new-album-form');
    const existingAlbumForm = document.getElementById('existing-album-form');
    const coverArtGroup = document.getElementById('cover-art-group');
    
    albumOptions.forEach(option => {
        option.addEventListener('change', function() {
            const value = this.value;
            
            if (value === '0') {
                // New album
                newAlbumForm.style.display = 'block';
                if (existingAlbumForm) existingAlbumForm.style.display = 'none';
                coverArtGroup.style.display = 'block';
            } else if (value === '1') {
                // Existing album
                newAlbumForm.style.display = 'none';
                if (existingAlbumForm) existingAlbumForm.style.display = 'block';
                coverArtGroup.style.display = 'block';
            } else {
                // No album
                newAlbumForm.style.display = 'none';
                if (existingAlbumForm) existingAlbumForm.style.display = 'none';
                document.getElementById('album').value = '';
                coverArtGroup.style.display = 'block';
            }
        });
    });
}