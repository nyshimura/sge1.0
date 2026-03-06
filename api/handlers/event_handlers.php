<?php
/**
 * api/handlers/event_handlers.php
 * Handles events, tours, presentations, and liability terms.
 */

if (!defined('ABSPATH')) {
    // define('ABSPATH', dirname(__FILE__) . '/');
}

require_once __DIR__ . '/../utils/audit.php';

/**
 * Creates a new event (Admin only).
 */
function handle_create_event($conn, $params) {
    if (session_status() == PHP_SESSION_NONE) session_start();

    // Check Permission
    $role = $_SESSION['user_role'] ?? '';
    if ($role !== 'admin' && $role !== 'superadmin') {
        send_response(false, ['message' => 'Unauthorized.'], 403);
    }

    // Validation
    if (empty($params['title']) || empty($params['eventDate'])) {
        send_response(false, ['message' => 'Title and Date are required.'], 400);
    }

    try {
        $sql = "INSERT INTO events (title, description, event_date, location, term_text, created_by, created_at)
                VALUES (:title, :description, :event_date, :location, :term_text, :created_by, NOW())";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':title' => trim($params['title']),
            ':description' => trim($params['description'] ?? ''),
            ':event_date' => $params['eventDate'],
            ':location' => trim($params['location'] ?? ''),
            ':term_text' => trim($params['termText'] ?? ''), // Liability term
            ':created_by' => $_SESSION['user_id']
        ]);

        $eventId = $conn->lastInsertId();

        // Log Action
        AuditLogger::log($conn, $_SESSION['user_id'], 'CREATE_EVENT', ['event_id' => $eventId, 'title' => $params['title']]);

        send_response(true, ['message' => 'Event created successfully.', 'eventId' => $eventId]);

    } catch (PDOException $e) {
        error_log("Create Event Error: " . $e->getMessage());
        send_response(false, ['message' => 'Database error.'], 500);
    }
}

/**
 * Lists all events.
 */
function handle_list_events($conn, $params) {
    if (session_status() == PHP_SESSION_NONE) session_start();

    try {
        $sql = "SELECT id, title, description, event_date, location FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC";
        // If admin, show all including past
        if (($_SESSION['user_role'] ?? '') === 'admin') {
             $sql = "SELECT id, title, description, event_date, location FROM events ORDER BY event_date DESC";
        }

        $stmt = $conn->query($sql);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        send_response(true, ['events' => $events]);
    } catch (PDOException $e) {
        send_response(false, ['message' => 'Failed to fetch events.'], 500);
    }
}

/**
 * Gets details of a specific event (including term text).
 */
function handle_get_event_details($conn, $params) {
    if (session_status() == PHP_SESSION_NONE) session_start();

    $eventId = filter_var($params['eventId'] ?? 0, FILTER_VALIDATE_INT);
    if ($eventId <= 0) send_response(false, ['message' => 'Invalid Event ID.'], 400);

    try {
        $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$event) send_response(false, ['message' => 'Event not found.'], 404);

        // If student is viewing, check if they already enrolled/accepted
        $enrollment = null;
        if (isset($_SESSION['user_id'])) {
            $stmtEnr = $conn->prepare("SELECT * FROM event_enrollments WHERE event_id = ? AND student_id = ?");
            $stmtEnr->execute([$eventId, $_SESSION['user_id']]);
            $enrollment = $stmtEnr->fetch(PDO::FETCH_ASSOC);
        }

        send_response(true, ['event' => $event, 'myEnrollment' => $enrollment]);

    } catch (PDOException $e) {
        send_response(false, ['message' => 'Database error.'], 500);
    }
}

/**
 * Enrolls a student in an event (Accepting terms).
 */
function handle_enroll_event($conn, $params) {
    if (session_status() == PHP_SESSION_NONE) session_start();

    if (!isset($_SESSION['user_id'])) {
        send_response(false, ['message' => 'Login required.'], 401);
    }

    $eventId = filter_var($params['eventId'] ?? 0, FILTER_VALIDATE_INT);
    $acceptTerms = filter_var($params['acceptTerms'] ?? false, FILTER_VALIDATE_BOOLEAN);

    if ($eventId <= 0) send_response(false, ['message' => 'Invalid Event.'], 400);

    // Check if event requires terms and if they were accepted
    try {
        $stmtEvent = $conn->prepare("SELECT term_text FROM events WHERE id = ?");
        $stmtEvent->execute([$eventId]);
        $event = $stmtEvent->fetch(PDO::FETCH_ASSOC);

        if (!empty($event['term_text']) && !$acceptTerms) {
            send_response(false, ['message' => 'You must accept the liability terms to enroll.'], 400);
        }

        // Insert/Update Enrollment
        $sql = "INSERT INTO event_enrollments (event_id, student_id, status, term_accepted, term_accepted_at, ip_address, created_at)
                VALUES (:eid, :sid, 'Confirmado', :term, NOW(), :ip, NOW())
                ON DUPLICATE KEY UPDATE
                status='Confirmado', term_accepted=:term, term_accepted_at=NOW(), ip_address=:ip";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':eid' => $eventId,
            ':sid' => $_SESSION['user_id'],
            ':term' => $acceptTerms ? 1 : 0,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
        ]);

        AuditLogger::log($conn, $_SESSION['user_id'], 'EVENT_ENROLL', [
            'event_id' => $eventId,
            'accepted_terms' => $acceptTerms
        ]);

        send_response(true, ['message' => 'Enrolled successfully!']);

    } catch (PDOException $e) {
        error_log("Enroll Event Error: " . $e->getMessage());
        send_response(false, ['message' => 'Database error.'], 500);
    }
}

/**
 * Lists participants for an event (Admin only).
 */
function handle_list_event_participants($conn, $params) {
    if (session_status() == PHP_SESSION_NONE) session_start();

    if (($_SESSION['user_role'] ?? '') !== 'admin' && ($_SESSION['user_role'] ?? '') !== 'superadmin') {
        send_response(false, ['message' => 'Unauthorized.'], 403);
    }

    $eventId = filter_var($params['eventId'] ?? 0, FILTER_VALIDATE_INT);

    try {
        $sql = "SELECT ee.*, u.firstName, u.lastName, u.email, u.phone
                FROM event_enrollments ee
                JOIN users u ON ee.student_id = u.id
                WHERE ee.event_id = ?
                ORDER BY u.firstName ASC";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$eventId]);
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Log Access (LGPD - accessing list of people)
        AuditLogger::log($conn, $_SESSION['user_id'], 'VIEW_EVENT_PARTICIPANTS', ['event_id' => $eventId]);

        send_response(true, ['participants' => $participants]);

    } catch (PDOException $e) {
        send_response(false, ['message' => 'Database error.'], 500);
    }
}
