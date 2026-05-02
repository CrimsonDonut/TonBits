// Error Modal Handler
const errorModal = document.getElementById('errorModal');

if (errorModal) {
    // Close modal when clicking outside the modal content
    errorModal.addEventListener('click', function(event) {
        if (event.target === errorModal) {
            errorModal.style.display = 'none';
        }
    });
    
    // Close modal when clicking the close button
    const closeBtn = errorModal.querySelector('.modal-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            errorModal.style.display = 'none';
        });
    }
}

// Profile dropdown toggle
const profileBtn = document.getElementById('profileBtn');
const profileDropdown = document.getElementById('profileDropdown');

profileBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    profileDropdown.classList.toggle('active');
});

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.profile-dropdown-container')) {
        profileDropdown.classList.remove('active');
    }
});
