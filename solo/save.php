<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

$success_count = 0;
$error_count = 0;
$duplicate_count = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['data'])) {
    $data = $_POST['data'];
    
    foreach ($data as $json_row) {
        $row = json_decode($json_row, true);
        
        // Check for duplicate registration number
        $check_sql = "SELECT id FROM solo_participants WHERE registration_number = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('s', $row['registration_number']);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            $duplicate_count++;
            continue;
        }
        
        $insert_sql = "INSERT INTO solo_participants (
            registration_number, email, participant_name, contact_number, 
            class, institution, event_name, category, bkash_transaction_id, 
            event_date, event_time, mail_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'not sent')";
        
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param(
            'sssssssssss',
            $row['registration_number'],
            $row['email'],
            $row['participant_name'],
            $row['contact_number'],
            $row['class'],
            $row['institution'],
            $row['event_name'],
            $row['category'],
            $row['bkash_transaction_id'],
            $row['event_date'],
            $row['event_time']
        );
        
        if ($insert_stmt->execute()) {
            $success_count++;
        } else {
            $error_count++;
        }
    }
    
    $_SESSION['message'] = "Data saved successfully: $success_count records. Errors: $error_count. Duplicates skipped: $duplicate_count";
    header('Location: manage.php');
    exit;
} else {
    header('Location: upload.php');
    exit;
}
?>