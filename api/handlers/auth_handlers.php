<?php
/**
 * handlers/auth_handlers.php
 * Handles authentication, registration, and password management.
 */

if (!defined('ABSPATH')) {
    // Prevent direct access if ABSPATH is not defined
    // define('ABSPATH', dirname(__FILE__) . '/');
}

// Helper functions (could be moved to a shared helper file)
// send_response is available globally via api/utils/response.php

/**
 * Handles user login.
 */
function handle_login($conn, $params) {
    if (empty($params['email']) || empty($params['password'])) {
        send_response(false, ['message' => 'E-mail and password are required.'], 400);
    }

    $email = trim($params['email']);
    $password = $params['password'];

    try {
        $sql = "SELECT id, password_hash, role, firstName FROM users WHERE email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Login successful
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_firstName'] = $user['firstName'];

            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);

            // Log login
            // error_log("[AUTH] Login successful for user ID: " . $user['id']);

            send_response(true, [
                'message' => 'Login successful!',
                'user' => [
                    'id' => $user['id'],
                    'role' => $user['role'],
                    'firstName' => $user['firstName']
                 ]
            ]);
        } else {
            // Invalid credentials
            // error_log("[AUTH] Login failed for email: " . $email);
            send_response(false, ['message' => 'Invalid email or password.'], 401);
        }
    } catch (PDOException $e) {
        error_log("Database Error (Login): " . $e->getMessage());
        send_response(false, ['message' => 'Database error during login.'], 500);
    }
}

/**
 * Handles user registration.
 */
function handle_register($conn, $params) {
    if (empty($params['firstName']) || empty($params['email']) || empty($params['password']) || empty($params['confirmPassword'])) {
        send_response(false, ['message' => 'All fields are required.'], 400);
    }

    $firstName = trim($params['firstName']);
    $email = trim($params['email']);
    $password = $params['password'];
    $confirmPassword = $params['confirmPassword'];
    $role = $params['role'] ?? 'student'; // Default to student

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        send_response(false, ['message' => 'Invalid email format.'], 400);
    }

    // Validate password strength
    if (strlen($password) < 8) {
        send_response(false, ['message' => 'Password must be at least 8 characters long.'], 400);
    }
    if ($password !== $confirmPassword) {
        send_response(false, ['message' => 'Passwords do not match.'], 400);
    }

    try {
        // Check if email already exists
        $sqlCheck = "SELECT id FROM users WHERE email = :email";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bindParam(':email', $email);
        $stmtCheck->execute();
        if ($stmtCheck->fetch()) {
            send_response(false, ['message' => 'Email is already registered.'], 409);
        }

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $sqlInsert = "INSERT INTO users (firstName, email, password_hash, role, created_at) VALUES (:firstName, :email, :passwordHash, :role, NOW())";
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->bindParam(':firstName', $firstName);
        $stmtInsert->bindParam(':email', $email);
        $stmtInsert->bindParam(':passwordHash', $passwordHash);
        $stmtInsert->bindParam(':role', $role);

        if ($stmtInsert->execute()) {
            send_response(true, ['message' => 'User registered successfully!']);
        } else {
            error_log("Database Error (Register Insert): " . implode(":", $stmtInsert->errorInfo()));
            send_response(false, ['message' => 'Failed to register user.'], 500);
        }

    } catch (PDOException $e) {
        error_log("Database Error (Register): " . $e->getMessage());
        send_response(false, ['message' => 'Database error during registration.'], 500);
    }
}

/**
 * Handles password change for logged-in users.
 */
function handle_change_password($conn, $params) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        send_response(false, ['message' => 'Unauthorized.'], 401);
    }
    $userId = $_SESSION['user_id'];

    if (empty($params['currentPassword']) || empty($params['newPassword']) || empty($params['confirmPassword'])) {
        send_response(false, ['message' => 'All password fields are required.'], 400);
    }

    $currentPassword = $params['currentPassword'];
    $newPassword = $params['newPassword'];
    $confirmPassword = $params['confirmPassword'];

    if (strlen($newPassword) < 8) {
        send_response(false, ['message' => 'New password must be at least 8 characters long.'], 400);
    }
    if ($newPassword !== $confirmPassword) {
        send_response(false, ['message' => 'New passwords do not match.'], 400);
    }

    try {
        // Verify current password
        $sqlSelect = "SELECT password_hash FROM users WHERE id = :userId";
        $stmtSelect = $conn->prepare($sqlSelect);
        $stmtSelect->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmtSelect->execute();
        $user = $stmtSelect->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            send_response(false, ['message' => 'Current password is incorrect.'], 401);
        }

        // Update password
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $sqlUpdate = "UPDATE users SET password_hash = :newPasswordHash WHERE id = :userId";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bindParam(':newPasswordHash', $newPasswordHash);
        $stmtUpdate->bindParam(':userId', $userId, PDO::PARAM_INT);

        if ($stmtUpdate->execute()) {
            send_response(true, ['message' => 'Password changed successfully!']);
        } else {
            send_response(false, ['message' => 'Failed to update password.'], 500);
        }

    } catch (PDOException $e) {
        error_log("Database Error (Change Password): " . $e->getMessage());
        send_response(false, ['message' => 'Database error.'], 500);
    }
}

/**
 * Handles password reset request (sends email).
 */
function handle_request_password_reset($conn, $params) {
    if (empty($params['email']) || !filter_var($params['email'], FILTER_VALIDATE_EMAIL)) {
        send_response(false, ['message' => 'Invalid email address.'], 400);
    }
    $email = $params['email'];

    // Include email helper
    require_once __DIR__ . '/../helpers/email_helper.php';

    try {
        $sql = "SELECT id, firstName FROM users WHERE email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expiresAt = new DateTime('+1 hour');
            $expiresAtFormatted = $expiresAt->format('Y-m-d H:i:s');

            $sqlUpdate = "UPDATE users SET reset_token = :token, reset_token_expires_at = :expires WHERE id = :id";
            $stmtUpdate = $conn->prepare($sqlUpdate);
            $stmtUpdate->bindParam(':token', $token);
            $stmtUpdate->bindParam(':expires', $expiresAtFormatted);
            $stmtUpdate->bindParam(':id', $user['id']);

            if ($stmtUpdate->execute()) {
                // Get site URL from system settings
                require_once __DIR__ . '/system_handlers.php';
                $settings = get_system_settings($conn);
                $siteUrl = $settings['site_url'] ?? 'http://' . $_SERVER['HTTP_HOST'] . '/';

                // Construct reset link
                $resetLink = rtrim($siteUrl, '/') . '/#resetPassword?token=' . $token;

                // Send email
                if (send_password_reset_email($conn, $email, $user['id'], $resetLink)) {
                    send_response(true, ['message' => 'Reset link sent to your email. Check your inbox and spam folder.']);
                } else {
                    error_log("Failed to send reset email to $email");
                    send_response(false, ['message' => 'Failed to send reset email. Please try again later.'], 500);
                }
            } else {
                send_response(false, ['message' => 'Database error.'], 500);
            }
        } else {
            // User not found, but return success to prevent email enumeration
            send_response(true, ['message' => 'Reset link sent to your email. Check your inbox and spam folder.']);
        }

    } catch (PDOException $e) {
        error_log("Database Error (Request Reset): " . $e->getMessage());
        send_response(false, ['message' => 'Database error.'], 500);
    } catch (Exception $e) {
        error_log("General Error (Request Reset): " . $e->getMessage());
        send_response(false, ['message' => 'An error occurred.'], 500);
    }
}

/**
 * Handles password reset with token.
 */
function handle_reset_password($conn, $params) {
    if (empty($params['token']) || empty($params['newPassword']) || empty($params['confirmPassword'])) {
        send_response(false, ['message' => 'Token and passwords are required.'], 400);
    }

    $token = $params['token'];
    $newPassword = $params['newPassword'];
    $confirmPassword = $params['confirmPassword'];

    if (strlen($newPassword) < 8) {
        send_response(false, ['message' => 'Password must be at least 8 characters long.'], 400);
    }
    if ($newPassword !== $confirmPassword) {
        send_response(false, ['message' => 'Passwords do not match.'], 400);
    }

    try {
        $sql = "SELECT id, reset_token_expires_at FROM users WHERE reset_token = :token";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $now = new DateTime();
            $expiresAt = new DateTime($user['reset_token_expires_at']);

            if ($now > $expiresAt) {
                send_response(false, ['message' => 'Reset token has expired.'], 400);
            } else {
                $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $sqlUpdate = "UPDATE users SET password_hash = :newPasswordHash, reset_token = NULL, reset_token_expires_at = NULL WHERE id = :id";
                $stmtUpdate = $conn->prepare($sqlUpdate);
                $stmtUpdate->bindParam(':newPasswordHash', $newPasswordHash);
                $stmtUpdate->bindParam(':id', $user['id']);

                if ($stmtUpdate->execute()) {
                    send_response(true, ['message' => 'Password reset successfully! You can now login.']);
                } else {
                    send_response(false, ['message' => 'Failed to update password.'], 500);
                }
            }
        } else {
            send_response(false, ['message' => 'Invalid reset token.'], 400);
        }

    } catch (PDOException $e) {
        error_log("Database Error (Reset Password): " . $e->getMessage());
        send_response(false, ['message' => 'Database error.'], 500);
    } catch (Exception $e) {
        error_log("General Error (Reset Password): " . $e->getMessage());
        send_response(false, ['message' => 'An error occurred.'], 500);
    }
}
function handle_logout($conn, $params) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    session_destroy();
    send_response(true, ['message' => 'Logged out successfully.']);
}
