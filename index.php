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

// Load the requested view with a global try...catch for database errors (Error 500 mitigation)
try {
    if (file_exists($viewFile)) {
        require $viewFile;
    } else {
        echo "<h1>Erro do Sistema</h1>";
        echo "<p>Arquivo de visualização não encontrado: " . htmlspecialchars($viewFile) . "</p>";
        echo "<p>Por favor, verifique se todos os arquivos foram enviados corretamente para o servidor Hostinger.</p>";
    }
} catch (PDOException $e) {
    // Catch database connection or query errors
    http_response_code(500);
    echo "<div style='font-family: sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; border: 1px solid #f5c6cb; background-color: #f8d7da; color: #721c24; border-radius: 5px;'>";
    echo "<h2 style='margin-top: 0;'>Erro de Conexão com o Banco de Dados</h2>";
    echo "<p>Ocorreu um erro ao tentar se conectar ao banco de dados ou executar uma consulta. Isso é comum após enviar os arquivos pela primeira vez para o Hostinger.</p>";
    echo "<p><strong>O que fazer:</strong></p>";
    echo "<ol>";
    echo "<li>Abra o arquivo <code>api/config.php</code> e verifique se as constantes <code>DB_HOST</code>, <code>DB_USER</code>, <code>DB_PASS</code> e <code>DB_NAME</code> estão corretas para o seu banco no Hostinger.</li>";
    echo "<li>Verifique se você importou o arquivo <code>schema.sql</code> no seu phpMyAdmin.</li>";
    echo "</ol>";
    echo "<p style='font-size: 0.8em; color: #555; margin-top: 20px;'><strong>Detalhes Técnicos (Para o Suporte):</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
} catch (Exception $e) {
    // Catch other general errors
    http_response_code(500);
    echo "<h1>Erro Interno do Servidor (500)</h1>";
    echo "<p>Um erro inesperado ocorreu. Detalhes: " . htmlspecialchars($e->getMessage()) . "</p>";
}
