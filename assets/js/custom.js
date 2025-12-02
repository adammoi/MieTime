/**
 * Mie Time - Custom JavaScript
 * Enhancement untuk UX dan interaktivitas
 */

// ==================== DOCUMENT READY ====================
document.addEventListener("DOMContentLoaded", function () {
  // Initialize tooltips
  initTooltips();

  // Initialize scroll to top button
  initScrollToTop();

  // Add fade-in animation to cards
  // Respect user's reduced motion preference: only animate if allowed
  try {
    var prefersReduced = window.matchMedia(
      "(prefers-reduced-motion: reduce)"
    ).matches;
  } catch (e) {
    var prefersReduced = false;
  }

  if (!prefersReduced) {
    animateCards();
  }

  // Initialize image lazy loading
  initLazyLoad();

  // Initialize confirm dialogs
  initConfirmDialogs();
});

// ==================== TOOLTIPS ====================
function initTooltips() {
  const tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });
}

// ==================== SCROLL TO TOP ====================
function initScrollToTop() {
  // Ensure one global button, and move it to body if it exists elsewhere
  let btn = document.querySelector(".scroll-to-top");
  if (!btn) {
    btn = document.createElement("div");
    btn.className = "scroll-to-top";
    btn.innerHTML = '<i class="fas fa-arrow-up"></i>';
    // Accessibility attributes
    btn.setAttribute("role", "button");
    btn.setAttribute("aria-label", "Scroll to top");
    btn.setAttribute("title", "Kembali ke atas");
  }
  // If button is not a direct child of body, move it
  if (btn.parentElement !== document.body) {
    document.body.appendChild(btn);
  }
  // Inline safeguards to keep it above footer/content
  btn.style.position = "fixed";
  btn.style.zIndex = "99999";
  btn.style.pointerEvents = "auto";
  // Click handler (avoid duplicate listeners)
  btn.onclick = function () {
    window.scrollTo({
      top: 0,
      behavior:
        window.matchMedia &&
        window.matchMedia("(prefers-reduced-motion: reduce)").matches
          ? "auto"
          : "smooth",
    });
  };

  // Keyboard accessibility: activate on Enter/Space
  btn.tabIndex = 0;
  btn.onkeydown = function (e) {
    if (e.key === "Enter" || e.key === " ") {
      e.preventDefault();
      btn.click();
    }
  };

  // Show/hide on scroll
  window.addEventListener("scroll", function () {
    const btn = document.querySelector(".scroll-to-top");
    if (btn && window.pageYOffset > 300) {
      btn.classList.add("show");
    } else if (btn) {
      btn.classList.remove("show");
    }
  });
}

// ==================== ANIMATE CARDS ====================
function animateCards() {
  const cards = document.querySelectorAll(".card");

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry, index) => {
        if (entry.isIntersecting) {
          setTimeout(() => {
            entry.target.classList.add("fade-in");
          }, index * 100);
          observer.unobserve(entry.target);
        }
      });
    },
    {
      threshold: 0.1,
    }
  );

  cards.forEach((card) => {
    observer.observe(card);
  });
}

// ==================== LAZY LOAD IMAGES ====================
function initLazyLoad() {
  const images = document.querySelectorAll("img[data-src]");

  const imageObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const img = entry.target;
        img.src = img.dataset.src;
        img.removeAttribute("data-src");
        imageObserver.unobserve(img);
      }
    });
  });

  images.forEach((img) => imageObserver.observe(img));
}

// ==================== CONFIRM DIALOGS ====================
function initConfirmDialogs() {
  document.querySelectorAll("[data-confirm]").forEach((element) => {
    element.addEventListener("click", function (e) {
      const message = this.dataset.confirm || "Apakah Anda yakin?";
      if (!confirm(message)) {
        e.preventDefault();
        return false;
      }
    });
  });
}

// ==================== FORM VALIDATION ====================
function validateForm(formId) {
  const form = document.getElementById(formId);
  if (!form) return true;

  const inputs = form.querySelectorAll("[required]");
  let isValid = true;

  inputs.forEach((input) => {
    if (!input.value.trim()) {
      input.classList.add("is-invalid");
      isValid = false;
    } else {
      input.classList.remove("is-invalid");
    }

    // Live validation
    input.addEventListener("input", function () {
      if (this.value.trim()) {
        this.classList.remove("is-invalid");
      }
    });
  });

  return isValid;
}

// ==================== COPY TO CLIPBOARD ====================
function copyToClipboard(text, successMessage = "Berhasil disalin!") {
  navigator.clipboard
    .writeText(text)
    .then(() => {
      showToast("success", successMessage);
    })
    .catch(() => {
      showToast("error", "Gagal menyalin");
    });
}

// ==================== TOAST NOTIFICATIONS ====================
function showToast(type, message, duration = 3000) {
  // Create toast container if not exists
  let container = document.querySelector(".toast-container");
  if (!container) {
    container = document.createElement("div");
    container.className = "toast-container position-fixed bottom-0 end-0 p-3";
    document.body.appendChild(container);
  }

  // Toast colors
  const colors = {
    success: "bg-success",
    error: "bg-danger",
    warning: "bg-warning",
    info: "bg-info",
  };

  const icons = {
    success: "fa-check-circle",
    error: "fa-exclamation-circle",
    warning: "fa-exclamation-triangle",
    info: "fa-info-circle",
  };

  // Create toast
  const toast = document.createElement("div");
  toast.className = `toast align-items-center text-white ${
    colors[type] || "bg-primary"
  } border-0`;
  toast.setAttribute("role", "alert");
  toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas ${icons[type]} me-2"></i>${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;

  container.appendChild(toast);

  const bsToast = new bootstrap.Toast(toast, {
    autohide: true,
    delay: duration,
  });

  bsToast.show();

  // Remove from DOM after hidden
  toast.addEventListener("hidden.bs.toast", function () {
    this.remove();
  });
}

// ==================== AJAX HELPER ====================
async function fetchAPI(url, options = {}) {
  try {
    const response = await fetch(url, {
      headers: {
        "Content-Type": "application/json",
        ...options.headers,
      },
      ...options,
    });

    if (!response.ok) {
      throw new Error("Network response was not ok");
    }

    return await response.json();
  } catch (error) {
    console.error("Fetch error:", error);
    showToast("error", "Terjadi kesalahan. Silakan coba lagi.");
    return null;
  }
}

// ==================== DEBOUNCE ====================
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

// ==================== SEARCH WITH DEBOUNCE ====================
function initSearchDebounce(inputId, callback, delay = 500) {
  const input = document.getElementById(inputId);
  if (!input) return;

  const debouncedSearch = debounce(callback, delay);

  input.addEventListener("input", function () {
    const query = this.value.trim();
    if (query.length > 2) {
      debouncedSearch(query);
    }
  });
}

// ==================== IMAGE PREVIEW ====================
function previewImage(input, previewId) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();

    reader.onload = function (e) {
      const preview = document.getElementById(previewId);
      if (preview) {
        preview.src = e.target.result;
        preview.style.display = "block";
      }
    };

    reader.readAsDataURL(input.files[0]);
  }
}

// ==================== MULTIPLE IMAGE PREVIEW ====================
function previewMultipleImages(input, containerId) {
  const container = document.getElementById(containerId);
  if (!container) return;

  container.innerHTML = "";

  if (input.files) {
    Array.from(input.files).forEach((file, index) => {
      const reader = new FileReader();

      reader.onload = function (e) {
        const div = document.createElement("div");
        div.className = "position-relative d-inline-block m-2";
        div.innerHTML = `
                    <img src="${e.target.result}" class="rounded" 
                         style="width: 100px; height: 100px; object-fit: cover;">
                    <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 rounded-circle"
                            onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                `;
        container.appendChild(div);
      };

      reader.readAsDataURL(file);
    });
  }
}

// ==================== RATING STARS ====================
function initRatingStars(containerId) {
  const container = document.getElementById(containerId);
  if (!container) return;

  const stars = container.querySelectorAll(".star");
  const input = container.querySelector('input[type="hidden"]');

  stars.forEach((star, index) => {
    star.addEventListener("click", function () {
      const rating = index + 1;
      input.value = rating;

      stars.forEach((s, i) => {
        if (i < rating) {
          s.classList.remove("far");
          s.classList.add("fas");
        } else {
          s.classList.remove("fas");
          s.classList.add("far");
        }
      });
    });

    star.addEventListener("mouseenter", function () {
      stars.forEach((s, i) => {
        if (i <= index) {
          s.classList.add("text-warning");
        } else {
          s.classList.remove("text-warning");
        }
      });
    });
  });

  container.addEventListener("mouseleave", function () {
    const currentRating = parseInt(input.value) || 0;
    stars.forEach((s, i) => {
      if (i < currentRating) {
        s.classList.add("text-warning");
      } else {
        s.classList.remove("text-warning");
      }
    });
  });
}

// ==================== AUTO DISMISS ALERTS ====================
setTimeout(function () {
  const alerts = document.querySelectorAll(".alert:not(.alert-permanent)");
  alerts.forEach((alert) => {
    const bsAlert = new bootstrap.Alert(alert);
    setTimeout(() => bsAlert.close(), 5000);
  });
}, 100);

// ==================== EXPORT FUNCTIONS ====================
window.MieTime = {
  copyToClipboard,
  showToast,
  fetchAPI,
  debounce,
  previewImage,
  previewMultipleImages,
  initRatingStars,
  validateForm,
};
