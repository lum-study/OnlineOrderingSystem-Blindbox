/**
 * Admin Profile JavaScript - STANDALONE VERSION
 * Handles admin profile separately from member profile
 * This file contains ALL necessary functions for admin profile to work independently
 */

document.addEventListener("DOMContentLoaded", function () {
  const baseUrl = window.location.origin + "/online_shopping_system/";

  console.log("[Admin] Initializing admin profile module");

  // ============================================================================
  // PROFILE FORM VALIDATION (Admin-specific copy)  // ============================================================================
  function validateAdminProfileForm(form) {
    // Prefer shared validators when available
    if (window.InputValidators && typeof window.InputValidators.validateUserForm === 'function') {
      return window.InputValidators.validateUserForm(form, { isStaff: true, isAdd: false });
    }

    console.log("[Admin] Validating profile form (fallback)");

    // Get form fields
    const fullnameInput = form.querySelector('input[name="fullname"]');
    const emailInput = form.querySelector(
      'input[name="email"]:not([disabled])'
    );
    const contactNumberInput = form.querySelector(
      'input[name="contact_number"]'
    );
    const birthDateInput = form.querySelector('input[name="birth_date"]');
    const genderSelect = form.querySelector('select[name="gender"]');

    // Small helper so validation doesn't crash if toast isn't loaded
    const notify = (msg) => {
      if (typeof showToast === "function") showToast(msg, "error");
      else alert(msg);
    };

    // 1. Validate Full Name (required, min 3 chars, max 100 chars)
    if (fullnameInput) {
      const fullname = fullnameInput.value.trim();

      if (!fullname) {
        notify("Full name is required");
        fullnameInput.focus();
        return false;
      }

      if (fullname.length < 3) {
        notify("Full name must be at least 3 characters long");
        fullnameInput.focus();
        return false;
      }

      if (fullname.length > 100) {
        notify("Full name must not exceed 100 characters");
        fullnameInput.focus();
        return false;
      }

      // Match backend: letters, spaces, hyphens ONLY (no apostrophes)
      const namePattern = /^[a-zA-Z\s\-]+$/;
      if (!namePattern.test(fullname)) {
        notify("Full name can only contain letters, spaces, and hyphens");
        fullnameInput.focus();
        return false;
      }
    }

    // 2. Validate Email (required, valid format, max 100 chars)
    if (emailInput) {
      const email = emailInput.value.trim();

      if (!email) {
        notify("Email is required");
        emailInput.focus();
        return false;
      }

      if (email.length > 100) {
        notify("Email must not exceed 100 characters");
        emailInput.focus();
        return false;
      }

      // RFC 5322 compliant email regex (simplified but comprehensive)
      const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
      if (!emailPattern.test(email)) {
        notify("Please enter a valid email address");
        emailInput.focus();
        return false;
      }

      // Additional checks for common mistakes
      if (
        email.includes("..") ||
        email.startsWith(".") ||
        email.endsWith(".")
      ) {
        notify("Email address format is invalid");
        emailInput.focus();
        return false;
      }
    }

    // 3. Validate Contact Number (optional, but if provided must be valid)
    if (contactNumberInput) {
      const contactNumber = contactNumberInput.value.trim();

      if (contactNumber) {
        // Must be 7-15 digits
        if (contactNumber.length < 7 || contactNumber.length > 15) {
          notify("Contact number must be between 7 and 15 digits");
          contactNumberInput.focus();
          return false;
        }

        // Must contain only digits
        const phonePattern = /^[0-9]+$/;
        if (!phonePattern.test(contactNumber)) {
          notify("Contact number can only contain digits");
          contactNumberInput.focus();
          return false;
        }
      }
    }

    // 4. Validate Birth Date (optional, but if provided must be valid and not in future)
    if (birthDateInput) {
      const birthDate = birthDateInput.value;

      if (birthDate) {
        const selectedDate = new Date(birthDate);
        const today = new Date();
        today.setHours(0, 0, 0, 0); // Reset time to start of day

        // Check if date is valid
        if (isNaN(selectedDate.getTime())) {
          notify("Please enter a valid birth date");
          birthDateInput.focus();
          return false;
        }

        // Check if date is not in the future
        if (selectedDate > today) {
          notify("Birth date cannot be in the future");
          birthDateInput.focus();
          return false;
        }

        // Check if age is reasonable (at least 18 years old for staff, max 120 years old)
        const minDate = new Date();
        minDate.setFullYear(minDate.getFullYear() - 120);
        const maxDate = new Date();
        maxDate.setFullYear(maxDate.getFullYear() - 18);

        if (selectedDate < minDate) {
          notify("Birth date cannot be more than 120 years ago");
          birthDateInput.focus();
          return false;
        }

        if (selectedDate > maxDate) {
          notify("Staff must be at least 18 years old");
          birthDateInput.focus();
          return false;
        }
      }
    }

    // 5. Validate Gender (optional)
    if (genderSelect) {
      const gender = (genderSelect.value || "").trim();
      const allowed = ["", "male", "female", "other"];
      if (!allowed.includes(gender)) {
        notify("Invalid gender selection");
        genderSelect.focus();
        return false;
      }
    }

    console.log("[Admin] Form validation passed");
    return true; // All validations passed
  }

  // ============================================================================
  // ADMIN PROFILE UPDATE FORM  //
  // ============================================================================
  const updateProfileForm = document.getElementById("update-profile-form");
  if (updateProfileForm) {
    console.log("[Admin] Update form found");

    updateProfileForm.addEventListener("submit", function (e) {
      e.preventDefault();

      console.log("[Admin] Submit profile update");

      // Validate the form before submission
      if (!validateAdminProfileForm(this)) {
        console.log("[Admin] Validation failed");
        return;
      }

      const formData = new FormData(this);
      const action = this.getAttribute("action");

      console.log("[Admin] Sending to:", action);

      fetch(action, {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          console.log("[Admin] Response:", data);

          if (typeof showToast === "function") {
            showToast(data.message, data.success ? "success" : "error");
          } else {
            alert(data.message);
          }

          if (data.success) {
            const fullname = formData.get("fullname");
            const adminSidebarName = document.querySelector(
              ".sidebar-user-info .user-name"
            );
            if (adminSidebarName && fullname) {
              adminSidebarName.textContent = fullname;
              console.log("[Admin] Updated sidebar name:", fullname);
            }
          }
        })
        .catch((err) => {
          console.error("[Admin] Error:", err);
          if (typeof showToast === "function") {
            showToast("An error occurred", "error");
          }
        });
    });
  }

  // ============================================================================
  // ADMIN CHANGE PASSWORD MODAL & FORM
  // ============================================================================
  const passwordModal = document.getElementById("password-modal");
  const openPasswordModalBtn = document.getElementById("open-password-modal");
  const closeBtns = document.querySelectorAll(".modal-close");

  if (openPasswordModalBtn) {
    openPasswordModalBtn.addEventListener("click", function (e) {
      e.preventDefault();
      passwordModal.classList.add("active");
    });
  }
  closeBtns.forEach((btn) => {
    btn.addEventListener("click", function () {
      this.closest(".modal-overlay").classList.remove("active");
    });
  });

  // Password strength validation function
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
  const changePasswordForm = document.getElementById("change-password-form");
  if (changePasswordForm) {
    console.log("[Admin] Change password form found");

    changePasswordForm.addEventListener("submit", function (e) {
      e.preventDefault();

      console.log("[Admin] Submit password change");

      const oldPassword =
        this.querySelector('[name="old_password"]')?.value || "";
      const newPassword =
        this.querySelector('[name="new_password"]')?.value || "";
      const confirmPassword =
        this.querySelector('[name="confirm_password"]')?.value || "";

      if (!oldPassword) {
        if (typeof showToast === "function")
          showToast("Old password is required", "error");
        else alert("Old password is required");
        this.querySelector('[name="old_password"]')?.focus();
        return;
      }

      const error = validatePasswordStrength(newPassword);
      if (error) {
        if (typeof showToast === "function") showToast(error, "error");
        else alert(error);
        this.querySelector('[name="new_password"]')?.focus();
        return;
      }

      if (newPassword !== confirmPassword) {
        if (typeof showToast === "function")
          showToast("Passwords do not match", "error");
        else alert("Passwords do not match");
        this.querySelector('[name="confirm_password"]')?.focus();
        return;
      }

      const formData = new FormData(this);
      const action = this.getAttribute("action");

      fetch(action, {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          console.log("[Admin] Response:", data);

          if (typeof showToast === "function") {
            showToast(data.message, data.success ? "success" : "error");
          } else {
            alert(data.message);
          }

          if (data.success) {
            changePasswordForm.reset();
            passwordModal.classList.remove("active");
            console.log("[Admin] Password changed successfully");
          }
        })
        .catch((err) => {
          console.error("[Admin] Error:", err);
          if (typeof showToast === "function") {
            showToast("An error occurred", "error");
          }
        });
    });
  }

  // ============================================================================
  // WEBCAM & PHOTO UPLOAD INTEGRATION
  // ============================================================================
  const photoFrame = document.getElementById("photo-upload-frame");
  const photoInput = document.getElementById("photo-file-input");
  const photoSourceModal = document.getElementById("photo-source-modal");
  const photoSourceModalClose = document.getElementById(
    "photo-source-modal-close"
  );
  const webcamVideo = document.getElementById("webcam-video");
  const webcamCanvas = document.getElementById("webcam-canvas");
  const webcamContainer = document.getElementById("webcam-container");
  const captureWebcamBtn = document.getElementById("capture-webcam-btn");
  const selectFileBtn = document.getElementById("select-file-btn");

  let webcamStream = null;
  // Open photo source modal when clicking photo frame
  if (photoFrame) {
    photoFrame.addEventListener(
      "click",
      function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (photoSourceModal) {
          photoSourceModal.classList.add("active");
          startWebcam();
        }
      },
      true
    ); // Use capture phase to intercept before photo_upload.js

    // Keep drag and drop functionality
    photoFrame.addEventListener("dragover", function (e) {
      e.preventDefault();
      e.stopPropagation();
      this.classList.add("drag-over");
    });

    photoFrame.addEventListener("dragleave", function (e) {
      e.preventDefault();
      e.stopPropagation();
      this.classList.remove("drag-over");
    });

    photoFrame.addEventListener("drop", function (e) {
      e.preventDefault();
      e.stopPropagation();
      this.classList.remove("drag-over");

      const files = e.dataTransfer.files;
      if (files && files.length > 0) {
        photoInput.files = files;
        photoInput.dispatchEvent(new Event("change", { bubbles: true }));
      }
    });
  }

  // Start webcam
  function startWebcam() {
    console.log("[Admin Profile] Starting webcam");
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
      navigator.mediaDevices
        .getUserMedia({ video: { facingMode: "user" }, audio: false })
        .then(function (stream) {
          webcamStream = stream;
          if (webcamVideo) {
            webcamVideo.srcObject = stream;
            webcamContainer.style.display = "block";
            captureWebcamBtn.style.display = "inline-block";
          }
        })
        .catch(function (err) {
          console.error("[Admin Profile] Webcam error:", err);
          if (typeof showToast === "function") {
            showToast(
              "Unable to access webcam. Please select a file instead.",
              "error"
            );
          }
          webcamContainer.style.display = "none";
          captureWebcamBtn.style.display = "none";
        });
    } else {
      console.log("[Admin Profile] Webcam not supported");
      if (typeof showToast === "function") {
        showToast("Webcam not supported on this device", "error");
      }
      webcamContainer.style.display = "none";
      captureWebcamBtn.style.display = "none";
    }
  }

  // Stop webcam
  function stopWebcam() {
    if (webcamStream) {
      webcamStream.getTracks().forEach((track) => track.stop());
      webcamStream = null;
      if (webcamVideo) {
        webcamVideo.srcObject = null;
      }
    }
  }

  // Close photo source modal
  function closePhotoSourceModal() {
    if (photoSourceModal) {
      photoSourceModal.classList.remove("active");
      stopWebcam();
      if (webcamContainer) webcamContainer.style.display = "none";
      if (captureWebcamBtn) captureWebcamBtn.style.display = "none";
    }
  }
  if (photoSourceModalClose) {
    photoSourceModalClose.addEventListener("click", closePhotoSourceModal);
  }

  // Handle webcam capture - convert blob to file and trigger input change
  function handleWebcamCapture(blob) {
    console.log("[Admin Profile] Webcam capture successful, size:", blob.size);
    const file = new File([blob], "webcam-capture.jpg", { type: "image/jpeg" });

    // Create a DataTransfer to set files on input
    const dataTransfer = new DataTransfer();
    dataTransfer.items.add(file);
    photoInput.files = dataTransfer.files;

    // Trigger change event to let photo_upload.js handle it
    photoInput.dispatchEvent(new Event("change", { bubbles: true }));
  }

  // Capture from webcam
  if (captureWebcamBtn) {
    captureWebcamBtn.addEventListener("click", function () {
      console.log("[Admin Profile] Capturing from webcam");
      if (webcamVideo && webcamCanvas) {
        const context = webcamCanvas.getContext("2d");
        webcamCanvas.width = webcamVideo.videoWidth;
        webcamCanvas.height = webcamVideo.videoHeight;
        context.drawImage(webcamVideo, 0, 0);

        webcamCanvas.toBlob(
          function (blob) {
            if (blob) {
              closePhotoSourceModal();
              // Let photo_upload.js handle it via file input change event
              if (typeof handleWebcamCapture === "function") {
                handleWebcamCapture(blob);
              } else {
                console.error("[Admin Profile] handleWebcamCapture not defined yet");
              }
            }
          },
          "image/jpeg",
          0.95
        );
      }
    });
  } // Select file button
  if (selectFileBtn) {
    selectFileBtn.addEventListener("click", function () {
      closePhotoSourceModal();
      if (photoInput) {
        photoInput.click();
      }
    });
  }

  console.log("[Admin] Profile handlers initialized");
});
