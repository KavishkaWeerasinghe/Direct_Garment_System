document.addEventListener('DOMContentLoaded', function() {
    // Password strength indicator
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        const strengthBar = document.createElement('div');
        strengthBar.className = 'password-strength';
        const strengthBarInner = document.createElement('div');
        strengthBarInner.className = 'password-strength-bar';
        strengthBar.appendChild(strengthBarInner);
        passwordInput.parentNode.insertBefore(strengthBar, passwordInput.nextSibling);
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Length check
            if (password.length >= 8) strength += 1;
            
            // Contains uppercase
            if (/[A-Z]/.test(password)) strength += 1;
            
            // Contains lowercase
            if (/[a-z]/.test(password)) strength += 1;
            
            // Contains number
            if (/[0-9]/.test(password)) strength += 1;
            
            // Contains special char
            if (/[\W_]/.test(password)) strength += 1;

            // Update strength bar
            const width = strength * 20;
            let color = '#dc3545'; // red
            
            if (strength >= 3) color = '#ffc107'; // yellow
            if (strength >= 4) color = '#28a745'; // green
            
            strengthBarInner.style.width = width + '%';
            strengthBarInner.style.backgroundColor = color;
        });
    }
    
    // Confirm password match
    const confirmPasswordInput = document.getElementById('confirm_password');
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', function() {
            const password = document.getElementById('password').value;
            if (this.value !== password) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    }
    
    // Terms and conditions checkbox validation
    const termsCheckbox = document.getElementById('terms');
    if (termsCheckbox) {
        termsCheckbox.addEventListener('change', function() {
            const submitButton = document.querySelector('button[type="submit"]');
            if (this.checked) {
                submitButton.disabled = false;
            } else {
                submitButton.disabled = true;
            }
        });
    }
});