<?php
/**
 * views/student/dashboard.php
 * Student Portal Dashboard
 */

$userId = $_SESSION['user_id'];
$conn = get_db_connection();

// 1. Fetch My Enrollments
$stmtEnrollments = $conn->prepare("
    SELECT e.*, c.name as courseName, c.dayOfWeek, c.startTime, c.endTime, u.firstName as teacherName
    FROM enrollments e
    JOIN courses c ON e.courseId = c.id
    LEFT JOIN users u ON c.teacherId = u.id
    WHERE e.studentId = ?
");
$stmtEnrollments->execute([$userId]);
$enrollments = $stmtEnrollments->fetchAll();

// 2. Fetch My Next Payments
$stmtPayments = $conn->prepare("
    SELECT p.*, c.name as courseName
    FROM payments p
    JOIN courses c ON p.courseId = c.id
    WHERE p.studentId = ? AND p.status IN ('Pendente', 'Atrasado')
    ORDER BY p.dueDate ASC LIMIT 5
");
$stmtPayments->execute([$userId]);
$payments = $stmtPayments->fetchAll();

// 3. Overall Attendance summary
$stmtAtt = $conn->prepare("
    SELECT status, COUNT(*) as count
    FROM attendance
    WHERE studentId = ?
    GROUP BY status
");
$stmtAtt->execute([$userId]);
$attendanceStats = $stmtAtt->fetchAll(PDO::FETCH_KEY_PAIR);
$totalClasses = array_sum($attendanceStats);
$presentClasses = $attendanceStats['Presente'] ?? 0;
$attendancePercentage = $totalClasses > 0 ? round(($presentClasses / $totalClasses) * 100) : 100;

?>

<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                Painel do Aluno
            </h2>
        </div>
    </div>

    <!-- Stats Grid -->
    <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-3">
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6 border-l-4 border-indigo-500">
            <dt class="truncate text-sm font-medium text-gray-500">Cursos Matriculados</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900"><?= count($enrollments) ?></dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6 border-l-4 border-green-500">
            <dt class="truncate text-sm font-medium text-gray-500">Frequência Geral</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900"><?= $attendancePercentage ?>%</dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6 border-l-4 border-red-500">
            <dt class="truncate text-sm font-medium text-gray-500">Pagamentos Pendentes</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-red-600"><?= count($payments) ?></dd>
        </div>
    </dl>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        <!-- My Courses -->
        <div class="overflow-hidden bg-white shadow sm:rounded-md">
            <div class="border-b border-gray-200 bg-gray-50 px-4 py-5 sm:px-6">
                <h3 class="text-base font-semibold leading-6 text-gray-900">Meus Cursos</h3>
            </div>
            <ul role="list" class="divide-y divide-gray-200">
                <?php if (empty($enrollments)): ?>
                    <li class="px-4 py-4 sm:px-6 text-gray-500 text-sm">Nenhuma matrícula encontrada.</li>
                <?php else: ?>
                    <?php foreach ($enrollments as $enrollment): ?>
                        <li class="px-4 py-4 sm:px-6 hover:bg-gray-50">
                            <div class="flex items-center justify-between">
                                <p class="truncate text-sm font-medium text-indigo-600"><?= htmlspecialchars($enrollment['courseName']) ?></p>
                                <div class="ml-2 flex flex-shrink-0">
                                    <p class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 <?= $enrollment['status'] === 'Aprovada' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                        <?= htmlspecialchars($enrollment['status']) ?>
                                    </p>
                                </div>
                            </div>
                            <div class="mt-2 sm:flex sm:justify-between">
                                <div class="sm:flex">
                                    <p class="flex items-center text-sm text-gray-500">
                                        👨‍🏫 Prof. <?= htmlspecialchars($enrollment['teacherName']) ?>
                                    </p>
                                </div>
                                <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                                    <p>📅 <?= htmlspecialchars($enrollment['dayOfWeek'] ?? '') ?> - <?= htmlspecialchars($enrollment['startTime'] ?? '') ?></p>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

        <!-- My Payments -->
        <div class="overflow-hidden bg-white shadow sm:rounded-md">
            <div class="border-b border-gray-200 bg-gray-50 px-4 py-5 sm:px-6 flex justify-between items-center">
                <h3 class="text-base font-semibold leading-6 text-gray-900">Próximos Pagamentos</h3>
                <a href="?p=student_payments" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Ver Todos</a>
            </div>
            <ul role="list" class="divide-y divide-gray-200">
                <?php if (empty($payments)): ?>
                    <li class="px-4 py-4 sm:px-6 text-gray-500 text-sm">Tudo em dia! Nenhum pagamento pendente próximo.</li>
                <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                        <li class="px-4 py-4 sm:px-6 hover:bg-gray-50">
                            <div class="flex items-center justify-between">
                                <p class="truncate text-sm font-medium text-gray-900">
                                    Mensalidade - <?= htmlspecialchars($payment['courseName']) ?>
                                </p>
                                <div class="ml-2 flex flex-shrink-0">
                                    <p class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 <?= $payment['status'] === 'Pendente' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= htmlspecialchars($payment['status']) ?>
                                    </p>
                                </div>
                            </div>
                            <div class="mt-2 sm:flex sm:justify-between">
                                <div class="sm:flex">
                                    <p class="flex items-center text-sm font-bold text-gray-700">
                                        R$ <?= number_format($payment['amount'], 2, ',', '.') ?>
                                    </p>
                                </div>
                                <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                                    <p>Vencimento: <?= date('d/m/Y', strtotime($payment['dueDate'])) ?></p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button class="w-full bg-indigo-50 text-indigo-700 hover:bg-indigo-100 px-3 py-2 text-sm font-medium rounded-md text-center border border-indigo-200">
                                    Pagar Agora (PIX)
                                </button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
