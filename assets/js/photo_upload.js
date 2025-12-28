document.addEventListener("DOMContentLoaded", function () {
  // Photo Upload
  const photoFrame = document.getElementById("photo-upload-frame");
  const photoInput = document.getElementById("photo-file-input");
  const photoPreview = document.getElementById("photo-preview");
  const cropModal = document.getElementById("crop-modal");
  const cropImage = document.getElementById("crop-image");
  const cropContainer = document.getElementById("crop-container");
  const cropConfirmBtn = document.getElementById("crop-confirm-btn");
  const cropCancelBtn = document.getElementById("crop-cancel-btn");
  const cropModalClose = document.getElementById("crop-modal-close");
  const uploadBtn = document.getElementById("upload-photo-btn");
  let isDragging = false;
  if (photoFrame) {
    // Only add click handler if no custom handler exists (allows override)
    if (!photoFrame.hasAttribute("data-custom-click")) {
      photoFrame.addEventListener("click", function () {
        photoInput.click();
      });
    }

    // Drag and drop functionality
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
  if (photoInput) {
    photoInput.addEventListener("change", function (e) {
      console.log("[Photo-Upload] File input changed");

      // Allow external handler to take over if window.handlePhotoFile exists
      if (typeof window.handlePhotoFile === "function") {
        const file = e.target.files[0];
        if (file) {
          window.handlePhotoFile(file);
          return;
        }
      }

      const file = e.target.files[0];

      // Client-side validation
      if (!file) {
        console.log("[Photo-Upload] No file selected");
        return;
      }

      console.log(
        "[Photo-Upload] File selected:",
        file.name,
        file.type,
        file.size
      );

      // Check if only one file is selected
      if (e.target.files.length > 1) {
        console.log("[Photo-Upload] Multiple files detected");
        if (typeof showToast === "function") {
          showToast("Please select only one file", "error");
        } else {
          alert("Please select only one file");
        }
        e.target.value = "";
        return;
      } // Validate file size (must be > 0 and <= 2MB)

      const maxSize = 2 * 1024 * 1024; // 2MB in bytes

      if (file.size <= 0) {
        console.log("[Photo-Upload] Empty file detected");
        if (typeof showToast === "function") {
          showToast("Selected file is empty (0 bytes)", "error");
        } else {
          alert("Selected file is empty (0 bytes)");
        }
        e.target.value = "";
        return;
      }
      if (file.size > maxSize) {
        console.log("[Photo-Upload] File too large:", file.size);
        if (typeof showToast === "function") {
          showToast("File size must be less than 2MB", "error");
        } else {
          alert("File size must be less than 2MB");
        }
        e.target.value = "";
        return;
      }

      // Validate file extension
      const fileName = file.name.toLowerCase();
      const allowedExtensions = [".jpg", ".jpeg", ".png", ".gif"];
      const hasValidExtension = allowedExtensions.some((ext) =>
        fileName.endsWith(ext)
      );

      if (!hasValidExtension) {
        console.log("[Photo-Upload] Invalid extension:", fileName);
        if (typeof showToast === "function") {
          showToast("Only JPG, JPEG, PNG, and GIF files are allowed", "error");
        } else {
          alert("Only JPG, JPEG, PNG, and GIF files are allowed");
        }
        e.target.value = "";
        return;
      }

      // Validate MIME type
      const allowedMimeTypes = ["image/jpeg", "image/png", "image/gif"];
      if (!allowedMimeTypes.includes(file.type)) {
        console.log("[Photo-Upload] Invalid MIME type:", file.type);
        if (typeof showToast === "function") {
          showToast(
            "Invalid file type. Only JPG, PNG, and GIF images are allowed",
            "error"
          );
        } else {
          alert("Invalid file type. Only JPG, PNG, and GIF images are allowed");
        }
        e.target.value = "";
        return;
      }

      // Additional validation: Check if file is actually an image
      if (!file.type.match("image.*")) {
        console.log("[Photo-Upload] File is not an image");
        if (typeof showToast === "function") {
          showToast("Selected file is not a valid image", "error");
        } else {
          alert("Selected file is not a valid image");
        }
        e.target.value = "";
        return;
      }

      console.log("[Photo-Upload] Validations passed, proceeding");

      // All validations passed, proceed with file
      selectedFile = file;
      const reader = new FileReader();
      reader.onload = function (event) {
        const img = new Image();
        img.onload = function () {
          // Validate image dimensions (optional: prevent extremely large images)
          const maxWidth = 5000;
          const maxHeight = 5000;
          if (img.width > maxWidth || img.height > maxHeight) {
            showToast(
              `Image dimensions too large. Maximum: ${maxWidth}x${maxHeight}px`,
              "error"
            );
            photoInput.value = "";
            return;
          }

          cropImage.src = event.target.result;
          cropImage.style.width = "auto";
          cropImage.style.height = "auto";
          cropImage.style.maxWidth = "none";
          cropImage.style.maxHeight = "none";
          cropImage.style.transform = "none";
          cropImage.style.position = "absolute";
          cropImage.style.top = "0";
          cropImage.style.left = "0";

          cropModal.classList.add("active");

          setTimeout(() => {
            initCropSelection(img.width, img.height);
          }, 50);
        };
        img.onerror = function () {
          showToast("Failed to load image. File may be corrupted", "error");
          photoInput.value = "";
        };
        img.src = event.target.result;
      };
      reader.onerror = function () {
        showToast("Failed to read file", "error");
        photoInput.value = "";
      };
      reader.readAsDataURL(file);
    });
  }

  let cropSelection = null;
  let cropHandles = [];
  let selectionX = 0;
  let selectionY = 0;
  let selectionSize = 200;
  let activeHandle = null;
  let dragStartX = 0;
  let dragStartY = 0;
  function initCropSelection(imgWidth, imgHeight) {
    if (!cropContainer || !cropImage) return;

    // Remove old selection if exists
    if (cropSelection) cropSelection.remove();
    cropHandles.forEach((h) => h.remove());
    cropHandles = [];

    // Fit container to image (max 600x600)
    const maxSize = 600;
    const scale = Math.min(maxSize / imgWidth, maxSize / imgHeight, 1);
    const displayWidth = imgWidth * scale;
    const displayHeight = imgHeight * scale;

    cropContainer.style.width = displayWidth + "px";
    cropContainer.style.height = displayHeight + "px";

    cropImage.style.width = displayWidth + "px";
    cropImage.style.height = displayHeight + "px";
    cropImage.style.left = "0";
    cropImage.style.top = "0";

    // Initial selection (centered square)
    const minDimension = Math.min(displayWidth, displayHeight);
    selectionSize = minDimension * 0.8;
    selectionX = (displayWidth - selectionSize) / 2;
    selectionY = (displayHeight - selectionSize) / 2;

    // Create selection box
    cropSelection = document.createElement("div");
    cropSelection.style.position = "absolute";
    cropSelection.style.border = "2px solid var(--text-color)";
    cropSelection.style.boxShadow = "0 0 0 9999px rgba(0,0,0,0.5)";
    cropSelection.style.cursor = "move";
    cropSelection.style.pointerEvents = "auto";
    cropContainer.appendChild(cropSelection);

    // Create corner handles
    const corners = ["nw", "ne", "sw", "se"];
    corners.forEach((corner) => {
      const handle = document.createElement("div");
      handle.className = "crop-handle crop-handle-" + corner;
      handle.style.position = "absolute";
      handle.style.width = "20px";
      handle.style.height = "20px";
      handle.style.background = "var(--text-color)";
      handle.style.border = "2px solid var(--bg-color)";
      handle.style.cursor = corner.includes("n")
        ? corner.includes("w")
          ? "nw-resize"
          : "ne-resize"
        : corner.includes("w")
        ? "sw-resize"
        : "se-resize";
      handle.style.zIndex = "10";
      handle.dataset.corner = corner;
      cropSelection.appendChild(handle);
      cropHandles.push(handle);
    });
    updateSelection();
    attachSelectionEvents();
  }

  // Expose initCropSelection globally for profile.js webcam capture
  window.initCropSelection = initCropSelection;

  function updateSelection() {
    if (!cropSelection) return;

    cropSelection.style.left = selectionX + "px";
    cropSelection.style.top = selectionY + "px";
    cropSelection.style.width = selectionSize + "px";
    cropSelection.style.height = selectionSize + "px";

    // Position handles
    cropHandles.forEach((handle) => {
      const corner = handle.dataset.corner;
      if (corner === "nw") {
        handle.style.left = "-10px";
        handle.style.top = "-10px";
      } else if (corner === "ne") {
        handle.style.right = "-10px";
        handle.style.top = "-10px";
      } else if (corner === "sw") {
        handle.style.left = "-10px";
        handle.style.bottom = "-10px";
      } else if (corner === "se") {
        handle.style.right = "-10px";
        handle.style.bottom = "-10px";
      }
    });
  }

  function attachSelectionEvents() {
    // Drag selection
    cropSelection.addEventListener("mousedown", function (e) {
      if (e.target.classList.contains("crop-handle")) return;
      isDragging = true;
      dragStartX = e.clientX - selectionX;
      dragStartY = e.clientY - selectionY;
      e.preventDefault();
      e.stopPropagation();
    });

    // Resize handles
    cropHandles.forEach((handle) => {
      handle.addEventListener("mousedown", function (e) {
        activeHandle = this.dataset.corner;
        dragStartX = e.clientX;
        dragStartY = e.clientY;
        e.preventDefault();
        e.stopPropagation();
      });
    });
  }
  // Mouse move for drag and resize
  document.addEventListener("mousemove", function (e) {
    if (isDragging && cropSelection) {
      e.preventDefault();
      const containerWidth = cropContainer.offsetWidth;
      const containerHeight = cropContainer.offsetHeight;
      const newX = e.clientX - dragStartX;
      const newY = e.clientY - dragStartY;

      selectionX = Math.max(0, Math.min(containerWidth - selectionSize, newX));
      selectionY = Math.max(0, Math.min(containerHeight - selectionSize, newY));
      updateSelection();
    } else if (activeHandle && cropSelection) {
      e.preventDefault();
      const containerWidth = cropContainer.offsetWidth;
      const containerHeight = cropContainer.offsetHeight;
      const deltaX = e.clientX - dragStartX;
      const deltaY = e.clientY - dragStartY;
      const delta = Math.max(deltaX, deltaY);

      let newSize = selectionSize;
      let newX = selectionX;
      let newY = selectionY;

      if (activeHandle === "se") {
        // Bottom-right: grow down and right
        newSize = Math.max(
          50,
          Math.min(
            selectionSize + delta,
            containerWidth - selectionX,
            containerHeight - selectionY
          )
        );
      } else if (activeHandle === "nw") {
        // Top-left: grow up and left (shrink when dragging down-right)
        const maxShrink = selectionSize - 50;
        const constrainedDelta = Math.min(delta, maxShrink);
        newSize = Math.max(
          50,
          Math.min(
            selectionSize - constrainedDelta,
            selectionX + selectionSize,
            selectionY + selectionSize
          )
        );
        newX = selectionX + (selectionSize - newSize);
        newY = selectionY + (selectionSize - newSize);
      } else if (activeHandle === "ne") {
        // Top-right: grow right and up (shrink)
        newSize = Math.max(
          50,
          Math.min(
            selectionSize + deltaX,
            containerWidth - selectionX,
            selectionY + selectionSize
          )
        );
        newY = selectionY + (selectionSize - newSize);
      } else if (activeHandle === "sw") {
        // Bottom-left: grow down and left (shrink)
        newSize = Math.max(
          50,
          Math.min(
            selectionSize + deltaY,
            selectionX + selectionSize,
            containerHeight - selectionY
          )
        );
        newX = selectionX + (selectionSize - newSize);
      }

      if (newSize >= 50 && newSize !== selectionSize) {
        selectionSize = newSize;
        selectionX = newX;
        selectionY = newY;
        dragStartX = e.clientX;
        dragStartY = e.clientY;
        updateSelection();
      }
    }
  });

  document.addEventListener("mouseup", function () {
    isDragging = false;
    activeHandle = null;
  });

  function closeCropModal() {
    cropModal.classList.remove("active");
    photoInput.value = "";
    selectedFile = null;
    if (cropSelection) {
      cropSelection.remove();
      cropSelection = null;
    }
    cropHandles = [];
  }

  let croppedBlob = null;

  if (cropConfirmBtn) {
    cropConfirmBtn.addEventListener("click", function () {
      const canvas = document.createElement("canvas");
      const ctx = canvas.getContext("2d");

      canvas.width = selectionSize;
      canvas.height = selectionSize;

      const img = new Image();
      img.src = cropImage.src;

      const scaleX = img.width / cropImage.offsetWidth;
      const scaleY = img.height / cropImage.offsetHeight;

      const sourceX = (selectionX - cropImage.offsetLeft) * scaleX;
      const sourceY = (selectionY - cropImage.offsetTop) * scaleY;
      const sourceSize = selectionSize * scaleX;

      ctx.drawImage(
        img,
        sourceX,
        sourceY,
        sourceSize,
        sourceSize,
        0,
        0,
        selectionSize,
        selectionSize
      );

      canvas.toBlob(
        function (blob) {
          croppedBlob = blob;
          const croppedImage = canvas.toDataURL("image/jpeg", 0.9);

          if (photoPreview) {
            photoPreview.src = croppedImage;
          } else {
            const placeholder = photoFrame.querySelector(
              ".photo-upload-placeholder"
            );
            if (placeholder) placeholder.remove();
            const newImg = document.createElement("img");
            newImg.id = "photo-preview";
            newImg.src = croppedImage;
            photoFrame.appendChild(newImg);
          }

          closeCropModal();
          uploadBtn.style.display = "block";
        },
        "image/jpeg",
        0.9
      );
    });
  }
  // Handle form submission with cropped blob
  const photoForm = document.getElementById("photo-upload-form");
  if (photoForm) {
    photoForm.addEventListener("submit", function (e) {
      e.preventDefault();

      if (!croppedBlob) {
        showToast("Please select and crop an image first", "error");
        return;
      }

      // Additional validation on the blob before upload
      const maxSize = 2 * 1024 * 1024; // 2MB
      if (croppedBlob.size <= 0) {
        showToast("Cropped image is empty", "error");
        return;
      }
      if (croppedBlob.size > maxSize) {
        showToast(
          "Cropped image size exceeds 2MB. Please try a smaller image",
          "error"
        );
        return;
      }

      // Validate blob type
      if (!croppedBlob.type || !croppedBlob.type.startsWith("image/")) {
        showToast("Invalid image format", "error");
        return;
      }
      const formData = new FormData();
      formData.append(
        "csrf_token",
        this.querySelector('[name="csrf_token"]').value
      );
      formData.append("action", "upload_photo");
      formData.append("profile_photo", croppedBlob, "profile.jpg");

      uploadBtn.disabled = true;
      uploadBtn.textContent = "Uploading...";

      const baseUrl = window.location.origin + "/online_shopping_system/";

      // Determine upload endpoint based on form action or default to member
      const formAction = this.getAttribute("action");
      const uploadEndpoint = formAction || baseUrl + "profile/upload-photo";

      fetch(uploadEndpoint, {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          uploadBtn.disabled = false;
          uploadBtn.textContent = "Upload Photo";
          showToast(data.message, data.success ? "success" : "error");
          if (data.success) {
            croppedBlob = null;
            uploadBtn.style.display = "none";

            const newSrc = data.filename
              ? baseUrl +
                "assets/images/uploads/profile/" +
                data.filename +
                "?t=" +
                Date.now()
              : null;

            if (newSrc) {

              // Auto-update all images with data-photo-target attribute
              document
                .querySelectorAll("[data-photo-target]")
                .forEach((img) => {
                  img.src = newSrc;
                  console.log(
                    "[Photo-Upload] Updated:",
                    img.dataset.photoTarget
                  );
                });

              // Fallback: Common selectors (for backward compatibility)
              const commonSelectors = [
                ".profile-user-card .profile-avatar",
                ".sidebar-user-info .user-avatar",
                ".sidebar-user-info img",
                "#admin-sidebar-avatar",
                "header .profile-avatar",
                "#photo-preview",
                "#photo-upload-frame img",
              ];

              commonSelectors.forEach((selector) => {
                const elements = document.querySelectorAll(selector);
                elements.forEach((el) => {
                  if (el && !el.hasAttribute("data-photo-target")) {
                    el.src = newSrc;
                    console.log("[Photo-Upload] Updated (fallback):", selector);
                  }
                });
              });

              // Replace placeholder with img if exists (for first-time uploads)
              const placeholder = document.querySelector('.profile-user-card .profile-avatar-placeholder');
              if (placeholder && newSrc) {
                const img = document.createElement('img');
                img.src = newSrc;
                img.alt = 'Profile';
                img.className = 'profile-avatar';
                img.setAttribute('data-photo-target', 'sidebar-avatar');
                placeholder.parentNode.replaceChild(img, placeholder);
                console.log("[Photo-Upload] Replaced placeholder with img");
              }

              // Replace admin sidebar placeholder with img if exists
              const adminPlaceholder = document.querySelector('.admin-sidebar-avatar-placeholder');
              if (adminPlaceholder && newSrc) {
                const img = document.createElement('img');
                img.src = newSrc;
                img.alt = 'Profile';
                img.className = 'admin-sidebar-avatar';
                img.id = 'admin-sidebar-avatar';
                img.setAttribute('data-photo-target', 'admin-sidebar-avatar');
                adminPlaceholder.parentNode.replaceChild(img, adminPlaceholder);
                console.log("[Photo-Upload] Replaced admin sidebar placeholder with img");
              }
            }

            // Trigger custom event for additional handling
            window.dispatchEvent(
              new CustomEvent("photoUploaded", {
                detail: { filename: data.filename, url: newSrc },
              })
            );
          }
        })
        .catch((error) => {
          console.error("Upload error:", error);
          uploadBtn.disabled = false;
          uploadBtn.textContent = "Upload Photo";

          showToast("Failed to upload photo. Please try again.", "error");
        });
    });
  }

  if (cropCancelBtn) {
    cropCancelBtn.addEventListener("click", closeCropModal);
  }

  if (cropModalClose) {
    cropModalClose.addEventListener("click", closeCropModal);
  }
});
