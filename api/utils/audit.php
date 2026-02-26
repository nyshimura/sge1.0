<?php
/**
 * api/utils/audit.php
 * LGPD Audit Logger Utility
 */

if (!defined('ABSPATH')) {
    // define('ABSPATH', dirname(__FILE__) . '/');
}

class AuditLogger {

    /**
     * Logs an action to the audit_logs table.
     *
     * @param PDO $conn Database connection.
     * @param int|null $userId User ID performing the action (null if anonymous/system).
     * @param string $action Short action code (e.g., 'LOGIN', 'VIEW_PROFILE').
     * @param string|array $details Details about the action. Arrays will be JSON encoded.
     * @return bool Success status.
     */
    public static function log($conn, $userId, $action, $details = null) {
        try {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';

            if (is_array($details) || is_object($details)) {
                $details = json_encode($details, JSON_UNESCAPED_UNICODE);
            }

            $sql = "INSERT INTO audit_logs (user_id, action, details, ip_address, user_agent, created_at)
                    VALUES (:user_id, :action, :details, :ip_address, :user_agent, NOW())";

            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, $userId ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':action', substr($action, 0, 50)); // Limit to 50 chars
            $stmt->bindValue(':details', $details);
            $stmt->bindValue(':ip_address', substr($ipAddress, 0, 45));
            $stmt->bindValue(':user_agent', substr($userAgent, 0, 255));

            return $stmt->execute();

        } catch (PDOException $e) {
            // Do not throw error to avoid breaking the main flow, but log to file
            error_log("Audit Log Error: " . $e->getMessage());
            return false;
        }
    }
}
