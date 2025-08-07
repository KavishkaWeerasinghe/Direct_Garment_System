// Company Settings Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    
    // Handle company logo upload
    const logoUpload = document.getElementById('logoUpload');
    const logoForm = document.getElementById('logoForm');
    
    if (logoUpload) {
        logoUpload.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                // Show loading state
                const logoContainer = document.querySelector('.company-logo-container');
                if (logoContainer) {
                    logoContainer.style.opacity = '0.7';
                }
                
                // Submit form
                logoForm.submit();
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

    // Company logo preview
    const companyLogo = document.getElementById('companyLogo');
    if (companyLogo) {
        companyLogo.addEventListener('error', function() {
            // Fallback to SVG logo if image fails to load
            this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgdmlld0JveD0iMCAwIDE1MCAxNTAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxNTAiIGhlaWdodD0iMTUwIiBmaWxsPSIjRjVGNUY1Ii8+CjxwYXRoIGQ9Ik0yNSAzMEgxMjVWMTAwSDEyNVYxMjBIMjVWMzBaIiBmaWxsPSIjQ0NDIi8+CjxwYXRoIGQ9Ik0yNSAxMjBIMTI1VjEwMEgyNVYxMjBaIiBmaWxsPSIjQ0NDIi8+Cjwvc3ZnPgo=';
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
            mainContent.classList.toggle('sidebar-collapsed');
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

    // Real-time form validation
    const requiredFields = document.querySelectorAll('input[required], select[required], textarea[required]');
    requiredFields.forEach(field => {
        field.addEventListener('blur', function() {
            validateField(this);
        });
        
        field.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                validateField(this);
            }
        });
    });

    function validateField(field) {
        const value = field.value.trim();
        const isValid = value.length > 0;
        
        if (isValid) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
        } else {
            field.classList.remove('is-valid');
            field.classList.add('is-invalid');
        }
    }

    // URL validation for website and social links
    const urlFields = document.querySelectorAll('input[type="url"]');
    urlFields.forEach(field => {
        field.addEventListener('blur', function() {
            validateUrlField(this);
        });
    });

    function validateUrlField(field) {
        const value = field.value.trim();
        
        if (value === '') {
            field.classList.remove('is-invalid', 'is-valid');
            return;
        }
        
        try {
            new URL(value);
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
        } catch (e) {
            field.classList.remove('is-valid');
            field.classList.add('is-invalid');
        }
    }

    // Email validation
    const emailFields = document.querySelectorAll('input[type="email"]');
    emailFields.forEach(field => {
        field.addEventListener('blur', function() {
            validateEmailField(this);
        });
    });

    function validateEmailField(field) {
        const value = field.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (value === '') {
            field.classList.remove('is-invalid', 'is-valid');
            return;
        }
        
        if (emailRegex.test(value)) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
        } else {
            field.classList.remove('is-valid');
            field.classList.add('is-invalid');
        }
    }

    // Number validation
    const numberFields = document.querySelectorAll('input[type="number"]');
    numberFields.forEach(field => {
        field.addEventListener('blur', function() {
            validateNumberField(this);
        });
    });

    function validateNumberField(field) {
        const value = field.value.trim();
        
        if (value === '') {
            field.classList.remove('is-invalid', 'is-valid');
            return;
        }
        
        const numValue = parseInt(value);
        const min = parseInt(field.min) || 0;
        const max = parseInt(field.max) || Infinity;
        
        if (!isNaN(numValue) && numValue >= min && numValue <= max) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
        } else {
            field.classList.remove('is-valid');
            field.classList.add('is-invalid');
        }
    }

    // Handle responsive behavior
    function handleResponsive() {
        const width = window.innerWidth;
        const mainContent = document.querySelector('.main-content');
        
        if (width <= 768 && mainContent) {
            mainContent.classList.add('sidebar-collapsed');
        }
    }

    // Call on load and resize
    handleResponsive();
    window.addEventListener('resize', handleResponsive);

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

    // Character counter for textareas
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        const maxLength = textarea.getAttribute('maxlength');
        if (maxLength) {
            const counter = document.createElement('div');
            counter.className = 'form-text text-end';
            counter.textContent = `0/${maxLength} characters`;
            
            textarea.parentNode.appendChild(counter);
            
            textarea.addEventListener('input', function() {
                const currentLength = this.value.length;
                counter.textContent = `${currentLength}/${maxLength} characters`;
                
                if (currentLength > maxLength * 0.9) {
                    counter.style.color = '#dc3545';
                } else if (currentLength > maxLength * 0.7) {
                    counter.style.color = '#ffc107';
                } else {
                    counter.style.color = '#6c757d';
                }
            });
        }
    });

    // Form reset confirmation
    const resetButtons = document.querySelectorAll('button[type="reset"]');
    resetButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to reset the form? All changes will be lost.')) {
                e.preventDefault();
            }
        });
    });

    // Auto-save functionality (optional)
    let autoSaveTimer;
    const form = document.querySelector('.company-form');
    
    if (form) {
        const formFields = form.querySelectorAll('input, select, textarea');
        formFields.forEach(field => {
            field.addEventListener('input', function() {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(() => {
                    // You can implement auto-save here if needed
                    console.log('Auto-save triggered');
                }, 3000); // Auto-save after 3 seconds of inactivity
            });
        });
    }
}); 