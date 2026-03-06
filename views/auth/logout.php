<?php
/**
 * views/auth/logout.php
 */
session_start();

if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../../api/config.php';
    require_once __DIR__ . '/../../api/utils/db.php';
    require_once __DIR__ . '/../../api/utils/audit.php';

    try {
        $conn = get_db_connection();
        AuditLogger::log($conn, $_SESSION['user_id'], 'LOGOUT_PHP_UI');
    } catch (Exception $e) {}
}

session_destroy();
header('Location: ?p=login');
exit;
