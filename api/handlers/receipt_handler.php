<?php
// api/handlers/receipt_handler.php

require_once __DIR__ . '/../fpdf/fpdf.php';
require_once __DIR__ . '/pdf_helpers.php'; // Para to_iso, valorPorExtenso, add_centered_logo

/**
 * Gera PDF do Recibo
 */
function handle_generate_receipt($conn, $data) {
    error_reporting(0); ini_set('display_errors', 0);
    $paymentId = isset($_GET['paymentId']) ? (int)$_GET['paymentId'] : 0;
    if ($paymentId <= 0) { /* ... erro 400 ... */ }

    $tmp_files = [];
    try {
        try { 
            $stmt = $conn->prepare(" SELECT p.id as paymentId, p.amount, p.referenceDate, p.paymentDate, u.firstName, u.lastName, c.name as courseName, s.name as schoolName, s.cnpj, s.address, s.profilePicture, s.signatureImage, s.city as schoolCity FROM payments p JOIN users u ON p.studentId = u.id JOIN courses c ON p.courseId = c.id JOIN school_profile s ON s.id = 1 WHERE p.id = ? AND p.status = 'Pago' ");
            $stmt->execute([$paymentId]); $details = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { error_log("!!! PDO Error recibo P:$paymentId: " . $e->getMessage()); header("HTTP/1.1 500"); header("Content-Type: text/plain"); echo "Erro DB."; exit; }

        if (!$details) { /* ... erro 404 ... */ }

        $pdf = new FPDF('P', 'mm', 'A4'); $pdf->AddPage(); $pdf->SetFont('Arial', '', 10); $pdf->SetTextColor(50, 50, 50); $pdf->SetMargins(15, 15, 15); $pdf->SetAutoPageBreak(true, 15);

        // ADICIONA LOGO CENTRALIZADO
        $posY_after_logo = add_centered_logo($pdf, $details['profilePicture'] ?? null, $tmp_files);
        $header_text_y = $posY_after_logo > 15 ? $posY_after_logo : 12;
        $pdf->SetY($header_text_y);

        // CABEÇALHO
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 7, to_iso($details['schoolName']), 0, 1, 'C'); 
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 5, to_iso('CNPJ: ' . ($details['cnpj'] ?? 'N/I')), 0, 1, 'C');
        $pdf->Cell(0, 5, to_iso($details['address'] ?? 'N/I'), 0, 1, 'C');
        $pdf->Ln(15); 

        // TÍTULO RECIBO
        $pdf->SetFont('Arial', 'B', 16); $pdf->Cell(0, 10, to_iso('RECIBO DE PAGAMENTO'), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 11); $pdf->Cell(0, 7, to_iso('Recibo Nº ' . str_pad($details['paymentId'], 5, '0', STR_PAD_LEFT)), 0, 1, 'C'); $pdf->Ln(15);

        // CORPO
        $pdf->SetFont('Arial', '', 12); $fullName = trim($details['firstName'] . ' ' . ($details['lastName'] ?? '')); $valor_extenso = valorPorExtenso($details['amount']); $texto_recibo = "Recebemos de " . to_iso($fullName) . ", a quantia de R$ " . number_format($details['amount'], 2, ',', '.') . " (" . to_iso($valor_extenso) . "), referente ao pagamento descrito abaixo."; $pdf->MultiCell(0, 7, $texto_recibo); $pdf->Ln(10);
        $meses = [1=>"Janeiro", 2=>"Fev", 3=>"Março", 4=>"Abril", 5=>"Maio", 6=>"Junho", 7=>"Julho", 8=>"Agosto", 9=>"Setembro", 10=>"Outubro", 11=>"Novembro", 12=>"Dezembro"];
        $pdf->SetFont('Arial', 'B', 11); $pdf->Cell(40, 8, 'Referente a:', 0, 0); $pdf->SetFont('Arial', '', 11); $pdf->Cell(0, 8, to_iso('Mensalidade - ' . $details['courseName']), 0, 1);
        $pdf->SetFont('Arial', 'B', 11); $pdf->Cell(40, 8, to_iso('Mês Referência:'), 0, 0); $pdf->SetFont('Arial', '', 11); $refDate = $details['referenceDate'] ? new DateTime($details['referenceDate']) : null; $pdf->Cell(0, 8, $refDate ? to_iso(ucfirst($meses[(int)$refDate->format('n')]) . '/' . $refDate->format('Y')) : 'N/A', 0, 1);
        $pdf->SetFont('Arial', 'B', 11); $pdf->Cell(40, 8, 'Data Pagamento:', 0, 0); $pdf->SetFont('Arial', '', 11); $pdf->Cell(0, 8, $details['paymentDate'] ? date('d/m/Y', strtotime($details['paymentDate'])) : 'N/A', 0, 1); $pdf->Ln(25);

        // ASSINATURA/CARIMBO
        $line_y = $pdf->GetY(); $pageWidth = $pdf->GetPageWidth(); $margin = 15; $line_width = ($pageWidth - (2 * $margin)) * 0.6; $line_start = ($pageWidth - $line_width) / 2;
        $pdf->Line($line_start, $line_y, $line_start + $line_width, $line_y);
        if (!empty($details['signatureImage'])) { /* ... código assinatura ... */ }
        $pdf->SetXY($line_start, $line_y + 1); $pdf->SetFont('Arial', '', 10); $pdf->MultiCell($line_width, 5, to_iso($details['schoolName']), 0, 'C');

        // RODAPÉ
        $pdf->Ln(10); $pdf->SetFont('Arial', '', 10); $dia = date('d'); $mes = $meses[(int)date('n')]; $ano = date('Y'); $data = $dia.'/'.$mes.'/'.$ano; $pdf->Cell(0, 7, to_iso(($details['schoolCity'] ?: 'Guarulhos') . ", " . $data . "."), 0, 1, 'C');

        foreach ($tmp_files as $file) { if (file_exists($file)) @unlink($file); }
        header('Content-Type: application/pdf');
        $pdf->Output('I', 'recibo_' . $details['paymentId'] . '.pdf'); exit;

    } catch (Exception $e) { /* ... (catch e limpeza como antes) ... */ }
}
?>