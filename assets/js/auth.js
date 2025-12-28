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

function validateFullname(fullname) {
  const trimmed = fullname.trim();
  if (!trimmed) return "Full name is required";
  if (trimmed.length < 3) return "Full name must be at least 3 characters long";
  if (trimmed.length > 100) return "Full name must not exceed 100 characters";
  if (!/^[a-zA-Z\s\-]+$/.test(trimmed))
    return "Full name can only contain letters, spaces, and hyphens";
  return null;
}

function validateEmail(email) {
  const trimmed = email.trim();
  if (!trimmed) return "Email is required";
  if (trimmed.length > 100) return "Email must not exceed 100 characters";
  if (!/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(trimmed))
    return "Please enter a valid email address";
  if (
    trimmed.includes("..") ||
    trimmed.startsWith(".") ||
    trimmed.endsWith(".")
  )
    return "Email address format is invalid";
  return null;
}

function validateContactNumber(contactNumber) {
  if (contactNumber && !/^[0-9]{7,15}$/.test(contactNumber))
    return "Contact number must be between 7 and 15 digits";
  return null;
}

function validateBirthDate(birthDate) {
  if (!birthDate) return null;
  const selectedDate = new Date(birthDate);
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  if (isNaN(selectedDate.getTime())) return "Please enter a valid birth date";
  if (selectedDate > today) return "Birth date cannot be in the future";
  const minDate = new Date();
  minDate.setFullYear(minDate.getFullYear() - 120);
  const maxDate = new Date();
  maxDate.setFullYear(maxDate.getFullYear() - 13);
  if (selectedDate < minDate)
    return "Birth date cannot be more than 120 years ago";
  if (selectedDate > maxDate) return "You must be at least 13 years old";
  return null;
}

function showAlert(message, type) {
  if (typeof showToast === "function") {
    showToast(message, type);
  } else {
    alert(message);
  }
}

document.addEventListener("DOMContentLoaded", function () {
  // Disable copy/paste on all password inputs
  const passwordInputs = document.querySelectorAll('input[type="password"]');
  passwordInputs.forEach((input) => {
    input.addEventListener("copy", (e) => e.preventDefault());
    input.addEventListener("paste", (e) => e.preventDefault());
    input.addEventListener("cut", (e) => e.preventDefault());
  });

  // Register form validation and AJAX submission
  const registerForm = document.getElementById("register-form");
  if (registerForm) {
    registerForm.addEventListener("submit", function (e) {
      e.preventDefault();
      const username = this.querySelector('[name="username"]')?.value || "";
      const password = this.querySelector('[name="password"]')?.value || "";
      const confirmPassword =
        this.querySelector('[name="confirm_password"]')?.value || "";
      const fullname = this.querySelector('[name="fullname"]')?.value || "";
      const email = this.querySelector('[name="email"]')?.value || "";
      const contactNumber =
        this.querySelector('[name="contact_number"]')?.value || "";
      const birthDate = this.querySelector('[name="birth_date"]')?.value || "";

      let error = validateUsername(username);
      if (error) {
        if (typeof showToast === "function") {
          showToast(error, "error");
        } else {
          alert(error);
        }
        this.querySelector('[name="username"]')?.focus();
        return;
      }

      error = validatePasswordStrength(password);
      if (error) {
        if (typeof showToast === "function") {
          showToast(error, "error");
        } else {
          alert(error);
        }
        this.querySelector('[name="password"]')?.focus();
        return;
      }

      if (password !== confirmPassword) {
        if (typeof showToast === "function") {
          showToast("Passwords do not match", "error");
        } else {
          alert("Passwords do not match");
        }
        this.querySelector('[name="confirm_password"]')?.focus();
        return;
      }

      error = validateFullname(fullname);
      if (error) {
        if (typeof showToast === "function") {
          showToast(error, "error");
        } else {
          alert(error);
        }
        this.querySelector('[name="fullname"]')?.focus();
        return;
      }

      error = validateEmail(email);
      if (error) {
        if (typeof showToast === "function") {
          showToast(error, "error");
        } else {
          alert(error);
        }
        this.querySelector('[name="email"]')?.focus();
        return;
      }

      error = validateContactNumber(contactNumber);
      if (error) {
        if (typeof showToast === "function") {
          showToast(error, "error");
        } else {
          alert(error);
        }
        this.querySelector('[name="contact_number"]')?.focus();
        return;
      }

      error = validateBirthDate(birthDate);
      if (error) {
        if (typeof showToast === "function") {
          showToast(error, "error");
        } else {
          alert(error);
        }
        this.querySelector('[name="birth_date"]')?.focus();
        return;
      }

      // Submit via AJAX
      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;
      submitBtn.disabled = true;
      submitBtn.textContent = "Registering...";

      fetch(this.action, {
        method: "POST",
        body: new FormData(this),
      })
        .then((res) => res.json())
        .then((data) => {
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;

          if (data.success) {
            // Show success message with resend verification option
            const persistentAlert = document.createElement("div");
            persistentAlert.className = "alert alert-success";
            persistentAlert.style.marginTop = "15px";
            persistentAlert.innerHTML = `
              <p>${data.message}</p>
              <form method="POST" action="${this.action.replace(
                "register",
                "resend_verification"
              )}" style="margin-top: 10px;">
                <input type="hidden" name="csrf_token" value="${
                  this.querySelector('[name="csrf_token"]').value
                }">
                <input type="hidden" name="email" value="${
                  data.registered_email
                }">
                <button type="submit" class="auth-btn" style="padding: 8px 16px; font-size: 14px;">Resend Verification Email</button>
              </form>
            `;

            // Remove any existing alert
            const existingAlert = this.parentElement.querySelector(".alert");
            if (existingAlert) {
              existingAlert.remove();
            }

            this.insertAdjacentElement("afterend", persistentAlert);
            this.reset();
            if (typeof grecaptcha !== "undefined") {
              grecaptcha.reset();
            }

            // Handle resend verification button
            const resendBtn = persistentAlert.querySelector("button");
            resendBtn.addEventListener("click", function (e) {
              e.preventDefault();
              const resendForm = this.closest("form");
              resendBtn.disabled = true;
              resendBtn.textContent = "Sending...";
              fetch(resendForm.action, {
                method: "POST",
                body: new FormData(resendForm),
              })
                .then((res) => res.json())
                .then((data) => {
                  if (typeof showToast === "function") {
                    showToast(data.message, data.success ? "success" : "error");
                  } else {
                    alert(data.message);
                  }
                  if (data.success && data.redirect) {
                    // Use global redirectWithToast function
                    redirectWithToast(data.redirect, data.message, "success");
                  } else {
                    resendBtn.disabled = false;
                    resendBtn.textContent = "Resend Verification Email";
                  }
                });
            });
          } else {
            if (window.grecaptcha) grecaptcha.reset();
            if (typeof showToast === "function") {
              showToast(data.message, "error");
            } else {
              alert(data.message);
            }
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

  // Login form AJAX submission
  const loginForm = document.querySelector('.auth-form[action*="login"]');
  if (loginForm) {
    loginForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;
      submitBtn.disabled = true;
      submitBtn.textContent = "Logging in...";

      fetch(this.action, {
        method: "POST",
        body: new FormData(this),
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            if (data.redirect) {
              // Use global redirectWithToast function
              redirectWithToast(data.redirect, data.message, "success");
            } else {
              window.location.href = "/";
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

            // Show attempts left or locked_until info when provided by the backend
            if (data.attempts_left !== undefined) {
              if (typeof showToast === "function") {
                showToast(`Attempts left: ${data.attempts_left}`, "info");
              } else {
                alert(`Attempts left: ${data.attempts_left}`);
              }
            }

            if (data.locked_until) {
              let lockedMsg = data.locked_until;
              try {
                lockedMsg = new Date(data.locked_until).toLocaleString();
              } catch (e) {}
              if (typeof showToast === "function") {
                showToast(`Account locked until ${lockedMsg}`, "error");
              } else {
                alert(`Account locked until ${lockedMsg}`);
              }
            }

            // Show resend verification if unverified email
            if (data.unverified_email) {
              const persistentAlert = document.createElement("div");
              persistentAlert.className = "alert alert-info";
              persistentAlert.style.marginTop = "15px";
              persistentAlert.innerHTML = `
                <p>${data.message}</p>
                <form method="POST" action="${this.action.replace(
                  "login",
                  "resend_verification"
                )}" style="margin-top: 10px;">
                  <input type="hidden" name="csrf_token" value="${
                    this.querySelector('[name="csrf_token"]').value
                  }">
                  <input type="hidden" name="email" value="${
                    data.unverified_email
                  }">
                  <button type="submit" class="auth-btn" style="padding: 8px 16px; font-size: 14px;">Resend Verification Email</button>
                </form>
              `;
              this.insertAdjacentElement("afterend", persistentAlert);

              // Handle resend verification form
              const resendBtn = persistentAlert.querySelector("button");
              resendBtn.addEventListener("click", function (e) {
                e.preventDefault();
                const resendForm = this.closest("form");
                resendBtn.disabled = true;
                resendBtn.textContent = "Sending...";
                fetch(resendForm.action, {
                  method: "POST",
                  body: new FormData(resendForm),
                })
                  .then((res) => res.json())
                  .then((data) => {
                    if (typeof showToast === "function") {
                      showToast(
                        data.message,
                        data.success ? "success" : "error"
                      );
                    } else {
                      alert(data.message);
                    }
                    if (data.success && data.redirect) {
                      // Use global redirectWithToast function
                      redirectWithToast(data.redirect, data.message, "success");
                    } else {
                      resendBtn.disabled = false;
                      resendBtn.textContent = "Resend Verification Email";
                    }
                  });
              });
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
  
  // Resend verification buttons (persistent alert version)
  document.addEventListener("submit", function (e) {
    if (e.target.matches('form[action*="resend_verification"]')) {
      e.preventDefault();
      const form = e.target;
      const btn = form.querySelector("button[type='submit']");
      const originalText = btn.textContent;
      btn.disabled = true;
      btn.textContent = "Sending...";

      fetch(form.action, {
        method: "POST",
        body: new FormData(form),
      })
        .then((res) => res.json())
        .then((data) => {
          btn.disabled = false;
          btn.textContent = originalText;

          // Find or create persistent alert
          let alertDiv = form.closest(".alert") || form.previousElementSibling;
          if (!alertDiv || !alertDiv.classList.contains("alert")) {
            alertDiv = document.createElement("div");
            alertDiv.className = "alert";
            form.parentElement.insertBefore(alertDiv, form);
          }
          alertDiv.className =
            "alert alert-" + (data.success ? "success" : "error");
          alertDiv.textContent = data.message;
          alertDiv.style.display = "block";

          if (data.success && data.redirect) {
            setTimeout(() => (window.location.href = data.redirect), 2000);
          }
        })
        .catch((error) => {
          btn.disabled = false;
          btn.textContent = originalText;
          if (typeof showToast === "function") {
            showToast("An error occurred. Please try again.", "error");
          } else {
            alert("An error occurred. Please try again.");
          }
        });
    }
  });
});
