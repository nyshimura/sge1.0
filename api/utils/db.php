<?php
/**
 * Database connection utility.
 * Use get_db_connection() to get a PDO instance.
 */

if (!defined('ABSPATH')) {
    // Prevent direct access if ABSPATH is not defined
    // define('ABSPATH', dirname(__FILE__) . '/');
}

require_once __DIR__ . '/../config.php';

/**
 * Returns a new PDO connection instance.
 * @return PDO
 * @throws PDOException
 */
function get_db_connection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $conn;
    } catch (PDOException $e) {
        // Log the error but don't expose sensitive info in production
        error_log("Database Connection Error: " . $e->getMessage());
        throw $e;
    }
}
