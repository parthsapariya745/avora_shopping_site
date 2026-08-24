    </div> <!-- /.main-wrapper -->
</div> <!-- /.app-container -->

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Mobile Sidebar Toggle
  const toggleBtn = document.getElementById('sidebarToggleBtn');
  const sidebar = document.getElementById('adminSidebar');
  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      sidebar.classList.toggle('open');
    });
    document.addEventListener('click', (e) => {
      if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
        sidebar.classList.remove('open');
      }
    });
  }

  // Auto slug generation helper
  const nameInput = document.getElementById('catName') || document.getElementById('prodName');
  const slugInput = document.getElementById('catSlug') || document.getElementById('prodSlug');
  if (nameInput && slugInput) {
    let userEditedSlug = slugInput.value.trim().length > 0;
    slugInput.addEventListener('input', () => { userEditedSlug = true; });
    nameInput.addEventListener('input', () => {
      if (!userEditedSlug) {
        slugInput.value = nameInput.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
      }
    });
  }

  // Multi-image upload instant preview
  const imgInput = document.getElementById('prodImageFiles');
  const previewBox = document.getElementById('imagePreviewContainer');
  if (imgInput && previewBox) {
    imgInput.addEventListener('change', () => {
      previewBox.innerHTML = '';
      if (imgInput.files) {
        Array.from(imgInput.files).forEach(file => {
          if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
              const div = document.createElement('div');
              div.className = 'preview-box';
              div.innerHTML = `<img src="${e.target.result}" alt="Preview" style="width:100%; height:100%; object-fit:cover; border-radius:6px;">`;
              previewBox.appendChild(div);
            };
            reader.readAsDataURL(file);
          }
        });
      }
    });
  }
});
</script>
</body>
</html>
