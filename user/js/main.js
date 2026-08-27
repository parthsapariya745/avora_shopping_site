document.addEventListener('DOMContentLoaded', () => {
  // Mobile Nav Menu Toggle
  const mobileToggle = document.getElementById('mobileMenuBtn');
  const navDrawer = document.getElementById('mobileNavDrawer');
  if (mobileToggle && navDrawer) {
    mobileToggle.addEventListener('click', () => {
      navDrawer.style.display = navDrawer.style.display === 'none' ? 'block' : 'none';
    });
  }

  // FAQ Accordion (Delegated Click)
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.faq-question');
    if (btn) {
      const parent = btn.closest('.faq-item');
      if (parent) {
        const isActive = parent.classList.contains('active');
        document.querySelectorAll('.faq-item').forEach((item) => item.classList.remove('active'));
        if (!isActive) {
          parent.classList.add('active');
        }
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
          window.lucide.createIcons();
        }
      }
    }
  });

  // Product Gallery Thumbnail Click
  const mainGalleryImg = document.getElementById('mainGalleryImg');
  const thumbnails = document.querySelectorAll('.thumbnail-img');
  if (mainGalleryImg && thumbnails.length > 0) {
    thumbnails.forEach((thumb) => {
      thumb.addEventListener('click', () => {
        thumbnails.forEach((t) => t.classList.remove('active'));
        thumb.classList.add('active');
        mainGalleryImg.src = thumb.src;
      });
    });
  }

  // Auto initialize lucide icons
  if (window.lucide) {
    window.lucide.createIcons();
  }

  // Auto dismiss initial PHP flash toast if present
  const flashToast = document.getElementById('flashToast');
  if (flashToast) {
    setTimeout(() => {
      flashToast.style.opacity = '0';
      flashToast.style.transform = 'translateY(-10px)';
      flashToast.style.transition = 'all 0.35s ease';
      setTimeout(() => flashToast.remove(), 350);
    }, 4500);
  }
});

// Toast notification helper
function showToast(message, type = 'success') {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    container.id = 'toastContainer';
    document.body.appendChild(container);
  }

  const iconName = type === 'success' ? 'check-circle' : (type === 'error' || type === 'danger' ? 'alert-circle' : 'info');

  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `
    <i data-lucide="${iconName}" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
    <span>${message}</span>
    <button onclick="this.parentElement.remove()" class="toast-close-btn" aria-label="Close">&times;</button>
  `;
  container.appendChild(toast);

  if (window.lucide) {
    window.lucide.createIcons();
  }

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(-10px)';
    toast.style.transition = 'all 0.35s ease';
    setTimeout(() => toast.remove(), 350);
  }, 4500);
}
