// Detect if we're in admin portal or user portal
const isAdminPortal = window.location.pathname.includes("/admin/");
const themeKey = isAdminPortal ? "admin-theme" : "user-theme";

// Apply saved theme on page load (default to light)
const savedTheme = localStorage.getItem(themeKey);
if (savedTheme === "dark") {
  document.documentElement.classList.remove("light");
} else {
  document.documentElement.classList.add("light");
}

// Toggle theme when button clicked
document.addEventListener("DOMContentLoaded", function () {
  const themeToggle = document.getElementById("theme-toggle");
  if (themeToggle) {
    themeToggle.addEventListener("click", function () {
      // Add transition class
      document.documentElement.classList.add("theme-transition");

      // Toggle theme
      document.documentElement.classList.toggle("light");
      const isLight = document.documentElement.classList.contains("light");
      const newTheme = isLight ? "light" : "dark";

      // Persist locally for immediate UI and cross-tab sync
      try {
        localStorage.setItem(themeKey, newTheme);
      } catch (e) {}
      try {
        localStorage.setItem("savedTheme", newTheme);
      } catch (e) {}

      // If user is logged in, save to server immediately
      if (window.APP && window.APP.IS_LOGGED_IN) {
        fetch(window.APP.BASE_URL + "profile/update-theme", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token": window.APP.CSRF_TOKEN,
          },
          body: JSON.stringify({
            theme: newTheme,
            csrf_token: window.APP.CSRF_TOKEN,
          }),
        }).catch(function (err) {
          console.error("Failed to persist theme on server", err);
        });
      }

      // Remove transition class after animation completes
      setTimeout(() => {
        document.documentElement.classList.remove("theme-transition");
      }, 300);
    });
  }

  // Use server preference if provided (applied early in header.php), else local
  (function initTheme() {
    var serverTheme =
      window.APP && window.APP.SAVED_USER_THEME
        ? window.APP.SAVED_USER_THEME
        : null;
    var saved = null;
    try {
      saved =
        localStorage.getItem(themeKey) || localStorage.getItem("savedTheme");
    } catch (e) {
      saved = null;
    }

    var themeToApply = serverTheme || saved || "light";
    if (themeToApply === "dark") {
      document.documentElement.classList.remove("light");
    } else {
      document.documentElement.classList.add("light");
    }
    try {
      localStorage.setItem(themeKey, themeToApply);
    } catch (e) {}
  })();

  // Listen to storage events to sync across tabs
  window.addEventListener("storage", function (e) {
    if (!e.key) return;
    if (e.key === themeKey || e.key === "savedTheme") {
      var v = e.newValue || "light";
      if (v === "dark") {
        document.documentElement.classList.remove("light");
      } else {
        document.documentElement.classList.add("light");
      }
    }
  });
});
