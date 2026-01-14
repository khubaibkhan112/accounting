/**
 * Client-side validation utilities for forms
 */

export const validators = {
    required: (value, message = 'This field is required') => {
        if (value === null || value === undefined || value === '' || (Array.isArray(value) && value.length === 0)) {
            return message;
        }
        return null;
    },

    email: (value, message = 'Please enter a valid email address') => {
        if (!value) return null; // Skip if empty (use required validator for that)
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            return message;
        }
        return null;
    },

    minLength: (value, min, message = null) => {
        if (!value) return null;
        const msg = message || `Must be at least ${min} characters`;
        if (value.length < min) {
            return msg;
        }
        return null;
    },

    maxLength: (value, max, message = null) => {
        if (!value) return null;
        const msg = message || `Must be no more than ${max} characters`;
        if (value.length > max) {
            return msg;
        }
        return null;
    },

    min: (value, min, message = null) => {
        if (value === null || value === undefined || value === '') return null;
        const num = parseFloat(value);
        if (isNaN(num) || num < min) {
            const msg = message || `Must be at least ${min}`;
            return msg;
        }
        return null;
    },

    max: (value, max, message = null) => {
        if (value === null || value === undefined || value === '') return null;
        const num = parseFloat(value);
        if (isNaN(num) || num > max) {
            const msg = message || `Must be no more than ${max}`;
            return msg;
        }
        return null;
    },

    numeric: (value, message = 'Must be a valid number') => {
        if (!value) return null;
        if (isNaN(parseFloat(value))) {
            return message;
        }
        return null;
    },

    positive: (value, message = 'Must be a positive number') => {
        if (!value) return null;
        const num = parseFloat(value);
        if (isNaN(num) || num < 0) {
            return message;
        }
        return null;
    },

    date: (value, message = 'Please enter a valid date') => {
        if (!value) return null;
        const date = new Date(value);
        if (isNaN(date.getTime())) {
            return message;
        }
        return null;
    },

    dateAfter: (value, afterDate, message = null) => {
        if (!value || !afterDate) return null;
        const date = new Date(value);
        const after = new Date(afterDate);
        if (isNaN(date.getTime()) || isNaN(after.getTime())) return null;
        const msg = message || `Must be after ${afterDate}`;
        if (date <= after) {
            return msg;
        }
        return null;
    },

    phone: (value, message = 'Please enter a valid phone number') => {
        if (!value) return null;
        const phoneRegex = /^[\d\s\-\+\(\)]+$/;
        if (!phoneRegex.test(value)) {
            return message;
        }
        return null;
    },

    url: (value, message = 'Please enter a valid URL') => {
        if (!value) return null;
        try {
            new URL(value);
            return null;
        } catch {
            return message;
        }
    },

    pattern: (value, regex, message = 'Invalid format') => {
        if (!value) return null;
        if (!regex.test(value)) {
            return message;
        }
        return null;
    },

    custom: (value, validatorFn, message = 'Invalid value') => {
        if (!value) return null;
        if (!validatorFn(value)) {
            return message;
        }
        return null;
    },
};

/**
 * Validate a form field with multiple validators
 * @param {*} value - The value to validate
 * @param {Array} rules - Array of validator functions or objects {validator, params, message}
 * @returns {string|null} - Error message or null if valid
 */
export function validateField(value, rules) {
    if (!rules || rules.length === 0) return null;

    for (const rule of rules) {
        let validator, params, message;

        if (typeof rule === 'function') {
            validator = rule;
            params = [];
            message = null;
        } else if (typeof rule === 'object') {
            validator = rule.validator || rule;
            params = rule.params || [];
            message = rule.message || null;
        } else {
            continue;
        }

        let error;
        if (typeof validator === 'string' && validators[validator]) {
            // String reference to validator
            error = validators[validator](value, ...params, message);
        } else if (typeof validator === 'function') {
            // Direct function
            error = validator(value, ...params, message);
        }

        if (error) {
            return error;
        }
    }

    return null;
}

/**
 * Validate an entire form object
 * @param {Object} formData - Form data object
 * @param {Object} validationRules - Object with field names as keys and rules arrays as values
 * @returns {Object} - Object with field names as keys and error messages as values
 */
export function validateForm(formData, validationRules) {
    const errors = {};

    for (const [field, rules] of Object.entries(validationRules)) {
        const value = formData[field];
        const error = validateField(value, rules);
        if (error) {
            errors[field] = error;
        }
    }

    return errors;
}

/**
 * Check if form has any errors
 * @param {Object} errors - Errors object from validateForm
 * @returns {boolean}
 */
export function hasErrors(errors) {
    return Object.keys(errors).length > 0;
}

/**
 * Clear validation errors for a field
 * @param {Object} errors - Errors object
 * @param {string} field - Field name
 */
export function clearError(errors, field) {
    if (errors[field]) {
        delete errors[field];
    }
}

/**
 * Clear all validation errors
 * @param {Object} errors - Errors object
 */
export function clearAllErrors(errors) {
    Object.keys(errors).forEach(key => delete errors[key]);
}
