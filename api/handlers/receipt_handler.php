<?php
// api/handlers/receipt_handler.php

require_once __DIR__ . '/../fpdf/fpdf.php';
require_once __DIR__ . '/pdf_helpers.php'; 

/**
 * Gera PDF do Recibo
 */
function handle_generate_receipt($conn, $data) {
    // 1. Limpeza de Buffer e Tratamento de Erros
    while (ob_get_level()) ob_end_clean(); // Limpa qualquer saída anterior (espaços em branco, warnings)
    error_reporting(E_ALL); 
    ini_set('display_errors', 0); // Não mostra erros na tela (quebra o PDF), loga no servidor

    $paymentId = isset($_GET['paymentId']) ? (int)$_GET['paymentId'] : 0;
    
    // Suporte para quando a chamada vem via JSON/POST também
    if ($paymentId === 0 && isset($data['paymentId'])) {
        $paymentId = (int)$data['paymentId'];
    }

    if ($paymentId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID do pagamento inválido.']);
        exit;
    }

    $tmp_files = [];
    
    try {
        // --- CONSULTA 1: DADOS DO PAGAMENTO ---
        try { 
            $sqlPayment = "
                SELECT 
                    p.id as paymentId, 
                    p.amount, 
                    p.referenceDate, 
                    p.paymentDate, 
                    u.firstName, 
                    u.lastName, 
                    c.name as courseName
                FROM payments p 
                JOIN users u ON p.studentId = u.id 
                JOIN courses c ON p.courseId = c.id 
                WHERE p.id = ? 
            ";
            
            $stmt = $conn->prepare($sqlPayment);
            $stmt->execute([$paymentId]); 
            $paymentDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) { 
            error_log("Erro SQL Pagamento: " . $e->getMessage());
            throw new Exception("Erro ao buscar dados do pagamento.");
        }

        if (!$paymentDetails) { 
            throw new Exception("Pagamento não encontrado ou ID inválido.");
        }

        // --- CONSULTA 2: DADOS DA ESCOLA (Separada para evitar erros de JOIN/Coluna) ---
        try {
            // Seleciona TUDO da escola, independente das colunas, e pega o primeiro registro (LIMIT 1)
            $stmtSchool = $conn->query("SELECT * FROM school_profile LIMIT 1");
            $schoolDetails = $stmtSchool->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Se falhar a escola, usa dados genéricos para não travar o recibo
            $schoolDetails = [];
            error_log("Erro SQL Escola: " . $e->getMessage());
        }

        // --- COMBINA OS DADOS ---
        $details = array_merge($paymentDetails, $schoolDetails ?: []);
        
        // Garante valores padrão para campos da escola se faltarem
        $details['schoolName'] = $schoolDetails['name'] ?? 'Nome da Escola';
        $details['cnpj'] = $schoolDetails['cnpj'] ?? '';
        $details['address'] = $schoolDetails['address'] ?? '';
        $details['schoolCity'] = $schoolDetails['city'] ?? 'Guarulhos'; // Fallback se a coluna city não existir
        $details['profilePicture'] = $schoolDetails['profilePicture'] ?? null;
        $details['signatureImage'] = $schoolDetails['signatureImage'] ?? null;


        // --- GERAÇÃO DO PDF ---
        $pdf = new FPDF('P', 'mm', 'A4'); 
        $pdf->AddPage(); 
        $pdf->SetFont('Arial', '', 10); 
        $pdf->SetTextColor(50, 50, 50); 
        $pdf->SetMargins(15, 15, 15); 
        $pdf->SetAutoPageBreak(true, 15);

        // LOGO
        $posY_after_logo = 15;
        if (!empty($details['profilePicture'])) {
            // Função auxiliar assume que lida com o path corretamente
            $posY_after_logo = add_centered_logo($pdf, $details['profilePicture'], $tmp_files);
        }
        
        $header_text_y = $posY_after_logo > 15 ? $posY_after_logo : 15;
        $pdf->SetY($header_text_y);

        // CABEÇALHO DA ESCOLA
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 7, to_iso($details['schoolName']), 0, 1, 'C'); 
        $pdf->SetFont('Arial', '', 9);
        if (!empty($details['cnpj'])) {
            $pdf->Cell(0, 5, to_iso('CNPJ: ' . $details['cnpj']), 0, 1, 'C');
        }
        $pdf->Cell(0, 5, to_iso($details['address']), 0, 1, 'C');
        $pdf->Ln(15); 

        // TÍTULO RECIBO
        $pdf->SetFont('Arial', 'B', 16); 
        $pdf->Cell(0, 10, to_iso('RECIBO DE PAGAMENTO'), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 11); 
        $pdf->Cell(0, 7, to_iso('Recibo Nº ' . str_pad($paymentId, 5, '0', STR_PAD_LEFT)), 0, 1, 'C'); 
        $pdf->Ln(15);

        // CORPO DO TEXTO
        $pdf->SetFont('Arial', '', 12); 
        $fullName = trim($details['firstName'] . ' ' . ($details['lastName'] ?? '')); 
        
        // Verifica se valorPorExtenso existe (função auxiliar)
        $valor_extenso = function_exists('valorPorExtenso') ? valorPorExtenso($details['amount']) : '';
        $valor_formatado = number_format($details['amount'], 2, ',', '.');
        
        $texto_recibo = "Recebemos de " . to_iso($fullName) . ", a quantia de R$ " . $valor_formatado . 
                        ($valor_extenso ? " (" . to_iso($valor_extenso) . ")" : "") . 
                        ", referente ao pagamento descrito abaixo."; 
        
        $pdf->MultiCell(0, 7, $texto_recibo); 
        $pdf->Ln(10);

        // DETALHES TÉCNICOS
        $meses = [1=>"Janeiro", 2=>"Fev", 3=>"Março", 4=>"Abril", 5=>"Maio", 6=>"Junho", 7=>"Julho", 8=>"Agosto", 9=>"Setembro", 10=>"Outubro", 11=>"Novembro", 12=>"Dezembro"];
        
        // Linha 1: Referência
        $pdf->SetFont('Arial', 'B', 11); $pdf->Cell(40, 8, 'Referente a:', 0, 0); 
        $pdf->SetFont('Arial', '', 11); $pdf->Cell(0, 8, to_iso('Mensalidade - ' . $details['courseName']), 0, 1);
        
        // Linha 2: Mês Referência
        $pdf->SetFont('Arial', 'B', 11); $pdf->Cell(40, 8, to_iso('Mês Referência:'), 0, 0); 
        $pdf->SetFont('Arial', '', 11); 
        $refDate = $details['referenceDate'] ? new DateTime($details['referenceDate']) : null; 
        $refText = $refDate ? ucfirst($meses[(int)$refDate->format('n')]) . '/' . $refDate->format('Y') : 'N/A';
        $pdf->Cell(0, 8, to_iso($refText), 0, 1);
        
        // Linha 3: Data Pagamento
        $pdf->SetFont('Arial', 'B', 11); $pdf->Cell(40, 8, 'Data Pagamento:', 0, 0); 
        $pdf->SetFont('Arial', '', 11); 
        $payDateFormatted = $details['paymentDate'] ? date('d/m/Y', strtotime($details['paymentDate'])) : date('d/m/Y');
        $pdf->Cell(0, 8, $payDateFormatted, 0, 1); 
        
        $pdf->Ln(25);

        // ASSINATURA/CARIMBO
        $line_y = $pdf->GetY(); 
        $pageWidth = $pdf->GetPageWidth(); 
        $margin = 15; 
        $line_width = ($pageWidth - (2 * $margin)) * 0.6; 
        $line_start = ($pageWidth - $line_width) / 2;
        
        $pdf->Line($line_start, $line_y, $line_start + $line_width, $line_y);
        
        // Verifica assinatura
        if (!empty($details['signatureImage'])) { 
             // Lógica de inserir imagem da assinatura (simplificada)
             $sigPath = __DIR__ . '/../../uploads/' . basename($details['signatureImage']);
             if (file_exists($sigPath)) {
                 $pdf->Image($sigPath, $line_start + ($line_width/2) - 15, $line_y - 15, 30);
             }
        }
        
        $pdf->SetXY($line_start, $line_y + 2); 
        $pdf->SetFont('Arial', '', 10); 
        $pdf->MultiCell($line_width, 5, to_iso($details['schoolName']), 0, 'C');

        // RODAPÉ (Data e Cidade)
        $pdf->Ln(10); 
        $pdf->SetFont('Arial', '', 10); 
        $dia = date('d'); 
        $mes = $meses[(int)date('n')]; 
        $ano = date('Y'); 
        $data = $dia.'/'.$mes.'/'.$ano; 
        
        $cidade = !empty($details['schoolCity']) ? $details['schoolCity'] : 'Guarulhos';
        $pdf->Cell(0, 7, to_iso($cidade . ", " . $data . "."), 0, 1, 'C');

        // Limpa arquivos temporários de imagem se houver
        foreach ($tmp_files as $file) { 
            if (file_exists($file)) @unlink($file); 
        }
        
        // Output do PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="recibo_' . $paymentId . '.pdf"');
        $pdf->Output('I', 'recibo_' . $paymentId . '.pdf'); 
        exit;

    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}
?>