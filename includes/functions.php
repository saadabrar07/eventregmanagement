<?php
require_once 'db.php';
require_once __DIR__ . '/../tcpdf/tcpdf.php';

function sanitizeInput($data) {
    $db = new Database();
    $conn = $db->getConnection();
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

function sendEmail($to, $subject, $body) {
    $headers = "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM . ">\r\n";
    $headers .= "Reply-To: " . EMAIL_FROM . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $body, $headers);
}

function generatePDF($html, $filename = 'output.pdf') {
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('DRMC Math Club');
    $pdf->SetTitle('Registration Details');
    $pdf->SetSubject('Registration Details');
    $pdf->SetKeywords('DRMC, Math, Summit, Registration');
    
    $pdf->AddPage();
    $pdf->writeHTML($html, true, false, true, false, '');
    
    $pdf->Output($filename, 'D');
}

function exportToCSV($data, $filename = 'export.csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Write headers
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
    }
    
    // Write data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}
?>