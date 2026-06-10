// ============================================
// FORM VALIDATION JAVASCRIPT
// HRGetafe - Human Resources Information System
// ============================================

/**
 * Validate Email
 */
function validateEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

/**
 * Validate Phone
 */
function validatePhone(phone) {
    const regex = /^[\d\s\-\+\(\)]{10,}$/;
    return regex.test(phone);
}

/**
 * Validate Date
 */
function validateDate(dateString) {
    const regex = /^\d{4}-\d{2}-\d{2}$/;
    if (!regex.test(dateString)) return false;
    
    const date = new Date(dateString);
    return date instanceof Date && !isNaN(date);
}

/**
 * Validate Number
 */
function validateNumber(value) {
    return !isNaN(parseFloat(value)) && isFinite(value);
}

/**
 * Validate Required Field
 */
function validateRequired(value) {
    return value.trim() !== '';
}

/**
 * Validate Min Length
 */
function validateMinLength(value, minLength) {
    return value.length >= minLength;
}

/**
 * Validate Max Length
 */
function validateMaxLength(value, maxLength) {
    return value.length <= maxLength;
}

/**
 * Validate Password Strength
 */
function validatePasswordStrength(password) {
    // Minimum 8 characters, 1 uppercase, 1 lowercase, 1 number
    const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d\S]{8,}$/;
    return regex.test(password);
}

/**
 * Get Password Strength Score
 */
function getPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z\d]/.test(password)) strength++;
    
    return strength;
}

/**
 * Show Password Strength Indicator
 */
function showPasswordStrength(inputId, indicatorId) {
    const input = document.getElementById(inputId);
    const indicator = document.getElementById(indicatorId);
    
    if (!input || !indicator) return;
    
    input.addEventListener('input', function() {
        const strength = getPasswordStrength(this.value);
        let strengthText = '';
        let strengthColor = '';
        
        switch(strength) {
            case 0:
            case 1:
                strengthText = 'Weak';
                strengthColor = 'danger';
                break;
            case 2:
            case 3:
                strengthText = 'Fair';
                strengthColor = 'warning';
                break;
            case 4:
            case 5:
                strengthText = 'Strong';
                strengthColor = 'success';
                break;
        }
        
        if (this.value.length > 0) {
            indicator.innerHTML = `<small class="text-${strengthColor}">Strength: ${strengthText}</small>`;
        } else {
            indicator.innerHTML = '';
        }
    });
}

/**
 * Validate Form
 */
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    let isValid = true;
    const fields = form.querySelectorAll('[data-validate]');
    
    fields.forEach(field => {
        const validationType = field.dataset.validate;
        let isFieldValid = false;
        
        switch(validationType) {
            case 'required':
                isFieldValid = validateRequired(field.value);
                break;
            case 'email':
                isFieldValid = validateEmail(field.value);
                break;
            case 'phone':
                isFieldValid = validatePhone(field.value);
                break;
            case 'date':
                isFieldValid = validateDate(field.value);
                break;
            case 'number':
                isFieldValid = validateNumber(field.value);
                break;
            case 'password':
                isFieldValid = validatePasswordStrength(field.value);
                break;
        }
        
        if (!isFieldValid) {
            isValid = false;
            field.classList.add('is-invalid');
            
            // Show error message
            const errorMsg = field.nextElementSibling;
            if (errorMsg && errorMsg.classList.contains('invalid-feedback')) {
                errorMsg.style.display = 'block';
            }
        } else {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
            
            const errorMsg = field.nextElementSibling;
            if (errorMsg && errorMsg.classList.contains('invalid-feedback')) {
                errorMsg.style.display = 'none';
            }
        }
    });
    
    return isValid;
}

/**
 * Clear Form Validation
 */
function clearFormValidation(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.querySelectorAll('.is-invalid, .is-valid').forEach(field => {
            field.classList.remove('is-invalid');
            field.classList.remove('is-valid');
        });
        
        form.querySelectorAll('.invalid-feedback').forEach(msg => {
            msg.style.display = 'none';
        });
    }
}

/**
 * Initialize Real-time Validation
 */
function initializeRealTimeValidation(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    const fields = form.querySelectorAll('[data-validate]');
    
    fields.forEach(field => {
        field.addEventListener('blur', function() {
            const validationType = this.dataset.validate;
            let isValid = false;
            
            switch(validationType) {
                case 'required':
                    isValid = validateRequired(this.value);
                    break;
                case 'email':
                    isValid = validateEmail(this.value);
                    break;
                case 'phone':
                    isValid = validatePhone(this.value);
                    break;
                case 'date':
                    isValid = validateDate(this.value);
                    break;
                case 'number':
                    isValid = validateNumber(this.value);
                    break;
            }
            
            if (isValid) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            }
        });
    });
}
