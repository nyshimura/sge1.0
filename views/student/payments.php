<?php
/**
 * views/student/payments.php
 * Student Payments View (Pure PHP)
 */

$userId = $_SESSION['user_id'];
$conn = get_db_connection();

// Fetch all payments for this student
$stmt = $conn->prepare("
    SELECT p.*, c.name as courseName
    FROM payments p
    JOIN courses c ON p.courseId = c.id
    WHERE p.studentId = ?
    ORDER BY p.dueDate ASC
");
$stmt->execute([$userId]);
$allPayments = $stmt->fetchAll();

// Calculate totals
$totalPending = 0;
$totalPaid = 0;
$totalOverdue = 0;

foreach ($allPayments as $payment) {
    if ($payment['status'] === 'Pendente') $totalPending += $payment['amount'];
    if ($payment['status'] === 'Pago') $totalPaid += $payment['amount'];
    if ($payment['status'] === 'Atrasado') $totalOverdue += $payment['amount'];
}
?>

<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="sm:flex sm:items-center">
    <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold leading-6 text-gray-900">Meus Pagamentos</h1>
        <p class="mt-2 text-sm text-gray-700">Acompanhe suas mensalidades, pague com PIX e veja seu histórico financeiro.</p>
    </div>
</div>

<!-- Summary Cards -->
<dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-3 mb-8">
    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6 border-l-4 border-yellow-400">
        <dt class="truncate text-sm font-medium text-gray-500">Total Pendente</dt>
        <dd class="mt-1 text-2xl font-semibold tracking-tight text-gray-900">R$ <?= number_format($totalPending, 2, ',', '.') ?></dd>
    </div>
    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6 border-l-4 border-red-500">
        <dt class="truncate text-sm font-medium text-gray-500">Total Atrasado</dt>
        <dd class="mt-1 text-2xl font-semibold tracking-tight text-red-600">R$ <?= number_format($totalOverdue, 2, ',', '.') ?></dd>
    </div>
    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6 border-l-4 border-green-500">
        <dt class="truncate text-sm font-medium text-gray-500">Total Pago</dt>
        <dd class="mt-1 text-2xl font-semibold tracking-tight text-gray-900">R$ <?= number_format($totalPaid, 2, ',', '.') ?></dd>
    </div>
</dl>

<div class="mt-8 flow-root">
    <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-300 bg-white">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Curso</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Valor</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Vencimento</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6 text-right font-semibold text-gray-900">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($allPayments)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Nenhum histórico de pagamento encontrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($allPayments as $payment): ?>
                                <tr>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                        <?= htmlspecialchars($payment['courseName']) ?>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        R$ <?= number_format($payment['amount'], 2, ',', '.') ?>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <?= date('d/m/Y', strtotime($payment['dueDate'])) ?>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        <?php
                                            $badgeClass = '';
                                            if ($payment['status'] === 'Pago') $badgeClass = 'bg-green-100 text-green-800';
                                            elseif ($payment['status'] === 'Pendente') $badgeClass = 'bg-yellow-100 text-yellow-800';
                                            elseif ($payment['status'] === 'Atrasado') $badgeClass = 'bg-red-100 text-red-800';
                                            else $badgeClass = 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium <?= $badgeClass ?>">
                                            <?= htmlspecialchars($payment['status']) ?>
                                        </span>
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <?php if ($payment['status'] !== 'Pago' && $payment['status'] !== 'Cancelado'): ?>
                                            <button class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-3 py-1 rounded border border-indigo-200" onclick="alert('Funcionalidade de PIX (Mercado Pago) sendo migrada para PHP Puro. Use a versão anterior temporariamente.')">
                                                Pagar (PIX)
                                            </button>
                                        <?php else: ?>
                                            <span class="text-gray-400">---</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
