// Validation functions
function validateUsername(username) {
  if (!username || username.trim().length < 3)
    return "Username must be at least 3 characters";
  if (username.length > 50) return "Username must not exceed 50 characters";
  if (!/^[a-zA-Z0-9_]+$/.test(username))
    return "Username can only contain letters, numbers, and underscores";
  return null;
}

function validatePasswordStrength(password) {
  if (!password || password.length < 8)
    return "Password must be at least 8 characters";
  if (!/[A-Z]/.test(password))
    return "Password must contain at least one uppercase letter";
  if (!/[a-z]/.test(password))
    return "Password must contain at least one lowercase letter";
  if (!/[0-9]/.test(password))
    return "Password must contain at least one number";
  if (!/[^A-Za-z0-9]/.test(password))
    return "Password must contain at least one special character";
  return null;
}

function showAlert(message, type) {
  if (typeof showToast === "function") {
    showToast(message, type);
  } else {
    alert(message);
  }
}

function hideAlert() {
  // No longer needed since we use toast, but keeping for backward compatibility
}

document.addEventListener("DOMContentLoaded", function () {
  // Admin login form AJAX submission
  const adminLoginForm = document.querySelector(
    '.auth-form[action*="admin/login"]'
  );
  if (adminLoginForm) {
    console.log("Admin login form found");
    adminLoginForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;
      submitBtn.disabled = true;
      submitBtn.textContent = "Logging in...";

      console.log("Admin login form submitted");
      console.log("Form action:", this.action);

      fetch(this.action, {
        method: "POST",
        body: new FormData(this),
      })
        .then((res) => {
          console.log("Response status:", res.status);
          return res.json();
        })
        .then((data) => {
          console.log("Response data:", data);

          if (data.success) {
            if (data.redirect) {
              redirectWithToast(data.redirect, data.message, "success");
            } else {
              window.location.href = "/admin/login";
            }
          } else {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            if (window.grecaptcha) grecaptcha.reset();

            if (typeof showToast === "function") {
              showToast(data.message, "error");
            } else {
              alert(data.message);
            }
          }
        })
        .catch((error) => {
          if (window.grecaptcha) grecaptcha.reset();
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;
          if (typeof showToast === "function") {
            showToast("An error occurred. Please try again.", "error");
          } else {
            alert("An error occurred. Please try again.");
          }
        });
    });
  } else {
    console.log("Admin login form not found");
  }

  // Admin forgot password form AJAX submission
  const adminForgotForm = document.getElementById("admin-forgot-form");
  if (adminForgotForm) {
    adminForgotForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;
      submitBtn.disabled = true;
      submitBtn.textContent = "Sending...";

      fetch(this.action, {
        method: "POST",
        body: new FormData(this),
      })
        .then((res) => res.json())
        .then((data) => {
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;

          showToast(data.message, data.success ? "success" : "error");

          if (data.success) {
            this.reset();
          }
        })
        .catch((error) => {
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;
          showToast("An error occurred. Please try again.", "error");
        });
    });
  }

  // Admin reset password form AJAX submission
  const adminResetForm = document.getElementById("admin-reset-form");
  if (adminResetForm) {
    adminResetForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const password = this.querySelector('[name="password"]').value;
      const confirmPassword = this.querySelector('[name="confirm_password"]').value;

      if (password !== confirmPassword) {
        showToast("Passwords do not match", "error");
        return;
      }

      const passwordError = validatePasswordStrength(password);
      if (passwordError) {
        showToast(passwordError, "error");
        return;
      }

      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;
      submitBtn.disabled = true;
      submitBtn.textContent = "Resetting...";

      fetch(this.action, {
        method: "POST",
        body: new FormData(this),
      })
        .then((res) => res.json())
        .then((data) => {
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;

          showToast(data.message, data.success ? "success" : "error");

          if (data.success && data.redirect) {
            setTimeout(() => (window.location.href = data.redirect), 2000);
          }
        })
        .catch((error) => {
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;
          showToast("An error occurred. Please try again.", "error");
        });
    });
  }

  // Forgot password form AJAX submission
  const forgotForm = document.querySelector(
    '.auth-form[action*="request_reset"]'
  );
  if (forgotForm) {
    forgotForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;
      submitBtn.disabled = true;
      submitBtn.textContent = "Sending...";

      fetch(this.action, {
        method: "POST",
        body: new FormData(this),
      })
        .then((res) => res.json())
        .then((data) => {
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;

          if (typeof showToast === "function") {
            showToast(data.message, data.success ? "success" : "error");
          } else {
            alert(data.message);
          }

          if (data.success) {
            this.reset();
          }
        })
        .catch((error) => {
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;
          if (typeof showToast === "function") {
            showToast("An error occurred. Please try again.", "error");
          } else {
            alert("An error occurred. Please try again.");
          }
        });
    });
  }

  // Reset password form AJAX submission
  const resetForm = document.querySelector(
    '.auth-form[action*="reset_password"]'
  );
  if (resetForm) {
    resetForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;
      submitBtn.disabled = true;
      submitBtn.textContent = "Resetting...";

      fetch(this.action, {
        method: "POST",
        body: new FormData(this),
      })
        .then((res) => res.json())
        .then((data) => {
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;

          if (typeof showToast === "function") {
            showToast(data.message, data.success ? "success" : "error");
          } else {
            alert(data.message);
          }

          if (data.success && data.redirect) {
            setTimeout(() => (window.location.href = data.redirect), 2000);
          }
        })
        .catch((error) => {
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;
          if (typeof showToast === "function") {
            showToast("An error occurred. Please try again.", "error");
          } else {
            alert("An error occurred. Please try again.");
          }
        });
    });
  }
});
