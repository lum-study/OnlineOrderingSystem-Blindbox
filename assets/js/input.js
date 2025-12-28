// Validation utilities (shared across admin & member)
(function () {
  'use strict';

  function _isEmpty(val) {
    return typeof val === 'undefined' || val === null || String(val).trim() === '';
  }

  function _toDateOnly(value) {
    // Accepts YYYY-MM-DD or Date-compatible string. Use explicit T00:00:00 to avoid timezone issues.
    try {
      if (!_isEmpty(value)) {
        return new Date(value + 'T00:00:00');
      }
    } catch (e) {
      return new Date('invalid');
    }
    return new Date('invalid');
  }

  const InputValidators = {
    validateFullName(value) {
      const v = (value || '').trim();
      if (v === '') return { valid: false, message: 'Full name is required' };
      if (v.length < 3) return { valid: false, message: 'Full name must be at least 3 characters long' };
      if (v.length > 100) return { valid: false, message: 'Full name must not exceed 100 characters' };
      if (!/^[a-zA-Z\s\-]+$/.test(v)) return { valid: false, message: 'Full name can only contain letters, spaces, and hyphens' };
      return { valid: true };
    },

    validateUsername(value) {
      const v = (value || '').trim();
      if (v === '') return { valid: false, message: 'Username is required' };
      if (v.length < 4) return { valid: false, message: 'Username must be at least 4 characters long' };
      if (v.length > 50) return { valid: false, message: 'Username must not exceed 50 characters' };
      if (!/^[a-zA-Z0-9_-]+$/.test(v)) return { valid: false, message: 'Username can only contain letters, numbers, underscores, and hyphens' };
      return { valid: true };
    },

    validateEmail(value, opts = {}) {
      const { required = false, maxLen = 100 } = opts;
      const v = (value || '').trim();
      if (required && v === '') return { valid: false, message: 'Email is required' };
      if (!required && v === '') return { valid: true };
      if (v.length > maxLen) return { valid: false, message: `Email must not exceed ${maxLen} characters` };
      const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
      if (!emailPattern.test(v) || v.includes('..') || v.startsWith('.') || v.endsWith('.')) {
        return { valid: false, message: 'Email address format is invalid' };
      }
      return { valid: true };
    },

    getPhoneParts(form, name) {
      // Accepts names with or without add_/edit_ prefix.
      const numberEl = form.querySelector(`[name="${name}"]`) || form.querySelector(`[name="${name.replace(/^(add_|edit_)/, '')}"]`);
      const prefixEl = form.querySelector(`[name="${name}_prefix"]`) || form.querySelector(`[name="${name.replace(/^(add_|edit_)/, '')}_prefix"]`);

      return {
        number: numberEl ? String(numberEl.value || '') : '',
        prefix: prefixEl ? String(prefixEl.value || '') : '+60',
        numberEl: numberEl || null,
        prefixEl: prefixEl || null
      };
    },

    validatePhoneNumber(prefix, number, opts = {}) {
      const { required = false } = opts;
      const n = String(number || '').trim();
      if (!required && n === '') return { valid: true };
      if (required && n === '') return { valid: false, message: 'Contact number is required' };
      if (!/^[0-9]+$/.test(n)) return { valid: false, message: 'Contact number can only contain digits' };
      if (n.length < 7 || n.length > 15) return { valid: false, message: 'Contact number must be between 7 and 15 digits' };
      return { valid: true };
    },

    validateBirthDate(value, opts = {}) {
      const { minAge = 13, maxAge = 120 } = opts;
      const v = (value || '').trim();
      if (v === '') return { valid: true };

      const d = _toDateOnly(v);
      if (isNaN(d.getTime())) return { valid: false, message: 'Please enter a valid birth date' };

      const today = new Date();
      today.setHours(0, 0, 0, 0);

      if (d > today) return { valid: false, message: 'Birth date cannot be in the future' };

      const minDate = new Date();
      minDate.setFullYear(minDate.getFullYear() - maxAge);
      minDate.setHours(0, 0, 0, 0);

      const maxDate = new Date();
      maxDate.setFullYear(maxDate.getFullYear() - minAge);
      maxDate.setHours(0, 0, 0, 0);

      if (d < minDate) return { valid: false, message: `Birth date cannot be more than ${maxAge} years ago` };

      if (d > maxDate) {
        if (minAge === 18) return { valid: false, message: 'Staff must be at least 18 years old' };
        return { valid: false, message: `User must be at least ${minAge} years old` };
      }

      return { valid: true };
    },

    validateGender(value) {
      const allowed = ['', 'male', 'female', 'other', null];
      if (allowed.indexOf(value) === -1) return { valid: false, message: 'Invalid gender selection' };
      return { valid: true };
    },

    validatePosition(value, customValue) {
      const v = (value || '').trim();
      if (v === '') return { valid: false, message: 'Position is required' };
      if (v === 'other') {
        if ((customValue || '').trim() === '') return { valid: false, message: 'Custom position is required when "Other" is selected' };
      }
      return { valid: true };
    },

    // Generic form validation for create/edit user or staff forms
    // options: { isStaff: bool, isAdd: bool }
    validateUserForm(form, options = {}) {
      const isStaff = !!options.isStaff;
      const isAdd = !!options.isAdd;
      const prefix = isAdd ? 'add_' : 'edit_';

      // Username (add only)
      if (isAdd) {
        const usernameEl = form.querySelector('[name="' + prefix + 'username"]');
        if (usernameEl) {
          const r = this.validateUsername(usernameEl.value);
          if (!r.valid) {
            if (typeof showToast === 'function') showToast(r.message, 'error');
            usernameEl.focus();
            return false;
          }
        }
      }

      // Full name
      const fullnameEl = form.querySelector('[name="' + prefix + 'fullname"]') || form.querySelector('[name="fullname"]');
      if (fullnameEl) {
        const r = this.validateFullName(fullnameEl.value);
        if (!r.valid) {
          if (typeof showToast === 'function') showToast(r.message, 'error');
          fullnameEl.focus();
          return false;
        }
      }

      // Email
      const emailEl = form.querySelector('[name="' + prefix + 'email"]');
      if (emailEl && !emailEl.disabled) {
        const requiredEmail = isAdd || isStaff; // add users/staff require email, edit staff requires email
        const r = this.validateEmail(emailEl.value, { required: requiredEmail });
        if (!r.valid) {
          if (typeof showToast === 'function') showToast(r.message, 'error');
          emailEl.focus();
          return false;
        }
      }

      // Contact number (optional)
      const phone = this.getPhoneParts(form, prefix + 'contact_number');
      const phoneValidateResult = this.validatePhoneNumber(phone.prefix, phone.number, { required: false });
      if (!phoneValidateResult.valid) {
        if (typeof showToast === 'function') showToast(phoneValidateResult.message, 'error');
        if (phone.numberEl) phone.numberEl.focus();
        return false;
      }

      // Birth date
      const birthDateEl = form.querySelector('[name="' + prefix + 'birth_date"]') || form.querySelector('[name="birth_date"]');
      if (birthDateEl) {
        const r = this.validateBirthDate(birthDateEl.value, { minAge: isStaff ? 18 : 13, maxAge: 120 });
        if (!r.valid) {
          if (typeof showToast === 'function') showToast(r.message, 'error');
          birthDateEl.focus();
          return false;
        }
      }

      // Gender (optional)
      const genderEl = form.querySelector('[name="' + prefix + 'gender"]') || form.querySelector('[name="gender"]');
      if (genderEl) {
        const r = this.validateGender(genderEl.value);
        if (!r.valid) {
          if (typeof showToast === 'function') showToast(r.message, 'error');
          genderEl.focus();
          return false;
        }
      }

      // Position (staff specific)
      if (isStaff) {
        const positionEl = form.querySelector('[name="' + prefix + 'position"]');
        const customEl = form.querySelector('[name="' + prefix + 'custom_position"]');
        if (positionEl) {
          const r = this.validatePosition(positionEl.value, customEl ? customEl.value : '');
          if (!r.valid) {
            if (typeof showToast === 'function') showToast(r.message, 'error');
            positionEl.focus();
            return false;
          }
        }
      }

      // All checks passed
      return true;
    }
  };

  window.InputValidators = InputValidators;
})();

document.addEventListener("DOMContentLoaded", function () {
  const baseUrl = window.location.origin + "/online_shopping_system/";

  // Disable copy/paste on password inputs
  function disableCopyPaste(input) {
    input.addEventListener("copy", (e) => e.preventDefault());
    input.addEventListener("paste", (e) => e.preventDefault());
    input.addEventListener("cut", (e) => e.preventDefault());
  }

  // Initialize password inputs
  function initPasswordInputs() {
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach((input) => {
      if (!input.dataset.passwordInitialized) {
        disableCopyPaste(input);
        input.dataset.passwordInitialized = "true";
      }
    });
  }
  // Initialize password toggles
  function initPasswordToggles() {
    const toggleButtons = document.querySelectorAll(".password-toggle");

    toggleButtons.forEach((button) => {
      if (!button.dataset.toggleInitialized) {
        button.addEventListener("click", function () {
          const wrapper = this.closest(".password-input-wrapper");
          const input = wrapper.querySelector(
            'input[type="password"], input[type="text"]'
          );
          const img = this.querySelector("img");

          if (input.type === "password") {
            input.type = "text";
            img.src = baseUrl + "assets/images/icons/eye-slash.svg";
            disableCopyPaste(input);
          } else {
            input.type = "password";
            img.src = baseUrl + "assets/images/icons/eye.svg";
          }
        });
        button.dataset.toggleInitialized = "true";
      }
    });
  }

  // Initial setup
  initPasswordInputs();
  console.log("[Input] Disabled Copy/Paste on Password Inputs.");
  initPasswordToggles();
  console.log("[Input] Toggle Password Visibility Done.");

  // Watch for dynamically added password fields (e.g., modals)
  const observer = new MutationObserver(function (mutations) {
    mutations.forEach(function (mutation) {
      if (mutation.addedNodes.length) {
        initPasswordInputs();
        initPasswordToggles();
      }
    });
  });

  // Observe the entire document for changes
  observer.observe(document.body, {
    childList: true,
    subtree: true,
  });
});
