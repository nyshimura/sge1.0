<?php
/**
 * handlers/user_handlers.php
 * Handles user profiles, dashboard data, and user management.
 */

if (!defined('ABSPATH')) {
    // define('ABSPATH', dirname(__FILE__) . '/');
}

require_once __DIR__ . '/../utils/audit.php';

// Helper to validate date format YYYY-MM-DD
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Returns dashboard data based on user role.
 */
function handle_get_dashboard_data($conn, $data) {
    if (session_status() == PHP_SESSION_NONE) session_start();

    $userId = $data['userId'] ?? $_SESSION['user_id'] ?? 0;
    $role = $data['role'] ?? $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';

    $response = [
        'courses' => [],
        'enrollments' => [],
        'attendance' => [],
        'payments' => [],
        'users' => [],
        'teachers' => []
    ];

    try {
        // Fetch Courses
        $sqlCourses = "SELECT c.*, u.firstName as teacherFirstName, u.lastName as teacherLastName
                       FROM courses c
                       LEFT JOIN users u ON c.teacherId = u.id
                       ORDER BY c.name ASC";
        $response['courses'] = $conn->query($sqlCourses)->fetchAll(PDO::FETCH_ASSOC);

        if ($role === 'admin' || $role === 'superadmin') {
            // Admin Data
            $response['enrollments'] = $conn->query("SELECT e.*, c.name as courseName, u.firstName, u.lastName FROM enrollments e JOIN courses c ON e.courseId = c.id JOIN users u ON e.studentId = u.id")->fetchAll(PDO::FETCH_ASSOC);
            $response['users'] = $conn->query("SELECT id, firstName, lastName, email, role, birthDate FROM users ORDER BY firstName ASC")->fetchAll(PDO::FETCH_ASSOC);
            $response['teachers'] = $conn->query("SELECT id, firstName, lastName FROM users WHERE role = 'teacher'")->fetchAll(PDO::FETCH_ASSOC);
            $response['payments'] = $conn->query("SELECT p.*, u.firstName, u.lastName, c.name as courseName FROM payments p JOIN users u ON p.studentId = u.id JOIN courses c ON p.courseId = c.id ORDER BY p.created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        } else if ($role === 'teacher') {
            // Teacher Data
            $stmt = $conn->prepare("SELECT * FROM courses WHERE teacherId = ?");
            $stmt->execute([$userId]);
            $response['myCourses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else if ($role === 'student') {
            // Student Data
            $stmt = $conn->prepare("SELECT e.*, c.name as courseName, c.dayOfWeek, c.startTime, c.endTime FROM enrollments e JOIN courses c ON e.courseId = c.id WHERE e.studentId = ?");
            $stmt->execute([$userId]);
            $response['myEnrollments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmtAtt = $conn->prepare("SELECT * FROM attendance WHERE studentId = ?");
            $stmtAtt->execute([$userId]);
            $response['attendance'] = $stmtAtt->fetchAll(PDO::FETCH_ASSOC);

            $stmtPay = $conn->prepare("
                SELECT p.*, c.name as courseName
                FROM payments p
                JOIN courses c ON p.courseId = c.id
                WHERE p.studentId = ?
                ORDER BY p.dueDate ASC
            ");
            $stmtPay->execute([$userId]);
            $response['payments'] = $stmtPay->fetchAll(PDO::FETCH_ASSOC);
        }

        send_response(true, $response);

    } catch (PDOException $e) {
        error_log("Database Error (Dashboard): " . $e->getMessage());
        send_response(false, ['message' => 'Failed to fetch dashboard data.'], 500);
    }
}

/**
 * Returns a list of users, optionally filtered.
 */
function handle_get_filtered_users($conn, $data) {
    $roleFilter = $data['role'] ?? '';
    $search = $data['search'] ?? '';
    $courseId = isset($data['courseId']) ? filter_var($data['courseId'], FILTER_VALIDATE_INT) : 0;

    $sql = "SELECT DISTINCT u.id, u.firstName, u.lastName, u.email, u.role, u.birthDate, u.created_at FROM users u WHERE 1=1";
    $params = [];

    if (!empty($roleFilter) && $roleFilter !== 'all') {
        $sql .= " AND u.role = ?";
        $params[] = $roleFilter;
    }

    if ($courseId > 0) {
        $sql .= " AND (u.id IN (SELECT studentId FROM enrollments WHERE courseId = ?) OR u.id IN (SELECT teacherId FROM courses WHERE id = ?))";
        $params[] = $courseId;
        $params[] = $courseId;
    }

    if (!empty($search)) {
        $sql .= " AND (u.firstName LIKE ? OR u.lastName LIKE ? OR u.email LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $sql .= " ORDER BY u.firstName ASC";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Log sensitive search if search term used
        if (!empty($search) && isset($_SESSION['user_id'])) {
             AuditLogger::log($conn, $_SESSION['user_id'], 'SEARCH_USERS', ['term' => $search]);
        }

        send_response(true, ['users' => $users]);
    } catch (PDOException $e) {
        error_log("Database Error (Filtered Users): " . $e->getMessage());
        send_response(false, ['message' => 'Failed to fetch users.'], 500);
    }
}

/**
 * Updates a user's role (Admin only).
 */
function handle_update_user_role($conn, $data) {
    $userId = isset($data['userId']) ? filter_var($data['userId'], FILTER_VALIDATE_INT) : 0;
    $newRole = $data['newRole'] ?? '';

    if ($userId <= 0) {
        send_response(false, ['message' => 'Invalid user ID.'], 400);
    }

    $allowedRoles = ['unassigned', 'student', 'teacher', 'admin', 'superadmin'];
    if (!in_array($newRole, $allowedRoles)) {
        send_response(false, ['message' => "Invalid role: $newRole"], 400);
    }

    try {
        $sql = "UPDATE users SET role = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $success = $stmt->execute([$newRole, $userId]);

        if ($success) {
            if (isset($_SESSION['user_id'])) {
                AuditLogger::log($conn, $_SESSION['user_id'], 'UPDATE_ROLE', ['target_id' => $userId, 'new_role' => $newRole]);
            }
            send_response(true, ['message' => 'User role updated successfully.']);
        } else {
            send_response(false, ['message' => 'Failed to update role.'], 500);
        }
    } catch (PDOException $e) {
        error_log("Database Error (Update Role): " . $e->getMessage());
        send_response(false, ['message' => 'Database error.'], 500);
    }
}

/**
 * Returns list of teachers.
 */
function handle_get_teachers($conn, $data) {
    try {
        $sql = "SELECT id, firstName, lastName, email FROM users WHERE role = 'teacher' ORDER BY firstName ASC";
        $stmt = $conn->query($sql);
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        send_response(true, ['teachers' => $teachers]);
    } catch (PDOException $e) {
        error_log("Database Error (Get Teachers): " . $e->getMessage());
        send_response(false, ['message' => 'Failed to fetch teachers.'], 500);
    }
}

/**
 * Returns list of active students.
 */
function handle_get_active_students($conn, $data) {
    try {
        $sql = "SELECT id, firstName, lastName, email FROM users WHERE role = 'student' ORDER BY firstName ASC";
        $stmt = $conn->query($sql);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        send_response(true, ['students' => $students]);
    } catch (PDOException $e) {
        error_log("Database Error (Get Students): " . $e->getMessage());
        send_response(false, ['message' => 'Failed to fetch students.'], 500);
    }
}

/**
 * Returns profile data for a specific user.
 */
function handle_get_profile_data($conn, $data) {
    $userId = 0;
    if (isset($data['userId'])) $userId = filter_var($data['userId'], FILTER_VALIDATE_INT);
    elseif (isset($data['id'])) $userId = filter_var($data['id'], FILTER_VALIDATE_INT);

    if ($userId <= 0) {
        if (session_status() == PHP_SESSION_NONE) session_start();
        $userId = $_SESSION['user_id'] ?? 0;
    }

    if ($userId <= 0) {
        send_response(false, ['message' => 'Invalid user ID.'], 400);
    }

    try {
        $stmt = $conn->prepare("SELECT id, firstName, lastName, email, role, profilePicture, address, rg, cpf, phone, birthDate, guardianName, guardianEmail, guardianPhone, guardianRG, guardianCPF FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Log access to profile (LGPD)
            if (session_status() == PHP_SESSION_NONE) session_start();
            $viewerId = $_SESSION['user_id'] ?? 0;
            if ($viewerId > 0 && $viewerId != $userId) { // Log only if viewing someone else
                 AuditLogger::log($conn, $viewerId, 'VIEW_PROFILE', ['target_id' => $userId]);
            }

            $stmtEnroll = $conn->prepare("SELECT e.*, c.name as courseName FROM enrollments e JOIN courses c ON e.courseId = c.id WHERE e.studentId = ?");
            $stmtEnroll->execute([$userId]);
            $enrollments = $stmtEnroll->fetchAll(PDO::FETCH_ASSOC);

            $stmtPay = $conn->prepare("SELECT p.*, c.name as courseName FROM payments p JOIN courses c ON p.courseId = c.id WHERE p.studentId = ? ORDER BY p.dueDate DESC");
            $stmtPay->execute([$userId]);
            $payments = $stmtPay->fetchAll(PDO::FETCH_ASSOC);

            send_response(true, ['user' => $user, 'enrollments' => $enrollments, 'payments' => $payments]);
        } else {
            send_response(false, ['message' => 'User not found.'], 404);
        }
    } catch (PDOException $e) {
        error_log("Database Error (Get Profile): " . $e->getMessage());
        send_response(false, ['message' => 'Failed to fetch profile.'], 500);
    }
}

/**
 * Updates user profile data.
 */
function handle_update_user_profile($conn, $data) {
    $userId = isset($data['id']) ? filter_var($data['id'], FILTER_VALIDATE_INT) : 0;
    if (session_status() == PHP_SESSION_NONE) session_start();

    $currentUserId = $_SESSION['user_id'] ?? 0;
    $currentUserRole = $_SESSION['role'] ?? $_SESSION['user_role'] ?? '';

    if ($userId <= 0) $userId = $currentUserId;

    if ($userId <= 0) {
        send_response(false, ['message' => 'Invalid user ID.'], 400);
    }

    // Authorization Check: User can update own profile, Admin/Superadmin can update any
    if ($userId != $currentUserId && $currentUserRole !== 'admin' && $currentUserRole !== 'superadmin') {
        send_response(false, ['message' => 'Permission denied.'], 403);
    }

    $fieldsToUpdate = ['firstName', 'lastName', 'email', 'phone', 'address', 'rg', 'cpf', 'birthDate', 'guardianName', 'guardianEmail', 'guardianPhone', 'guardianRG', 'guardianCPF', 'profilePicture'];
    $fields = [];
    $params = [];

    foreach($fieldsToUpdate as $f) {
        if (array_key_exists($f, $data)) {
            $fields[] = "$f = :$f";
            $params[":$f"] = trim($data[$f]);
        }
    }

    if (empty($fields)) {
        send_response(true, ['message' => 'No changes submitted.']);
    }

    try {
        $params[':id'] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        send_response(true, ['message' => 'Profile updated successfully.']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            send_response(false, ['message' => 'Email already in use.'], 409);
        }
        error_log("Database Error (Update Profile): " . $e->getMessage());
        send_response(false, ['message' => 'Failed to update profile.'], 500);
    }
}

// Aliases for compatibility
function handle_get_user_profile($conn, $data) { handle_get_profile_data($conn, $data); }
function handle_getUserProfile($conn, $data) { handle_get_profile_data($conn, $data); }
function handle_updateUserProfile($conn, $data) { handle_update_user_profile($conn, $data); }
function handle_list_users($conn, $data) { handle_get_filtered_users($conn, $data); }
function handle_create_user($conn, $data) {
    // Usually calls auth_handlers.php -> handle_register but internal
    require_once __DIR__ . '/auth_handlers.php';
    handle_register($conn, $data);
}
function handle_delete_user($conn, $data) {
    if (session_status() == PHP_SESSION_NONE) session_start();
    $currentUserRole = $_SESSION['user_role'] ?? '';
    if ($currentUserRole !== 'admin' && $currentUserRole !== 'superadmin') {
         send_response(false, ['message' => 'Permission denied.'], 403);
    }

    $userId = isset($data['id']) ? filter_var($data['id'], FILTER_VALIDATE_INT) : 0;
    if ($userId <= 0) send_response(false, ['message' => 'Invalid ID.'], 400);

    try {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$userId])) {
            send_response(true, ['message' => 'User deleted successfully.']);
        } else {
            send_response(false, ['message' => 'Failed to delete user.'], 500);
        }
    } catch (PDOException $e) {
        error_log("Delete User Error: " . $e->getMessage());
        send_response(false, ['message' => 'Database error.'], 500);
    }
}
