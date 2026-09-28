// global.js
//
// Sidebar navigation itself is wired directly via onclick attributes in
// partials/sidebar.html (the single shared sidebar) — no link table needed
// here anymore.

document.addEventListener("DOMContentLoaded", () => {
    injectCsrfTokens();
    // other setup calls
});

// Native (non-fetch) form submissions can't set a custom header, so the CSRF
// token is carried as a hidden field instead. security.php's verify_csrf()
// accepts either the X-CSRF-Token header or this csrf_token POST field.
function injectCsrfTokens() {
    const applyToken = (token) => {
        document.querySelectorAll('form[method="post" i]').forEach(form => {
            let input = form.querySelector('input[name="csrf_token"]');
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'csrf_token';
                form.appendChild(input);
            }
            input.value = token;
        });
    };

    const cached = sessionStorage.getItem('csrfToken');
    if (cached) {
        applyToken(cached);
        return;
    }

    // No cached token (e.g. a bookmarked page opened in a fresh tab) - fetch one
    // for the existing server session, if any.
    fetch('/sts/INDEX-PHP/csrf_token.php')
        .then(response => response.json())
        .then(data => {
            if (data.token) {
                sessionStorage.setItem('csrfToken', data.token);
                applyToken(data.token);
            }
        })
        .catch(error => console.error('Error fetching CSRF token:', error));
}

function recordFormSubmitTime() {
    document.getElementById('form_submit_time').value = new Date().toISOString();
}
