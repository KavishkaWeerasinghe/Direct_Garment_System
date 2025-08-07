document.addEventListener("DOMContentLoaded", function () {
  // Sidebar elements
  const sidebar = document.querySelector(".sidebar");
  const mainContent = document.querySelector(".main-content");
  const toggleBtn = document.getElementById("sidebarToggle");

  // Initialize sidebar state
  function initializeSidebar() {
    // Check for saved preference
    if (localStorage.getItem("sidebarCollapsed") === "true") {
      sidebar.classList.add("collapsed");
      mainContent.classList.add("collapsed");
      if (toggleBtn) toggleBtn.style.left = "60px";
    } else {
      if (toggleBtn) toggleBtn.style.left = "280px";
    }
  }

  // Toggle sidebar function
  function toggleSidebar() {
    sidebar.classList.toggle("collapsed");
    mainContent.classList.toggle("collapsed");

    // Update toggle button position
    if (toggleBtn) {
      toggleBtn.style.left = sidebar.classList.contains("collapsed")
        ? "60px"
        : "280px";

      // Rotate icon logic
      const collapseIcon = toggleBtn.querySelector(".collapse-icon");
      const expandIcon = toggleBtn.querySelector(".expand-icon");

      if (sidebar.classList.contains("collapsed")) {
        collapseIcon.style.display = "none";
        expandIcon.style.display = "block";
      } else {
        collapseIcon.style.display = "block";
        expandIcon.style.display = "none";
      }
    }

    // Store preference
    localStorage.setItem(
      "sidebarCollapsed",
      sidebar.classList.contains("collapsed")
    );
  }

  // Initialize on load
  initializeSidebar();

  // Toggle sidebar when button clicked
  if (toggleBtn) {
    toggleBtn.addEventListener("click", toggleSidebar);
  }

  // Handle navigation item clicks
  document.querySelectorAll(".nav-item").forEach(function (item) {
    item.addEventListener("click", function (e) {
      // If sidebar is collapsed, expand it first
      if (sidebar.classList.contains("collapsed")) {
        toggleSidebar(); // This will expand the sidebar
      }

      // Handle submenus if present
      if (this.classList.contains("has-submenu")) {
        e.preventDefault();
        const section = this.getAttribute("data-section");
        const submenu = document.getElementById("submenu_" + section);

        // Close all other submenus
        document.querySelectorAll(".submenu").forEach(function (menu) {
          if (menu !== submenu) menu.style.display = "none";
        });

        // Toggle current submenu
        submenu.style.display =
          submenu.style.display === "block" ? "none" : "block";

        // Update expanded state
        document
          .querySelectorAll(".nav-item.has-submenu")
          .forEach(function (navItem) {
            navItem.classList.remove("expanded");
          });
        this.classList.toggle("expanded", submenu.style.display === "block");
      }

      // Update active state
      document
        .querySelectorAll(".nav-item, .submenu-item")
        .forEach(function (navItem) {
          navItem.classList.remove("active");
        });
      this.classList.add("active");
    });
  });

  // Handle submenu item clicks
  document.querySelectorAll(".submenu-item").forEach(function (item) {
    item.addEventListener("click", function (e) {
      document
        .querySelectorAll(".nav-item, .submenu-item")
        .forEach(function (navItem) {
          navItem.classList.remove("active");
        });
      this.classList.add("active");
    });
  });

  // Close submenus when clicking outside
  document.addEventListener("click", function (e) {
    if (!e.target.closest(".nav-item") && !e.target.closest(".submenu")) {
      document.querySelectorAll(".submenu").forEach(function (menu) {
        menu.style.display = "none";
      });
      document
        .querySelectorAll(".nav-item.has-submenu")
        .forEach(function (navItem) {
          navItem.classList.remove("expanded");
        });
    }
  });
});
