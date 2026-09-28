// Sidebar/topnav wiring — runs once the shared sidebar (sidebar.js) has
// been injected into the page, rather than on DOMContentLoaded, since the
// toggle button, overlay, and logo all live inside that injected markup.
document.addEventListener('sidebar:ready', function () {
    const toggleBtn = document.getElementById('toggleBtn');
    const overlay = document.getElementById('overlay');
    const profileIcon = document.getElementById('profileIcon');
    const dropdown = document.getElementById('dropdown');
    const usernameDisplay = document.getElementById('usernameDisplay');
    const themeToggle = document.getElementById('themeToggle');
    const sidebarLogo = document.getElementById('sidebarLogo');
    const sidebar = document.getElementById('sidebar');

    // Apply sidebar collapsed state ASAP before anything else renders
    if (sidebar && localStorage.getItem('sidebarCollapsed') === 'true') {
        sidebar.classList.add('collapsed');
    }

    // username from session
    function getUsernameFromSession() {
        const username = sessionStorage.getItem("userName"); // Correct method name: getItem
        if (username) {
            usernameDisplay.textContent = username;
        }
        return username || "Username"; // If no username found, return a default value
    }

    window.onload = function () {
        getUsernameFromSession();
    };

    // Set username display and first letter for profile icon
    const username = getUsernameFromSession();
    usernameDisplay.textContent = username;
    profileIcon.textContent = username.charAt(0).toUpperCase();

    // Toggle sidebar (desktop collapse + mobile open/overlay)
    toggleBtn.addEventListener('click', function () {
        sidebar.classList.toggle('collapsed');
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');

        // Store sidebar state in localStorage
        if (sidebar.classList.contains('collapsed')) {
            localStorage.setItem('sidebarCollapsed', 'true');
        } else {
            localStorage.setItem('sidebarCollapsed', 'false');
        }
    });

    // Profile dropdown toggle
    profileIcon.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.toggle('show');
    });

    // Close dropdown when clicking elsewhere
    document.addEventListener('click', function (e) {
        if (!profileIcon.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });

    // Close sidebar when clicking overlay (mobile)
    overlay.addEventListener('click', function () {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
    });

    // Ensure responsive behavior on resize
    window.addEventListener('resize', function () {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        }
    });

    function updateLogo(theme) {
        if (theme === 'dark') {
            sidebarLogo.src = "../ASSETS/logo-Black&White.png"; // Dark mode logo
        } else {
            sidebarLogo.src = "../ASSETS/logo64.png"; // Light mode logo
        }
    }

    // Theme toggle functionality
    themeToggle.addEventListener('change', function () {
        if (this.checked) {
            document.body.classList.add('dark-theme');
            localStorage.setItem('theme', 'dark');
            updateLogo('dark');
        } else {
            document.body.classList.remove('dark-theme');
            localStorage.setItem('theme', 'light');
            updateLogo('light');
        }
    });

    // Load theme from localStorage
    function loadTheme() {
        const currentTheme = localStorage.getItem('theme');
        if (currentTheme === 'dark') {
            document.body.classList.add('dark-theme');
            themeToggle.checked = true;
        } else {
            document.body.classList.remove('dark-theme');
            themeToggle.checked = false;
        }
        updateLogo(currentTheme);
    }
    loadTheme(); // Call once the sidebar is ready to apply correct logo and theme
});

// Placeholder logout function
const logout = () => {
    sessionStorage.removeItem('isLoggedIn');
    sessionStorage.removeItem('userName');
    window.location.href = '../index.html';
};
