function handleRestrictedFeature(response) {
    if (response.prompt_signup) {
        const modal = document.createElement('div');
        modal.innerHTML = `
            <div class="signup-prompt-modal">
                <h2>Create an Account</h2>
                <p>${response.message}</p>
                <button onclick="redirectToSignup()">Sign Up</button>
                <button onclick="closeModal()">Cancel</button>
            </div>
        `;
        document.body.appendChild(modal);
    }
}

function redirectToSignup() {
    window.location.href = '/register';
}

function closeModal() {
    document.querySelector('.signup-prompt-modal').remove();
}