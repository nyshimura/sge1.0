<?php
function handle_get_financial_dashboard_data($conn, $data) {
    $month = $data['month']; // Ex: "2025-10"
    
    // 1. CALCULA A RECEITA ARRECADADA (O QUE ENTROU NO CAIXA NO MÊS)
    $stmtCollected = $conn->prepare("SELECT SUM(amount) as total FROM payments WHERE paymentDate LIKE ?");
    $stmtCollected->execute(["$month%"]);
    $collectedRevenue = $stmtCollected->fetchColumn() ?: 0;

    // 2. CALCULA O PREVISTO E A INADIMPLÊNCIA (REFERENTE ÀS MENSALIDADES DO MÊS)
    $stmtReference = $conn->prepare("SELECT SUM(CASE WHEN status != 'Cancelado' THEN amount ELSE 0 END) as totalExpected, 
                                            SUM(CASE WHEN status = 'Pendente' OR status = 'Atrasado' THEN amount ELSE 0 END) as totalOutstanding
                                     FROM payments WHERE referenceDate LIKE ?");
    $stmtReference->execute(["$month%"]);
    $referenceData = $stmtReference->fetch(PDO::FETCH_ASSOC);
    
    $expectedRevenue = $referenceData['totalExpected'] ?: 0;
    $outstandingRevenue = $referenceData['totalOutstanding'] ?: 0;


    // 3. CALCULA RECEITA POR CURSO (BASEADO NO QUE ENTROU NO CAIXA NO MÊS)
    $stmtCourse = $conn->prepare("SELECT c.name, SUM(p.amount) as total FROM payments p JOIN courses c ON p.courseId = c.id WHERE p.paymentDate LIKE ? GROUP BY c.name");
    $stmtCourse->execute(["$month%"]);
    $revenueByCourseData = $stmtCourse->fetchAll(PDO::FETCH_KEY_PAIR);

    // 4. CALCULA EVOLUÇÃO DA RECEITA (BASEADO NO QUE ENTROU NO CAIXA EM CADA MÊS)
    $evolutionData = [];
    $date = new DateTime("$month-01");
    $date->modify('-5 months');
    for ($i = 0; $i < 6; $i++) {
        $m = $date->format('Y-m');
        
        $stmtEvoCollected = $conn->prepare("SELECT SUM(amount) FROM payments WHERE paymentDate LIKE ?");
        $stmtEvoCollected->execute(["$m%"]);
        $collected = $stmtEvoCollected->fetchColumn() ?: 0;
        
        $stmtEvoExpected = $conn->prepare("SELECT SUM(amount) FROM payments WHERE referenceDate LIKE ? AND status != 'Cancelado'");
        $stmtEvoExpected->execute(["$m%"]);
        $expected = $stmtEvoExpected->fetchColumn() ?: 0;

        $evolutionData[] = [
            'month' => $date->format('M'), 
            'collected' => $collected, 
            'expected' => $expected
        ];
        $date->modify('+1 month');
    }

    send_response(true, [
        'expectedRevenue' => $expectedRevenue, 
        'collectedRevenue' => $collectedRevenue, 
        'outstandingRevenue' => $outstandingRevenue, 
        'revenueByCourseData' => $revenueByCourseData, 
        'evolutionData' => $evolutionData
    ]);
}

function handle_get_student_payments($conn, $data) {
    $studentId = $data['studentId'];
    // ALTERAÇÃO AQUI: Mudado de DESC para ASC para ordem crescente (antigas primeiro)
    $stmt = $conn->prepare("SELECT * FROM payments WHERE studentId = ? ORDER BY referenceDate ASC");
    $stmt->execute([$studentId]);
    $payments = $stmt->fetchAll();
    send_response(true, ['payments' => $payments]);
}

function handle_update_payment_status($conn, $data) {
    $paymentId = $data['paymentId'];
    $status = $data['status'];
    $paymentDate = ($status === 'Pago') ? date('Y-m-d') : null;
    $stmt = $conn->prepare("UPDATE payments SET status = ?, paymentDate = ? WHERE id = ?");
    $stmt->execute([$status, $paymentDate, $paymentId]);
    send_response(true, ['message' => 'Status do pagamento atualizado.']);
}

function handle_get_defaulters_report($conn, $data) {
    $month = $data['month'] ?? null;
    $courseId = $data['courseId'] ?? 'all';

    $sql = "SELECT 
                u.id as studentId,
                u.firstName,
                u.lastName,
                u.email,
                c.name as courseName,
                p.id as paymentId,
                p.amount,
                p.dueDate,
                p.status
            FROM payments p
            JOIN users u ON p.studentId = u.id
            JOIN courses c ON p.courseId = c.id
            WHERE p.status IN ('Pendente', 'Atrasado')";

    $params = [];
    if (!empty($month)) {
        $sql .= " AND p.dueDate LIKE ?";
        $params[] = $month . '%';
    }
    if ($courseId !== 'all' && is_numeric($courseId)) {
        $sql .= " AND p.courseId = ?";
        $params[] = (int)$courseId;
    }
    
    $sql .= " ORDER BY p.dueDate ASC, u.firstName ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $defaulters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Busca todos os cursos para preencher o filtro
    $courses = $conn->query("SELECT id, name FROM courses ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    send_response(true, ['defaulters' => $defaulters, 'courses' => $courses]);
}

function handle_bulk_update_payment_status($conn, $data) {
    $paymentIds = $data['paymentIds'] ?? [];

    if (empty($paymentIds) || !is_array($paymentIds)) {
        send_response(false, 'Nenhum pagamento selecionado.', 400);
    }

    $placeholders = implode(',', array_fill(0, count($paymentIds), '?'));
    $paymentDate = date('Y-m-d');

    $sql = "UPDATE payments SET status = 'Pago', paymentDate = ? WHERE id IN ($placeholders)";
    
    $params = array_merge([$paymentDate], $paymentIds);

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    send_response(true, ['message' => $stmt->rowCount() . ' pagamento(s) atualizado(s) com sucesso.']);
}
?>