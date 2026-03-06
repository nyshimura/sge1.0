<?php
/**
 * views/admin/dashboard.php
 * Admin Portal Dashboard
 */

$conn = get_db_connection();

// Quick Stats
$totalStudents = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$totalTeachers = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn();
$activeCourses = $conn->query("SELECT COUNT(*) FROM courses WHERE status = 'Aberto'")->fetchColumn();

// Recent Enrollments
$stmtEnrolls = $conn->query("
    SELECT e.studentId, e.courseId, u.firstName, u.lastName, c.name as courseName, e.enrollmentDate, e.status
    FROM enrollments e
    JOIN users u ON e.studentId = u.id
    JOIN courses c ON e.courseId = c.id
    ORDER BY e.enrollmentDate DESC LIMIT 5
");
$recentEnrollments = $stmtEnrolls->fetchAll();

// Pending Approvals
$pendingApprovals = $conn->query("SELECT COUNT(*) FROM enrollments WHERE status = 'Pendente'")->fetchColumn();

?>

<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="space-y-6">
    <div class="md:flex md:items-center md:justify-between">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                Painel Administrativo
            </h2>
        </div>
        <div class="mt-4 flex md:ml-4 md:mt-0 gap-2">
            <a href="?p=legacy_app#students" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Novo Aluno</a>
            <a href="?p=legacy_app#courses" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Novo Curso</a>
        </div>
    </div>

    <!-- Stats Grid -->
    <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-4">
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6 border-l-4 border-indigo-500">
            <dt class="truncate text-sm font-medium text-gray-500">Alunos Ativos</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900"><?= $totalStudents ?></dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6 border-l-4 border-blue-500">
            <dt class="truncate text-sm font-medium text-gray-500">Cursos Abertos</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900"><?= $activeCourses ?></dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6 border-l-4 border-purple-500">
            <dt class="truncate text-sm font-medium text-gray-500">Professores</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900"><?= $totalTeachers ?></dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6 border-l-4 border-yellow-500">
            <dt class="truncate text-sm font-medium text-gray-500">Matrículas Pendentes</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight <?= $pendingApprovals > 0 ? 'text-yellow-600' : 'text-gray-900' ?>"><?= $pendingApprovals ?></dd>
        </div>
    </dl>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Recent Enrollments Table -->
        <div class="overflow-hidden bg-white shadow sm:rounded-md">
            <div class="border-b border-gray-200 bg-gray-50 px-4 py-5 sm:px-6 flex justify-between items-center">
                <h3 class="text-base font-semibold leading-6 text-gray-900">Matrículas Recentes</h3>
                <a href="?p=legacy_app#enrollment" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Ver Todas</a>
            </div>
            <ul role="list" class="divide-y divide-gray-200">
                <?php if (empty($recentEnrollments)): ?>
                    <li class="px-4 py-4 sm:px-6 text-gray-500 text-sm">Nenhuma matrícula recente.</li>
                <?php else: ?>
                    <?php foreach ($recentEnrollments as $enr): ?>
                        <li class="px-4 py-4 sm:px-6 hover:bg-gray-50">
                            <div class="flex items-center justify-between">
                                <p class="truncate text-sm font-medium text-indigo-600"><?= htmlspecialchars($enr['firstName'] . ' ' . $enr['lastName']) ?></p>
                                <div class="ml-2 flex flex-shrink-0">
                                    <p class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 <?= $enr['status'] === 'Aprovada' ? 'bg-green-100 text-green-800' : ($enr['status'] === 'Pendente' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                                        <?= htmlspecialchars($enr['status']) ?>
                                    </p>
                                </div>
                            </div>
                            <div class="mt-2 sm:flex sm:justify-between">
                                <div class="sm:flex">
                                    <p class="flex items-center text-sm text-gray-500">
                                        📚 <?= htmlspecialchars($enr['courseName']) ?>
                                    </p>
                                </div>
                                <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                                    <p>📅 <?= date('d/m/Y H:i', strtotime($enr['enrollmentDate'])) ?></p>
                                </div>
                            </div>
                            <?php if ($enr['status'] === 'Pendente'): ?>
                                <div class="mt-3 flex gap-2">
                                    <button class="bg-green-50 text-green-700 hover:bg-green-100 px-3 py-1 text-xs font-medium rounded-md border border-green-200">Aprovar</button>
                                    <button class="bg-red-50 text-red-700 hover:bg-red-100 px-3 py-1 text-xs font-medium rounded-md border border-red-200">Recusar</button>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Quick Actions -->
        <div class="overflow-hidden bg-white shadow sm:rounded-md p-6">
            <h3 class="text-base font-semibold leading-6 text-gray-900 mb-4">Ações Rápidas</h3>
            <div class="grid grid-cols-2 gap-4">
                <a href="?p=legacy_app#financial" class="block w-full rounded-lg border-2 border-dashed border-gray-300 p-4 text-center hover:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 group transition">
                    <span class="block text-2xl mb-1 group-hover:scale-110 transition-transform">📝</span>
                    <span class="mt-2 block text-sm font-semibold text-gray-900 group-hover:text-indigo-600">Lançar Mensalidades</span>
                </a>
                <a href="?p=legacy_app#certificates" class="block w-full rounded-lg border-2 border-dashed border-gray-300 p-4 text-center hover:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 group transition">
                    <span class="block text-2xl mb-1 group-hover:scale-110 transition-transform">🎓</span>
                    <span class="mt-2 block text-sm font-semibold text-gray-900 group-hover:text-indigo-600">Gerar Certificados</span>
                </a>
                <a href="?p=legacy_app#events" class="block w-full rounded-lg border-2 border-dashed border-gray-300 p-4 text-center hover:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 group transition">
                    <span class="block text-2xl mb-1 group-hover:scale-110 transition-transform">🎭</span>
                    <span class="mt-2 block text-sm font-semibold text-gray-900 group-hover:text-indigo-600">Criar Evento/Turnê</span>
                </a>
                <a href="?p=legacy_app#settings" class="block w-full rounded-lg border-2 border-dashed border-gray-300 p-4 text-center hover:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 group transition">
                    <span class="block text-2xl mb-1 group-hover:scale-110 transition-transform">⚙️</span>
                    <span class="mt-2 block text-sm font-semibold text-gray-900 group-hover:text-indigo-600">Configurações Gerais</span>
                </a>
            </div>
        </div>

    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
