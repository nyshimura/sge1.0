<?php
/**
 * views/teacher/attendance.php
 * Teacher Attendance View (Pure PHP)
 */

$userId = $_SESSION['user_id'];
$conn = get_db_connection();

// Determine Selected Course and Date
$selectedCourseId = $_GET['course_id'] ?? null;
$selectedDate = $_GET['date'] ?? date('Y-m-d');

// Fetch My Courses
$stmtCourses = $conn->prepare("SELECT id, name FROM courses WHERE teacherId = ?");
$stmtCourses->execute([$userId]);
$myCourses = $stmtCourses->fetchAll();

// Handle Form Submission (Save Attendance)
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $courseId = $_POST['course_id'];
    $date = $_POST['attendance_date'];
    $attendanceData = $_POST['attendance'] ?? []; // Array of studentId => status ('Presente'|'Falta')

    try {
        $conn->beginTransaction();
        foreach ($attendanceData as $studentId => $status) {
            // Delete existing record for this student/course/date to handle updates
            $delStmt = $conn->prepare("DELETE FROM attendance WHERE courseId = ? AND studentId = ? AND date = ?");
            $delStmt->execute([$courseId, $studentId, $date]);

            // Insert new record
            if (in_array($status, ['Presente', 'Falta'])) {
                $insStmt = $conn->prepare("INSERT INTO attendance (courseId, studentId, date, status) VALUES (?, ?, ?, ?)");
                $insStmt->execute([$courseId, $studentId, $date, $status]);
            }
        }
        $conn->commit();
        $message = "Frequência salva com sucesso!";
        // Keep selection
        $selectedCourseId = $courseId;
        $selectedDate = $date;
    } catch (Exception $e) {
        $conn->rollBack();
        $message = "Erro ao salvar frequência.";
    }
}

// Ensure Selected variables are updated if POSTed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $selectedCourseId = $_POST['course_id'];
    $selectedDate = $_POST['attendance_date'];
}

// Fetch Students if Course is Selected
$students = [];
$existingAttendance = [];

if ($selectedCourseId) {
    // Verify course belongs to teacher
    $valid = false;
    foreach ($myCourses as $c) { if ($c['id'] == $selectedCourseId) $valid = true; }

    if ($valid) {
        // Get Enrolled Students
        $stmtStudents = $conn->prepare("
            SELECT u.id, u.firstName, u.lastName
            FROM users u
            JOIN enrollments e ON u.id = e.studentId
            WHERE e.courseId = ? AND e.status = 'Aprovada'
            ORDER BY u.firstName ASC
        ");
        $stmtStudents->execute([$selectedCourseId]);
        $students = $stmtStudents->fetchAll();

        // Get Existing Attendance for the selected date
        $stmtAtt = $conn->prepare("SELECT studentId, status FROM attendance WHERE courseId = ? AND date = ?");
        $stmtAtt->execute([$selectedCourseId, $selectedDate]);
        $existingAttendance = $stmtAtt->fetchAll(PDO::FETCH_KEY_PAIR); // array[studentId] => status
    }
}
?>

<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="sm:flex sm:items-center">
    <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold leading-6 text-gray-900">Controle de Frequência</h1>
        <p class="mt-2 text-sm text-gray-700">Selecione uma turma e uma data para registrar a presença dos alunos.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="mt-4 bg-green-50 p-4 border-l-4 border-green-400">
        <p class="text-green-700"><?= htmlspecialchars($message) ?></p>
    </div>
<?php endif; ?>

<!-- Selection Form -->
<div class="mt-6 bg-white p-6 rounded-lg shadow border border-gray-200">
    <form method="GET" action="" class="grid grid-cols-1 gap-y-6 sm:grid-cols-3 sm:gap-x-8">
        <input type="hidden" name="p" value="teacher_attendance">

        <div>
            <label for="course_id" class="block text-sm font-medium leading-6 text-gray-900">Selecione a Turma</label>
            <select id="course_id" name="course_id" class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6" onchange="this.form.submit()">
                <option value="">-- Escolha --</option>
                <?php foreach ($myCourses as $course): ?>
                    <option value="<?= $course['id'] ?>" <?= $selectedCourseId == $course['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($course['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="date" class="block text-sm font-medium leading-6 text-gray-900">Data da Aula</label>
            <input type="date" id="date" name="date" value="<?= htmlspecialchars($selectedDate) ?>" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" onchange="this.form.submit()">
        </div>
    </form>
</div>

<!-- Attendance Form -->
<?php if ($selectedCourseId): ?>
    <?php if (empty($students)): ?>
        <div class="mt-8 text-center text-gray-500 py-12 bg-white rounded shadow">Nenhum aluno matriculado nesta turma.</div>
    <?php else: ?>
        <form method="POST" action="?p=teacher_attendance&course_id=<?= htmlspecialchars($selectedCourseId) ?>&date=<?= htmlspecialchars($selectedDate) ?>">
            <input type="hidden" name="course_id" value="<?= htmlspecialchars($selectedCourseId) ?>">
            <input type="hidden" name="attendance_date" value="<?= htmlspecialchars($selectedDate) ?>">

            <div class="mt-8 flow-root">
                <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-300 bg-white">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Aluno</th>
                                        <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">Presença</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($students as $student): ?>
                                        <?php
                                            $sid = $student['id'];
                                            $currentStatus = $existingAttendance[$sid] ?? null;
                                        ?>
                                        <tr>
                                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                                <?= htmlspecialchars($student['firstName'] . ' ' . $student['lastName']) ?>
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 text-center">
                                                <div class="flex items-center justify-center space-x-6">
                                                    <label class="inline-flex items-center">
                                                        <input type="radio" name="attendance[<?= $sid ?>]" value="Presente" class="form-radio text-indigo-600 h-5 w-5" <?= $currentStatus === 'Presente' ? 'checked' : '' ?> required>
                                                        <span class="ml-2 text-green-600 font-semibold">Presente</span>
                                                    </label>
                                                    <label class="inline-flex items-center">
                                                        <input type="radio" name="attendance[<?= $sid ?>]" value="Falta" class="form-radio text-red-600 h-5 w-5" <?= $currentStatus === 'Falta' ? 'checked' : '' ?> required>
                                                        <span class="ml-2 text-red-600 font-semibold">Falta</span>
                                                    </label>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" name="save_attendance" value="1" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-md shadow focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    💾 Salvar Chamada
                </button>
            </div>
        </form>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/../layout/footer.php'; ?>
