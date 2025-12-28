// Toast Notification System
function showToast(message, type = "success") {
  const toast = document.createElement("div");
  toast.className = `toast toast-${type}`;
  toast.textContent = message;

  document.body.appendChild(toast);

  // Trigger animation after DOM insertion
  setTimeout(() => toast.classList.add("show"), 10);

  setTimeout(() => toast.classList.remove("show"), 3000);
  setTimeout(() => toast.remove(), 3300);
}

// Redirect with toast message
function redirectWithToast(url, message, type = "success") {
  const redirectUrl = new URL(url, window.location.origin);
  redirectUrl.searchParams.set("toast_message", encodeURIComponent(message));
  redirectUrl.searchParams.set("toast_type", type);
  window.location.href = redirectUrl.toString();
}

// Handle AJAX response with optional redirect and toast
function handleAjaxResponse(data, options = {}) {
  const { onSuccess = null, onError = null, redirectDelay = 0 } = options;

  if (data.success) {
    // Show success toast
    if (data.message) {
      showToast(data.message, "success");
    }

    // Handle redirect with toast if needed
    if (data.redirect) {
      setTimeout(() => {
        redirectWithToast(data.redirect, data.message || "Success", "success");
      }, redirectDelay);
    }

    // Execute custom success callback if provided
    if (typeof onSuccess === "function") {
      onSuccess(data);
    }
  } else {
    // Show error toast
    if (data.message) {
      showToast(data.message, "error");
    }

    // Execute custom error callback if provided
    if (typeof onError === "function") {
      onError(data);
    }
  }
}

// Auto-show toast from URL parameters
document.addEventListener("DOMContentLoaded", function () {
  const urlParams = new URLSearchParams(window.location.search);
  const success = urlParams.get("success");
  const error = urlParams.get("error");

  // Check for toast messages in URL parameters
  const toastMessage = urlParams.get("toast_message");
  const toastType = urlParams.get("toast_type");

  if (toastMessage && toastType) {
    showToast(decodeURIComponent(toastMessage), toastType);
    // Clean URL by removing toast parameters
    urlParams.delete("toast_message");
    urlParams.delete("toast_type");
    const newUrl =
      window.location.pathname +
      (urlParams.toString() ? "?" + urlParams.toString() : "");
    window.history.replaceState({}, "", newUrl);
  } else if (success) {
    showToast(success, "success");
  } else if (error) {
    showToast(error, "error");
  }
});
