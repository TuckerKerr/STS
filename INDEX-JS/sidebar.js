// Loads the shared sidebar (partials/sidebar.html) into the page's
// <div id="sidebar-placeholder"></div> and announces readiness via the
// "sidebar:ready" event, since fetch() resolves after DOMContentLoaded.
// Wrapper.js listens for that event to wire up the sidebar's behavior.
(function () {
    async function loadSidebar() {
        const placeholder = document.getElementById('sidebar-placeholder');
        if (!placeholder) return;

        try {
            const response = await fetch('/sts/partials/sidebar.html');
            placeholder.outerHTML = await response.text();
        } catch (error) {
            console.error('Failed to load sidebar:', error);
        }

        document.dispatchEvent(new Event('sidebar:ready'));
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadSidebar);
    } else {
        loadSidebar();
    }
})();
