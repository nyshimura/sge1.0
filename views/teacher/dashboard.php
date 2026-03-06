<?php
/**
 * views/teacher/dashboard.php
 * Teacher Portal Dashboard
 */

$userId = $_SESSION['user_id'];
$conn = get_db_connection();

// My Courses
$stmtCourses = $conn->prepare("SELECT * FROM courses WHERE teacherId = ?");
$stmtCourses->execute([$userId]);
$myCourses = $stmtCourses->fetchAll();

// Get enrolled students count per course
foreach ($myCourses as &$course) {
    $stmtCount = $conn->prepare("SELECT COUNT(*) FROM enrollments WHERE courseId = ? AND status = 'Aprovada'");
    $stmtCount->execute([$course['id']]);
    $course['studentCount'] = $stmtCount->fetchColumn();
}
unset($course); // break reference

?>

<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="space-y-6">
    <div class="md:flex md:items-center md:justify-between">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                Painel do Professor
            </h2>
        </div>
    </div>

    <!-- Stats Grid -->
    <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6 border-l-4 border-indigo-500">
            <dt class="truncate text-sm font-medium text-gray-500">Minhas Turmas</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900"><?= count($myCourses) ?></dd>
        </div>
    </dl>

    <div class="grid grid-cols-1 gap-6">
        <!-- My Courses Table -->
        <div class="overflow-hidden bg-white shadow sm:rounded-md">
            <div class="border-b border-gray-200 bg-gray-50 px-4 py-5 sm:px-6">
                <h3 class="text-base font-semibold leading-6 text-gray-900">Minhas Turmas</h3>
            </div>
            <ul role="list" class="divide-y divide-gray-200 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                <?php if (empty($myCourses)): ?>
                    <li class="px-4 py-4 sm:px-6 text-gray-500 text-sm col-span-full">Você não possui turmas atribuídas.</li>
                <?php else: ?>
                    <?php foreach ($myCourses as $course): ?>
                        <li class="col-span-1 flex flex-col divide-y divide-gray-200 rounded-lg bg-white text-center shadow">
                            <div class="flex flex-1 flex-col p-8">
                                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-indigo-100 text-2xl">
                                    📚
                                </span>
                                <h3 class="mt-6 text-sm font-medium text-gray-900"><?= htmlspecialchars($course['name']) ?></h3>
                                <dl class="mt-1 flex flex-grow flex-col justify-between">
                                    <dt class="sr-only">Title</dt>
                                    <dd class="text-sm text-gray-500"><?= htmlspecialchars($course['dayOfWeek'] ?? '') ?> - <?= htmlspecialchars($course['startTime'] ?? '') ?></dd>
                                    <dt class="sr-only">Role</dt>
                                    <dd class="mt-3">
                                        <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20"><?= $course['studentCount'] ?> Alunos</span>
                                    </dd>
                                </dl>
                            </div>
                            <div>
                                <div class="-mt-px flex divide-x divide-gray-200">
                                    <div class="flex w-0 flex-1">
                                        <a href="?p=teacher_attendance&course_id=<?= $course['id'] ?>&date=<?= date('Y-m-d') ?>" class="relative -mr-px inline-flex w-0 flex-1 items-center justify-center gap-x-3 rounded-bl-lg border border-transparent py-4 text-sm font-semibold text-gray-900 hover:bg-gray-50">
                                            <span class="text-xl">📝</span>
                                            Frequência
                                        </a>
                                    </div>
                                    <div class="-ml-px flex w-0 flex-1">
                                        <a href="?p=legacy_app#students" class="relative inline-flex w-0 flex-1 items-center justify-center gap-x-3 rounded-br-lg border border-transparent py-4 text-sm font-semibold text-gray-900 hover:bg-gray-50">
                                            <span class="text-xl">👥</span>
                                            Alunos
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
