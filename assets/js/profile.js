/**
 * MEMBER Profile JavaScript - MEMBER ONLY
 * This file is for MEMBER profiles only (/views/pages/member/profile.php)
 * Admin profiles use admin_profile.js instead
 */

// Profile page
document.addEventListener("DOMContentLoaded", function () {
  const baseUrl = window.location.origin + "/online_shopping_system/";
  console.log("[Profile] Initializing member profile module");

  const navItems = document.querySelectorAll(".profile-nav-item");
  const sections = document.querySelectorAll(".profile-section");

  navItems.forEach((item) => {
    item.addEventListener("click", function (e) {
      e.preventDefault();
      const targetSection = this.getAttribute("data-section");

      navItems.forEach((nav) => nav.classList.remove("active"));
      this.classList.add("active");

      sections.forEach((section) => section.classList.remove("active"));
      document.getElementById(targetSection).classList.add("active");

      const newUrl = new URL(window.location);
      newUrl.searchParams.set("section", targetSection);
      window.history.pushState({}, "", newUrl);
    });
  });

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
    console.log("[Profile] Starting webcam");
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
          console.error("[Profile] Webcam error:", err);
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
      console.log("[Profile] Webcam not supported");
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
    console.log("[Profile] Webcam capture successful, size:", blob.size);
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
      console.log("[Profile] Capturing from webcam");
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
                console.error("[Profile] handleWebcamCapture not defined yet");
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

  // Modal functionality
  const usernameBtn = document.getElementById("open-username-modal");
  const passwordBtn = document.getElementById("open-password-modal");
  const addressBtn = document.getElementById("open-address-modal");
  const usernameModal = document.getElementById("username-modal");
  const passwordModal = document.getElementById("password-modal");
  const addAddressModal = document.getElementById("add-address-modal");
  const editAddressModal = document.getElementById("edit-address-modal");
  const closeBtns = document.querySelectorAll(".modal-close");

  if (usernameBtn) {
    usernameBtn.addEventListener("click", function (e) {
      e.preventDefault();
      if (usernameModal) usernameModal.classList.add("active");
    });
  }

  if (passwordBtn) {
    passwordBtn.addEventListener("click", function (e) {
      e.preventDefault();
      passwordModal.classList.add("active");
    });
  }

  if (addressBtn) {
    addressBtn.addEventListener("click", function (e) {
      e.preventDefault();
      addAddressModal.classList.add("active");
    });
  }

  closeBtns.forEach((btn) => {
    btn.addEventListener("click", function () {
      this.closest(".modal-overlay").classList.remove("active");
    });
  });

  // Prevent closing modals when clicking outside
  [usernameModal, passwordModal, addAddressModal, editAddressModal].forEach(
    (modal) => {
      if (modal) {
        modal.addEventListener("click", function (e) {
          if (e.target === this) {
            e.stopPropagation();
          }
        });
      }
    }
  );

  // Delete Account Modal
  const deleteAccountBtn = document.getElementById("open-delete-account-modal");
  const deleteAccountModal = document.getElementById("delete-account-modal");

  if (deleteAccountBtn) {
    deleteAccountBtn.addEventListener("click", function (e) {
      e.preventDefault();
      deleteAccountModal.classList.add("active");
    });
  }

  // AJAX Form Handlers
  const updateProfileForm = document.getElementById("update-profile-form");
  if (updateProfileForm) {
    console.log("[Profile] Update form found");

    updateProfileForm.addEventListener("submit", function (e) {
      e.preventDefault();

      console.log("[Profile] Submit profile update");

      // Validate the form before submission
      if (!validateProfileForm(this)) {
        console.log("[Profile] Validation failed");
        return; // Stop if validation fails
      }

      console.log("[Profile] Validation passed");

      const formData = new FormData(this);
      const fullname = formData.get("fullname");
      console.log("[Profile] Fullname:", fullname);

      fetch(baseUrl + "profile/update", { method: "POST", body: formData })
        .then((res) => res.json())
        .then((data) => {
          console.log("[Profile] Response:", data);

          showToast(data.message, data.success ? "success" : "error");

          if (data.success) {
            // Update profile page sidebar name
            const sidebarName = document.querySelector(".profile-user-name");
            if (sidebarName && fullname) {
              sidebarName.textContent = fullname;
            }

            // Update admin sidebar (if present) when staff updates their own profile
            if (typeof window.updateAdminSidebarFromUser === 'function') {
              // Prefer server-provided user object when available
              const updatedUser = data.data || data.user || {};

              // Ensure fullname is present
              if (!updatedUser.fullname && fullname) updatedUser.fullname = fullname;

              // Attempt to obtain position from the form if present
              try {
                const pos = new FormData(updateProfileForm).get('position');
                if (!updatedUser.position && pos) updatedUser.position = pos;
              } catch (e) {
                // ignore
              }

              // Include profile photo if server returned it
              if (!updatedUser.profile_photo && (data.profile_photo || data.profile_photo_file)) {
                updatedUser.profile_photo = data.profile_photo || data.profile_photo_file;
              }

              try {
                window.updateAdminSidebarFromUser(updatedUser);
              } catch (err) {
                console.error('[Profile] updateAdminSidebarFromUser error:', err);
              }
            }
          }
        })
        .catch((err) => {
          console.error("[Profile] Error:", err);
          showToast("An error occurred", "error");
        });
    });
  } else {
    console.log("[Profile] Update form not found");
  }

  // Profile Form Validation Function
  function validateProfileForm(form) {
    // Use shared validators when available
    if (window.InputValidators && typeof window.InputValidators.validateUserForm === 'function') {
      return window.InputValidators.validateUserForm(form, { isStaff: false, isAdd: false });
    }

    // Fallback to original validation if InputValidators is not loaded
    console.log('[Profile] Validating form (fallback)');

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

    // 1. Validate Full Name (required, min 3 chars, max 100 chars)
    if (fullnameInput) {
      const fullname = fullnameInput.value.trim();

      if (!fullname) {
        showToast('Full name is required', 'error');
        fullnameInput.focus();
        return false;
      }

      if (fullname.length < 3) {
        showToast('Full name must be at least 3 characters long', 'error');
        fullnameInput.focus();
        return false;
      }

      if (fullname.length > 100) {
        showToast('Full name must not exceed 100 characters', 'error');
        fullnameInput.focus();
        return false;
      }

      // Match backend: letters, spaces, hyphens ONLY (no apostrophes)
      const namePattern = /^[a-zA-Z\s\-]+$/;
      if (!namePattern.test(fullname)) {
        showToast('Full name can only contain letters, spaces, and hyphens', 'error');
        fullnameInput.focus();
        return false;
      }
    }

    // 2. Validate Email (required, valid format, max 100 chars)
    if (emailInput) {
      const email = emailInput.value.trim();

      if (!email) {
        showToast('Email is required', 'error');
        emailInput.focus();
        return false;
      }

      if (email.length > 100) {
        showToast('Email must not exceed 100 characters', 'error');
        emailInput.focus();
        return false;
      }

      // RFC 5322 compliant email regex (simplified but comprehensive)
      const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
      if (!emailPattern.test(email)) {
        showToast('Please enter a valid email address', 'error');
        emailInput.focus();
        return false;
      }

      // Additional checks for common mistakes
      if (
        email.includes('..') ||
        email.startsWith('.') ||
        email.endsWith('.')
      ) {
        showToast('Email address format is invalid', 'error');
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
          showToast('Contact number must be between 7 and 15 digits', 'error');
          contactNumberInput.focus();
          return false;
        }

        // Must contain only digits
        const phonePattern = /^[0-9]+$/;
        if (!phonePattern.test(contactNumber)) {
          showToast('Contact number can only contain digits', 'error');
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
          showToast('Please enter a valid birth date', 'error');
          birthDateInput.focus();
          return false;
        }

        // Check if date is not in the future
        if (selectedDate > today) {
          showToast('Birth date cannot be in the future', 'error');
          birthDateInput.focus();
          return false;
        }

        // Check if age is reasonable (at least 13 years old, max 120 years old)
        const minDate = new Date();
        minDate.setFullYear(minDate.getFullYear() - 120);
        const maxDate = new Date();
        maxDate.setFullYear(maxDate.getFullYear() - 13);

        if (selectedDate < minDate) {
          showToast('Birth date cannot be more than 120 years ago', 'error');
          birthDateInput.focus();
          return false;
        }

        if (selectedDate > maxDate) {
          showToast('You must be at least 13 years old', 'error');
          birthDateInput.focus();
          return false;
        }
      }
    }

    // 5. Validate Gender (optional, no specific validation needed as it's a select dropdown)
    // Gender validation is handled by the dropdown itself

    console.log('[Profile] Form validation passed');
    return true; // All validations passed
  }

  // --------------------------------------------------------------------------
  // Address Form Validation
  // --------------------------------------------------------------------------
  function validateAddressForm(form) {
    if (!form) return false;

    const receiverName = (
      form.querySelector('[name="receiver_name"]')?.value || ""
    ).trim();
    const phonePrefix = (
      form.querySelector('[name="phone_number_prefix"]')?.value || "+60"
    ).trim();
    const phoneNumber = (
      form.querySelector('[name="phone_number"]')?.value || ""
    ).trim();
    const email = (form.querySelector('[name="email"]')?.value || "").trim();

    // Address fields can be named either addr_* (add/edit) or edit_addr_* in admin-style forms.
    const address = (
      form.querySelector('[name="addr_address"]')?.value || ""
    ).trim();
    const city = (form.querySelector('[name="addr_city"]')?.value || "").trim();
    const state = (
      form.querySelector('[name="addr_state"]')?.value || ""
    ).trim();
    const postalCode = (
      form.querySelector('[name="addr_postal_code"]')?.value || ""
    ).trim();
    const country = (
      form.querySelector('[name="addr_country"]')?.value || ""
    ).trim();

    if (!receiverName || receiverName.length < 3 || receiverName.length > 255) {
      showToast("Receiver name must be 3-255 characters", "error");
      form.querySelector('[name="receiver_name"]')?.focus();
      return false;
    }
    // Match backend: letters, spaces, hyphens ONLY (no apostrophes)
    if (!/^[a-zA-Z\s\-]+$/.test(receiverName)) {
      showToast(
        "Receiver name can only contain letters, spaces, and hyphens",
        "error"
      );
      form.querySelector('[name="receiver_name"]')?.focus();
      return false;
    }

    if (!phoneNumber || !/^[0-9]{7,15}$/.test(phoneNumber)) {
      showToast("Phone number must be 7-15 digits", "error");
      form.querySelector('[name="phone_number"]')?.focus();
      return false;
    }

    // Optional email
    if (email && email.length > 255) {
      showToast("Email must not exceed 255 characters", "error");
      form.querySelector('[name="email"]')?.focus();
      return false;
    }
    if (
      email &&
      !/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(email)
    ) {
      showToast("Invalid email format", "error");
      form.querySelector('[name="email"]')?.focus();
      return false;
    }

    if (!address || address.length < 10 || address.length > 255) {
      showToast("Address must be 10-255 characters", "error");
      form.querySelector('[name="addr_address"]')?.focus();
      return false;
    }

    if (
      !city ||
      city.length < 2 ||
      city.length > 100 ||
      !/^[a-zA-Z\s]+$/.test(city)
    ) {
      showToast(
        "City must be 2-100 characters and contain letters/spaces only",
        "error"
      );
      form.querySelector('[name="addr_city"]')?.focus();
      return false;
    }

    if (
      !state ||
      state.length < 2 ||
      state.length > 100 ||
      !/^[a-zA-Z\s]+$/.test(state)
    ) {
      showToast(
        "State must be 2-100 characters and contain letters/spaces only",
        "error"
      );
      form.querySelector('[name="addr_state"]')?.focus();
      return false;
    }

    if (
      !postalCode ||
      postalCode.length < 3 ||
      postalCode.length > 20 ||
      !/^[a-zA-Z0-9\s\-]+$/.test(postalCode)
    ) {
      showToast("Postal code must be 3-20 characters", "error");
      form.querySelector('[name="addr_postal_code"]')?.focus();
      return false;
    }

    const allowedCountries = ["MY", "SG", "ID", "TH"];
    if (!country || !allowedCountries.includes(country)) {
      showToast("Invalid country selected", "error");
      form.querySelector('[name="addr_country"]')?.focus();
      return false;
    }

    // Ensure prefix is present (no strict validation needed)
    if (!phonePrefix) {
      showToast("Please select a phone prefix", "error");
      form.querySelector('[name="phone_number_prefix"]')?.focus();
      return false;
    }

    return true;
  }

  const addAddressForm = document.getElementById("add-address-form");
  if (addAddressForm) {
    addAddressForm.addEventListener("submit", function (e) {
      e.preventDefault();

      if (!validateAddressForm(this)) return;
      const context = addAddressModal.dataset.context;

      fetch(baseUrl + "profile/address/add", {
        method: "POST",
        body: new FormData(this),
      })
        .then((res) => res.json())
        .then((data) => {
          if (!data.success) {
            showToast(data.message, "error");
            return;
          }
          showToast(data.message, "success");
          if (data.success) {
            addAddressModal.classList.remove("active");
            this.reset();

            console.log("[Profile] Address added, updating UI");

            if (typeof context !== "undefined" && context == "checkout") {
              const addr = data.address;
              const $addressCard = $(".address-card-body");
              $addressCard.find(".address-value").each(function () {
                const $field = $(this);
                const label = $field
                  .closest(".address-field")
                  .find(".address-label")
                  .text()
                  .toLowerCase();

                if (label.includes("name")) {
                  $field.text(addr.receiver_name);
                } else if (label.includes("email")) {
                  $field.text(addr.email || "");
                } else if (label.includes("phone")) {
                  $field.text(addr.phone);
                } else if (
                  label.includes("street") ||
                  label.includes("address")
                ) {
                  $field.text(addr.address);
                } else if (label.includes("city")) {
                  $field.text(addr.city);
                } else if (label.includes("state")) {
                  $field.text(addr.state);
                } else if (label.includes("postal")) {
                  $field.text(addr.postal_code);
                } else if (label.includes("country")) {
                  $field.text(addr.country);
                }
              });
              $("#selected-address-id").val(addr.id);

              const modalContent = document.querySelector(
                "#select-address-modal .modal-content"
              );
              // Create new address item
              const mappedAddress = {
                address_id: addr.id,
                receiver_name: addr.receiver_name,
                email: addr.email,
                phone_number: addr.phone,
                address: addr.address,
                city: addr.city,
                state: addr.state,
                postal_code: addr.postal_code,
                country: addr.country
              };
              const addressItem = document.createElement("div");
              addressItem.className = "address-item";
              addressItem.innerHTML = `
                <div class="address-header">
                    <strong>${addr.receiver_name}</strong>
                    <button type="button" class="address-select-btn" data-address='${JSON.stringify(
                mappedAddress
              )}'>Select</button>
                </div>
                <div class="address-details">
                    <p>${addr.phone}</p>
                    ${addr.email ? `<p>${addr.email}</p>` : ""}
                    <p>${addr.address}</p>
                    <p>${addr.city}, ${addr.state} ${addr.postal_code}</p>
                    <p>${addr.country}</p>
                </div>
              `;
              modalContent.appendChild(addressItem);
              return;
            }
            const emptyState = document.querySelector(
              "#address-book .empty-state"
            );
            if (emptyState) emptyState.remove();
            if (data.address.is_default) {
              const prevDefault = document.querySelector(".address-badge");
              if (prevDefault) {
                const prevItem = prevDefault.closest(".address-item");
                prevDefault.remove();
                const setDefaultBtn = document.createElement("button");
                setDefaultBtn.type = "button";
                setDefaultBtn.className = "address-default-btn";
                setDefaultBtn.setAttribute(
                  "data-address-id",
                  prevItem
                    .querySelector(".address-delete-btn")
                    .getAttribute("data-address-id")
                );
                setDefaultBtn.setAttribute(
                  "onclick",
                  "setDefaultAddress(this)"
                );
                setDefaultBtn.textContent = "Set Default";
                prevItem
                  .querySelector('div[style*="display: flex"]')
                  .insertBefore(
                    setDefaultBtn,
                    prevItem.querySelector('div[style*="display: flex"]')
                      .firstChild
                  );
              }
            }
            let addressList = document.querySelector(".address-list");
            if (!addressList) {
              addressList = document.createElement("div");
              addressList.className = "address-list";
              document
                .querySelector(
                  "#address-book .profile-card, #address-book .admin-card"
                )
                .appendChild(addressList);
            }
            const addr = data.address;
            const addrId = addr.address_id || addr.id;
            const phoneNum = addr.phone_number || addr.phone;
            const addressItem = document.createElement("div");
            addressItem.className = "address-item";
            addressItem.innerHTML = `<div class="address-header"><strong>${addr.receiver_name
              }</strong>${addr.is_default
                ? '<span class="address-badge">Default</span>'
                : ""
              }</div><div class="address-details"><p>${phoneNum}</p>${addr.email ? `<p>${addr.email}</p>` : ""
              }<p>${addr.address}</p><p>${addr.city}, ${addr.state} ${addr.postal_code
              }</p><p>${addr.country
              }</p></div><div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">${!addr.is_default
                ? `<button type="button" class="address-default-btn" data-address-id="${addrId}" onclick="setDefaultAddress(this)">Set Default</button>`
                : ""
              }<button type="button" class="address-default-btn" data-address-id="${addrId}" data-receiver="${addr.receiver_name
              }" data-phone="${phoneNum}" data-email="${addr.email || ""
              }" data-address="${addr.address}" data-city="${addr.city
              }" data-state="${addr.state}" data-postal="${addr.postal_code
              }" data-country="${addr.country}" data-default="${addr.is_default ? "1" : "0"
              }" onclick="openEditAddressModal(this)">Edit</button><button type="button" class="address-delete-btn" data-address-id="${addrId}" onclick="openDeleteAddressModal(this)">Delete</button></div>`;
            addr.is_default
              ? addressList.insertBefore(addressItem, addressList.firstChild)
              : addressList.appendChild(addressItem);
          }
        })
        .catch((err) => console.error("Error:", err));
    });
  }
  const editAddressForm = document.getElementById("edit-address-form");
  if (editAddressForm) {
    editAddressForm.addEventListener("submit", function (e) {
      e.preventDefault();

      if (!validateAddressForm(this)) return;

      const defaultCheckbox = document.getElementById("edit_is_default");
      if (defaultCheckbox && defaultCheckbox.disabled)
        defaultCheckbox.disabled = false;

      const formData = new FormData(this);
      fetch(baseUrl + "profile/address/edit", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          showToast(data.message, data.success ? "success" : "error");
          if (data.success) {
            closeEditAddressModal();

            // Update the address item in the UI without reloading
            const addressId = formData.get("address_id");
            const addr = data.address;
            const addressItem = document
              .querySelector(`[data-address-id="${addressId}"]`)
              ?.closest(".address-item");

            if (addressItem) {
              const phoneNum = addr.phone_number || addr.phone;

              // Update details
              const detailsDiv = addressItem.querySelector(".address-details");
              if (detailsDiv) {
                detailsDiv.innerHTML = `<p>${phoneNum}</p>${addr.email ? `<p>${addr.email}</p>` : ""
                  }<p>${addr.address}</p><p>${addr.city}, ${addr.state} ${addr.postal_code
                  }</p><p>${addr.country}</p>`;
              }

              // Update edit button data attributes
              const editBtn = addressItem.querySelector(
                '[onclick*="openEditAddressModal"]'
              );
              if (editBtn) {
                editBtn.setAttribute("data-receiver", addr.receiver_name);
                editBtn.setAttribute("data-phone", phoneNum);
                editBtn.setAttribute("data-email", addr.email || "");
                editBtn.setAttribute("data-address", addr.address);
                editBtn.setAttribute("data-city", addr.city);
                editBtn.setAttribute("data-state", addr.state);
                editBtn.setAttribute("data-postal", addr.postal_code);
                editBtn.setAttribute("data-country", addr.country);
                editBtn.setAttribute(
                  "data-default",
                  addr.is_default ? "1" : "0"
                );
              }

              // Update receiver name
              const headerStrong = addressItem.querySelector(
                ".address-header strong"
              );
              if (headerStrong) {
                headerStrong.textContent = addr.receiver_name;
              }
            }
          }
        })
        .catch((err) => console.error("Error:", err));
    });
  }

  // Username validation function
  function validateUsername(username) {
    if (!username || username.trim().length < 3)
      return "Username must be at least 3 characters";
    if (username.length > 50) return "Username must not exceed 50 characters";
    if (!/^[a-zA-Z0-9_]+$/.test(username))
      return "Username can only contain letters, numbers, and underscores";
    return null;
  }

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

  const usernameForm = document.getElementById("change-username-form");
  if (usernameForm) {
    usernameForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const form = this;
      const submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn) submitBtn.disabled = true;

      const newUsername = (
        form.querySelector('[name="new_username"]')?.value || ""
      ).trim();
      const currentUsername = (
        form.querySelector("input[disabled]")?.value || ""
      ).trim();

      if (newUsername === currentUsername) {
        showToast("New username is the same as current username", "error");
        form.querySelector('[name="new_username"]')?.focus();
        if (submitBtn) submitBtn.disabled = false;
        return;
      }

      const error = validateUsername(newUsername);
      if (error) {
        showToast(error, "error");
        form.querySelector('[name="new_username"]')?.focus();
        if (submitBtn) submitBtn.disabled = false;
        return;
      }

      fetch(baseUrl + "profile/change-username", {
        method: "POST",
        body: new FormData(form),
      })
        .then((res) => res.json())
        .then((data) => {
          showToast(data.message, data.success ? "success" : "error");
          if (data.success) {
            // Update UI username displays safely (do not replace element with HTML)
            const currentUsernameEl = document.getElementById('current_username');
            const newUsername = (data && data.username) ? data.username : (usernameInput ? usernameInput.value : '');
            if (currentUsernameEl) {
                if ('value' in currentUsernameEl) {
                    currentUsernameEl.value = newUsername;
                } else {
                    currentUsernameEl.textContent = newUsername;
                }
            }

            // Update other possible username displays (header/profile dropdown)
            const possibleSelectors = ['.profile-username', '.user-dropdown .username', '#header-username', '.nav-username'];
            possibleSelectors.forEach(sel => {
                const el = document.querySelector(sel);
                if (el) el.textContent = newUsername;
            });

            // Update the Change Username modal's current username input if present
            const modalCurrentUsername = document.getElementById('modal-current-username');
            if (modalCurrentUsername) {
                if ('value' in modalCurrentUsername) modalCurrentUsername.value = newUsername;
                else modalCurrentUsername.textContent = newUsername;
            }

            // Reset the new username input field
            const newUsernameInput = form.querySelector('[name="new_username"]');
            if (newUsernameInput) newUsernameInput.value = "";

            // Close the modal safely (guard element lookup)
            const usernameModalEl = document.getElementById("username-modal");
            if (usernameModalEl && usernameModalEl.classList.contains("active")) {
              usernameModalEl.classList.remove("active");
            }
          }
        })
        .catch((err) => {
          console.error("Error:", err);
          showToast("An error occurred. Please try again.", "error");
        })
        .finally(() => {
          if (submitBtn) submitBtn.disabled = false;
        });
    });
  }

  const passwordForm = document.getElementById("change-password-form");
  if (passwordForm) {
    passwordForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const oldPassword =
        this.querySelector('[name="old_password"]')?.value || "";
      const newPassword =
        this.querySelector('[name="new_password"]')?.value || "";
      const confirmPassword =
        this.querySelector('[name="confirm_password"]')?.value || "";

      if (!oldPassword) {
        showToast("Old password is required", "error");
        this.querySelector('[name="old_password"]')?.focus();
        return;
      }

      const error = validatePasswordStrength(newPassword);
      if (error) {
        showToast(error, "error");
        this.querySelector('[name="new_password"]')?.focus();
        return;
      }

      if (newPassword !== confirmPassword) {
        showToast("Passwords do not match", "error");
        this.querySelector('[name="confirm_password"]')?.focus();
        return;
      }

      fetch(baseUrl + "profile/change-password", {
        method: "POST",
        body: new FormData(this),
      })
        .then((res) => res.json())
        .then((data) => {
          showToast(data.message, data.success ? "success" : "error");
          if (data.success) {
            passwordModal.classList.remove("active");
            this.reset();
          }
        })
        .catch((err) => console.error("Error:", err));
    });
  }

  const deleteAddressForm = document.getElementById("delete-address-form");
  if (deleteAddressForm) {
    deleteAddressForm.addEventListener("submit", function (e) {
      e.preventDefault();
      const formData = new FormData(this);
      fetch(baseUrl + "profile/address/delete", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          showToast(data.message, data.success ? "success" : "error");
          if (data.success) {
            closeDeleteAddressModal();
            const addressItem = document
              .querySelector(
                `[data-address-id="${formData.get("address_id")}"]`
              )
              .closest(".address-item");
            if (addressItem) addressItem.remove();

            const addressList = document.querySelector(".address-list");
            if (addressList && addressList.children.length === 0) {
              const emptyState = document.createElement("p");
              emptyState.className = "empty-state";
              emptyState.textContent = "No addresses saved";
              addressList.parentNode.appendChild(emptyState);
              addressList.remove();
            }
          }
        })
        .catch((err) => console.error("Error:", err));
    });
  }
  const deleteAccountForm = document.getElementById("delete-account-form");
  if (deleteAccountForm) {
    deleteAccountForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const password =
        this.querySelector('[name="delete_password"]')?.value || "";
      if (!password || password.length < 8) {
        showToast("Please enter your password to confirm", "error");
        this.querySelector('[name="delete_password"]')?.focus();
        return;
      } fetch(baseUrl + "profile/delete-account", {
        method: "POST",
        body: new FormData(this),
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success && data.redirect) {
            redirectWithToast(data.redirect, data.message, "success");
          } else {
            showToast(data.message, "error");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          showToast("An error occurred. Please try again.", "error");
        });
    });
  }
});

// Edit Address Modal Functions
function openEditAddressModal(btn) {
  const addressId = btn.getAttribute("data-address-id");
  const receiver = btn.getAttribute("data-receiver");
  const phone = btn.getAttribute("data-phone");
  const email = btn.getAttribute("data-email");
  const address = btn.getAttribute("data-address");
  const city = btn.getAttribute("data-city");
  const state = btn.getAttribute("data-state");
  const postal = btn.getAttribute("data-postal");
  const country = btn.getAttribute("data-country");
  const isDefault = btn.getAttribute("data-default") === "1";

  const [prefix, number] = phone.match(/^(\+\d+)-(.+)$/)
    ? phone.match(/^(\+\d+)-(.+)$/).slice(1)
    : ["+60", phone];

  document.getElementById("edit-address-id").value = addressId;
  document.getElementById("edit_receiver_name").value = receiver;
  document.getElementById("edit_phone_number_prefix").value = prefix;
  document.getElementById("edit_phone_number").value = number;
  document.getElementById("edit_email").value = email;
  document.getElementById("edit_addr_address").value = address;
  document.getElementById("edit_addr_city").value = city;
  document.getElementById("edit_addr_state").value = state;
  document.getElementById("edit_addr_postal_code").value = postal;
  document.getElementById("edit_addr_country").value = country;
  const defaultCheckbox = document.getElementById("edit_is_default");
  defaultCheckbox.checked = isDefault;
  defaultCheckbox.disabled = isDefault;

  document.getElementById("edit-address-modal").classList.add("active");
}

function closeEditAddressModal() {
  document.getElementById("edit-address-modal").classList.remove("active");
}

// Delete Address Modal Functions
function openDeleteAddressModal(btn) {
  const addressId = btn.getAttribute("data-address-id");
  document.getElementById("delete-address-id").value = addressId;
  document.getElementById("delete-address-modal").classList.add("active");
}

function closeDeleteAddressModal() {
  document.getElementById("delete-address-modal").classList.remove("active");
}

function closeDeleteAccountModal() {
  document.getElementById("delete-account-modal").classList.remove("active");
}

function setDefaultAddress(btn) {
  const addressId = btn.getAttribute("data-address-id");
  const baseUrl = window.location.origin + "/online_shopping_system/";
  const csrfToken = document.querySelector('[name="csrf_token"]').value;

  const formData = new FormData();
  formData.append("csrf_token", csrfToken);
  formData.append("address_id", addressId);

  fetch(baseUrl + "profile/address/set-default", {
    method: "POST",
    body: formData,
  })
    .then((res) => res.json())
    .then((data) => {
      showToast(data.message, data.success ? "success" : "error");

      if (data.success) {
        const prevDefault = document.querySelector(".address-badge");
        if (prevDefault) {
          const prevItem = prevDefault.closest(".address-item");
          const prevEditBtn = prevItem.querySelector(
            '[onclick*="openEditAddressModal"]'
          );
          if (prevEditBtn) prevEditBtn.setAttribute("data-default", "0");
          prevDefault.remove();
          const setDefaultBtn = document.createElement("button");
          setDefaultBtn.type = "button";
          setDefaultBtn.className = "address-default-btn";
          setDefaultBtn.setAttribute(
            "data-address-id",
            prevItem
              .querySelector(".address-delete-btn")
              .getAttribute("data-address-id")
          );
          setDefaultBtn.setAttribute("onclick", "setDefaultAddress(this)");
          setDefaultBtn.textContent = "Set Default";
          prevItem
            .querySelector('div[style*="display: flex"]')
            .insertBefore(
              setDefaultBtn,
              prevItem.querySelector('div[style*="display: flex"]').firstChild
            );
        }
        const addressItem = btn.closest(".address-item");
        const editBtn = addressItem.querySelector(
          '[onclick*="openEditAddressModal"]'
        );
        if (editBtn) editBtn.setAttribute("data-default", "1");
        const badge = document.createElement("span");
        badge.className = "address-badge";
        badge.textContent = "Default";
        addressItem.querySelector(".address-header").appendChild(badge);
        btn.remove();
      }
    })
    .catch((err) => console.error("Error:", err));
}
