// Profile Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    
    // Handle profile photo upload
    const photoUpload = document.getElementById('photoUpload');
    const photoForm = document.getElementById('photoForm');
    
    if (photoUpload) {
        photoUpload.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                photoForm.submit();
            }
        });
    }

    // Password confirmation validation
    const confirmPassword = document.getElementById('confirm_password');
    const newPassword = document.getElementById('new_password');
    
    if (confirmPassword && newPassword) {
        confirmPassword.addEventListener('input', function() {
            const newPassValue = newPassword.value;
            const confirmPassValue = this.value;
            
            if (newPassValue !== confirmPassValue) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    }

            // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                if (alert.classList.contains('alert-dismissible')) {
                    // Check if bootstrap is available
                    if (typeof bootstrap !== 'undefined') {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    } else {
                        // Fallback: hide alert manually
                        alert.style.display = 'none';
                    }
                }
            });
        }, 5000);

    // Delete account confirmation modal
    const deleteAccountBtn = document.getElementById('deleteAccountBtn');
    const deleteAccountModal = document.getElementById('deleteAccountModal');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deletePasswordInput = document.getElementById('deletePasswordInput');
    const deletePasswordForm = document.getElementById('deletePasswordForm');

    if (deleteAccountBtn) {
        deleteAccountBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (deleteAccountModal) {
                const modal = new bootstrap.Modal(deleteAccountModal);
                modal.show();
            }
        });
    }

    if (confirmDeleteBtn && deletePasswordInput) {
        confirmDeleteBtn.addEventListener('click', function() {
            const password = deletePasswordInput.value.trim();
            
            if (!password) {
                showAlert('Please enter your password to confirm deletion.', 'danger');
                return;
            }

            // Submit the delete form
            if (deletePasswordForm) {
                const passwordField = document.createElement('input');
                passwordField.type = 'hidden';
                passwordField.name = 'delete_password';
                passwordField.value = password;
                deletePasswordForm.appendChild(passwordField);
                deletePasswordForm.submit();
            }
        });
    }

    // Show alert function
    function showAlert(message, type = 'info') {
        const alertContainer = document.createElement('div');
        alertContainer.className = `alert alert-${type} alert-dismissible fade show`;
        alertContainer.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.querySelector('.main-content');
        if (container) {
            container.insertBefore(alertContainer, container.firstChild);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                if (alertContainer.parentNode) {
                    alertContainer.remove();
                }
            }, 5000);
        }
    }

    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Profile photo preview
    const profilePhoto = document.getElementById('profilePhoto');
    if (profilePhoto) {
        profilePhoto.addEventListener('error', function() {
            // Fallback to SVG avatar if image fails to load
            this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgdmlld0JveD0iMCAwIDE1MCAxNTAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxNTAiIGhlaWdodD0iMTUwIiBmaWxsPSIjRjVGNUY1Ii8+CjxjaXJjbGUgY3g9Ijc1IiBjeT0iNjAiIHI9IjIwIiBmaWxsPSIjQ0NDIi8+CjxwYXRoIGQ9Ik0yNSAxMjBDMjUgMTAwIDQ1IDg1IDc1IDg1QzEwNSA4NSAxMjUgMTAwIDEyNSAxMjBIMjVaIiBmaWxsPSIjQ0NDIi8+Cjwvc3ZnPgo=';
        });
    }

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Add loading states to buttons
    document.querySelectorAll('button[type="submit"]').forEach(button => {
        button.addEventListener('click', function() {
            if (this.form && this.form.checkValidity()) {
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
            }
        });
    });

    // Initialize tooltips
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Initialize popovers
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    }

    // Handle sidebar toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebarToggle && mainContent) {
        sidebarToggle.addEventListener('click', function() {
            mainContent.classList.toggle('collapsed');
        });
    }

    // Add animation classes to elements
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    }, observerOptions);

    // Observe cards and other elements for animation
    document.querySelectorAll('.card, .alert').forEach(el => {
        observer.observe(el);
    });

    // Handle form field focus effects
    document.querySelectorAll('.form-control, .form-select').forEach(field => {
        field.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        field.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });

    // Handle table row hover effects
    document.querySelectorAll('.table tbody tr').forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.01)';
            this.style.transition = 'transform 0.2s ease';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });

    // Handle responsive behavior
    function handleResponsive() {
        const width = window.innerWidth;
        const mainContent = document.querySelector('.main-content');
        
        if (width <= 768 && mainContent) {
            mainContent.classList.add('collapsed');
        }
    }

    // Call on load and resize
    handleResponsive();
    window.addEventListener('resize', handleResponsive);

    // Password toggle functionality
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const toggleBtn = input.parentElement.querySelector('.password-toggle i');
        
        if (input.type === 'password') {
            input.type = 'text';
            toggleBtn.className = 'fas fa-eye-slash';
        } else {
            input.type = 'password';
            toggleBtn.className = 'fas fa-eye';
        }
    }

    // Password strength indicator
    const newPasswordInput = document.getElementById('new_password');
    const passwordStrength = document.getElementById('passwordStrength');
    
    if (newPasswordInput && passwordStrength) {
        newPasswordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = checkPasswordStrength(password);
            
            passwordStrength.className = 'password-strength ' + strength.level;
            passwordStrength.title = strength.message;
        });
    }

    function checkPasswordStrength(password) {
        let score = 0;
        let feedback = [];

        if (password.length >= 8) score++;
        else feedback.push('At least 8 characters');

        if (/[a-z]/.test(password)) score++;
        else feedback.push('Lowercase letter');

        if (/[A-Z]/.test(password)) score++;
        else feedback.push('Uppercase letter');

        if (/[0-9]/.test(password)) score++;
        else feedback.push('Number');

        if (/[^A-Za-z0-9]/.test(password)) score++;
        else feedback.push('Special character');

        if (password.length >= 12) score++;

        let level, message;
        if (score <= 2) {
            level = 'weak';
            message = 'Weak password';
        } else if (score <= 3) {
            level = 'fair';
            message = 'Fair password';
        } else if (score <= 4) {
            level = 'good';
            message = 'Good password';
        } else {
            level = 'strong';
            message = 'Strong password';
        }

        return { level, message, feedback };
    }

    // Add success message handling
    const urlParams = new URLSearchParams(window.location.search);
    const success = urlParams.get('success');
    const message = urlParams.get('message');
    
    if (success && message) {
        showAlert(decodeURIComponent(message), success === 'true' ? 'success' : 'danger');
        
        // Clean up URL
        const newUrl = window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);
    }

    // Make functions globally available
    window.togglePassword = togglePassword;
}); 