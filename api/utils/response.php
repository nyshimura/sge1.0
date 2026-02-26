<?php
/**
 * Utility for sending JSON responses.
 */

if (!defined('ABSPATH')) {
    // Prevent direct access if ABSPATH is not defined (basic security)
    // define('ABSPATH', dirname(__FILE__) . '/');
}

/**
 * Sends a JSON response and exits the script.
 *
 * @param bool $success Whether the operation was successful.
 * @param array $data The data to include in the response.
 * @param int $statusCode The HTTP status code.
 * @return void
 */
function send_response($success, $data = [], $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');

    // Add CORS headers if not already set (though config.php should handle this ideally)
    if (!headers_sent()) {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    }

    $response = [
        'success' => $success,
        'data' => $data
    ];

    // If there's a 'message' key in data and success is false, ensure it's at the top level too for legacy compatibility if needed
    if (!$success && isset($data['message'])) {
        $response['message'] = $data['message'];
    } elseif ($success && isset($data['message'])) {
        $response['message'] = $data['message'];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
