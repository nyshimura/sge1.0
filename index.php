<?php
/**
 * Root index.php - Secure Front Controller for Pure PHP SGE
 */

session_start();

// Load configuration and utilities
require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/utils/db.php';

// Simple Router
$page = $_GET['p'] ?? 'login';

// If user is not logged in, force login page
if (!isset($_SESSION['user_id']) && $page !== 'login') {
    header('Location: ?p=login');
    exit;
}

// If user is logged in and tries to access login, redirect to dashboard
if (isset($_SESSION['user_id']) && $page === 'login') {
    header('Location: ?p=dashboard');
    exit;
}

$role = $_SESSION['user_role'] ?? '';

// Dynamic dashboard routing based on role
if ($page === 'dashboard') {
    if ($role === 'superadmin' || $role === 'admin') {
        $page = 'admin_dashboard';
    } elseif ($role === 'teacher') {
        $page = 'teacher_dashboard';
    } elseif ($role === 'student') {
        $page = 'student_dashboard';
    } else {
        die("Perfil sem acesso ao painel.");
    }
}

// Define routes with required roles for authorization
$routes = [
    'login' => ['file' => 'views/auth/login.php', 'roles' => ['all']],
    'logout' => ['file' => 'views/auth/logout.php', 'roles' => ['all']],
    'admin_dashboard' => ['file' => 'views/admin/dashboard.php', 'roles' => ['admin', 'superadmin']],
    'student_dashboard' => ['file' => 'views/student/dashboard.php', 'roles' => ['student']],
    'teacher_dashboard' => ['file' => 'views/teacher/dashboard.php', 'roles' => ['teacher']],
    'student_payments' => ['file' => 'views/student/payments.php', 'roles' => ['student']],
    'teacher_attendance' => ['file' => 'views/teacher/attendance.php', 'roles' => ['teacher', 'admin', 'superadmin']],
    'legacy_app' => ['file' => 'views/legacy_spa_wrapper.php', 'roles' => ['all']], // Catch-all for missing features
];

// Authorization Check
if (array_key_exists($page, $routes)) {
    $routeInfo = $routes[$page];
    $allowedRoles = $routeInfo['roles'];

    // Check if route requires specific roles and user has it
    if (!in_array('all', $allowedRoles) && !in_array($role, $allowedRoles)) {
        // User is authenticated but forbidden to access this specific route
        http_response_code(403);
        $viewFile = 'views/403.php'; // Unauthorized
    } else {
        $viewFile = $routeInfo['file'];
    }
} else {
    // Route not found
    http_response_code(404);
    $viewFile = 'views/404.php';
}

// Load the requested view
if (file_exists($viewFile)) {
    require $viewFile;
} else {
    echo "<h1>Erro</h1>";
    echo "<p>Arquivo de view não encontrado: " . htmlspecialchars($viewFile) . "</p>";
}
