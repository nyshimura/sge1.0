<?php
/**
 * Main API Entry Point
 */

// Load configuration and utilities
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils/response.php';
require_once __DIR__ . '/utils/db.php';

// Start Session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get Database Connection
try {
    $conn = get_db_connection();
} catch (PDOException $e) {
    send_response(false, ['message' => 'Database connection failed. Please check config.php.'], 500);
}

// Determine Action
$action = $_REQUEST['action'] ?? '';

// Handler Map - Organize handlers by module for better maintainability
$handlers = [
    'auth' => [
        'login', 'register', 'logout', 'requestPasswordReset', 'resetPassword', 'changePassword'
    ],
    'user' => [
        'getUserProfile', 'getProfileData', 'updateUserProfile', 'uploadProfilePicture',
        'listUsers', 'createUser', 'deleteUser', 'getFilteredUsers', 'updateUserRole',
        'getTeachers', 'getActiveStudents', 'getDashboardData'
    ],
    'system' => [
        'getSchoolProfile', 'updateSchoolProfile', 'uploadSchoolLogo', 'getSystemSettings',
        'updateSystemSettings', 'updateDocumentTemplates', 'exportDatabase'
    ],
    'course' => [
        'createCourse', 'updateCourse', 'getCourses', 'getCourseDetails', 'endCourse',
        'reopenCourse', 'saveAttendance', 'getAttendance', 'getAttendanceData'
    ],
    'enrollment' => [
        'initiateEnrollment', 'getEnrollmentDocuments', 'submitEnrollment', 'approveEnrollment',
        'cancelEnrollment', 'reactivateEnrollment', 'updateEnrollmentDetails', 'submitReenrollment'
    ],
    'financial' => [
        'generatePayment', 'getFinancialDashboard', 'getFinancialDashboardData', 'getFinancialReport',
        'getDefaulters', 'getDefaultersReport', 'updatePaymentStatus', 'bulkUpdatePaymentStatus',
        'getPaymentHistory', 'getStudentPayments'
    ],
    'receipt' => [
        'generateReceiptPdf'
    ],
    'certificate' => [
        'generateCertificate', 'viewCertificate', 'verifyCertificate', 'getMyCertificates', 'getStudentCertificates'
    ],
    'contract' => [
        'generateContractPdf'
    ],
    'image_term' => [
        'generateImageTermsPdf'
    ],
    'ai' => [
        'generateDescriptionAI', 'generateAiDescription'
    ],
    'event' => [
        'createEvent', 'listEvents', 'getEventDetails', 'enrollEvent', 'listEventParticipants'
    ]
];

// Helper to find handler file for an action
function getHandlerFile($action, $handlers) {
    foreach ($handlers as $module => $actions) {
        if (in_array($action, $actions)) {
            // Map module name to filename if it differs (e.g., 'auth' -> 'auth_handlers.php')
            // Using a simple convention: module_handlers.php or custom
            switch ($module) {
                case 'receipt': return __DIR__ . '/handlers/receipt_handler.php';
                case 'certificate': return __DIR__ . '/handlers/certificate_handler.php';
                case 'contract': return __DIR__ . '/handlers/contract_handler.php';
                case 'image_term': return __DIR__ . '/handlers/image_term_handler.php';
                default: return __DIR__ . '/handlers/' . $module . '_handlers.php';
            }
        }
    }
    return null;
}

// Find Handler
$handlerFile = getHandlerFile($action, $handlers);

if (!$handlerFile || !file_exists($handlerFile)) {
    send_response(false, ['message' => 'Action not found or invalid handler.'], 404);
}

// Check Authentication (except for public actions)
$publicActions = [
    'login', 'register', 'requestPasswordReset', 'resetPassword',
    'getSchoolProfile', 'verifyCertificate', 'generateContractPdf'
];

if (!isset($_SESSION['user_id']) && !in_array($action, $publicActions)) {
    send_response(false, ['message' => 'Unauthorized access. Please login.'], 401);
}

// Load Handler
require_once $handlerFile;

// Prepare Parameters (Merge Request & JSON Body)
$params = $_REQUEST;
$jsonInput = file_get_contents('php://input');
$jsonData = json_decode($jsonInput, true);
if (is_array($jsonData)) {
    $params = array_merge($params, $jsonData);
}
// Remove action from params to keep it clean (optional)
unset($params['action']);

// Call Function
// Convert action to function name: 'updateUserProfile' -> 'handle_update_user_profile'
function fromCamelCase($input) {
    return ltrim(strtolower(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '_$0', $input)), '_');
}

$functionNameSnake = 'handle_' . fromCamelCase($action);
$functionNameExact = 'handle_' . $action;

if (function_exists($functionNameSnake)) {
    $functionNameSnake($conn, $params);
} elseif (function_exists($functionNameExact)) {
    $functionNameExact($conn, $params);
} else {
    send_response(false, ['message' => "Handler function for '$action' not found."], 500);
}
