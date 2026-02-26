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
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

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
