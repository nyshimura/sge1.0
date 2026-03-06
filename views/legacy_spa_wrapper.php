<?php
/**
 * views/legacy_spa_wrapper.php
 * Wraps the legacy JS SPA so it can still be used for features not yet migrated to pure PHP.
 */

// Basic security check (already done in router, but good practice)
if (!isset($_SESSION['user_id'])) {
    header('Location: ?p=login');
    exit;
}

// 1. We need to pass the session data to the SPA so it knows who is logged in without needing to login again.
// The SPA previously used localStorage. We can inject a script to set this up.
$currentUserJson = json_encode([
    'id' => $_SESSION['user_id'],
    'role' => $_SESSION['user_role'],
    'firstName' => $_SESSION['user_firstName']
]);

// 2. Load the legacy HTML
$spaContent = file_get_contents(__DIR__ . '/../legacy_spa/index.html');

// 3. Fix paths for CSS and JS since we are running from the root now
$spaContent = str_replace('href="index.css"', 'href="legacy_spa/index.css"', $spaContent);
$spaContent = str_replace('href="../sge/index.css"', '', $spaContent);
$spaContent = str_replace('src="index.js"', 'src="legacy_spa/index.js"', $spaContent);

// 4. Inject the session script just before the closing head tag
$injectionScript = "
<script>
    // Sync PHP session to SPA LocalStorage
    localStorage.setItem('currentUser', JSON.stringify({$currentUserJson}));

    // Add a 'Back to Dashboard' button since we are in a wrapper
    document.addEventListener('DOMContentLoaded', () => {
        const header = document.getElementById('app-header');
        if(header) {
            const backBtn = document.createElement('a');
            backBtn.href = '?p=dashboard';
            backBtn.innerHTML = '&larr; Voltar ao Novo Painel';
            backBtn.style.cssText = 'display:inline-block; padding: 10px 15px; background: #4f46e5; color: white; text-decoration: none; font-weight: bold; margin: 10px; border-radius: 5px; font-family: sans-serif;';
            header.insertBefore(backBtn, header.firstChild);
        }
    });
</script>
</head>";

$spaContent = str_replace('</head>', $injectionScript, $spaContent);

// 5. Output
echo $spaContent;
