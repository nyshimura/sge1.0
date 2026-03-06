<?php
/**
 * Configuration File
 */

// Error Reporting
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Set timezone
date_default_timezone_set('America/Sao_Paulo');

// CORS Configuration
// Moved Content-Type and CORS headers to api/utils/response.php to allow
// HTML rendering for the new Pure PHP Frontend.

// Database Credentials
// Use environment variables if available, otherwise fallback to defaults
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'user');
define('DB_PASS', getenv('DB_PASS') ?: 'senha');
define('DB_NAME', getenv('DB_NAME') ?: 'banco');

// GitHub Repo Info for Updates (optional)
define('REPO_OWNER', 'nyshimura');
define('REPO_NAME', 'sge');
define('REPO_BRANCH', 'main');
