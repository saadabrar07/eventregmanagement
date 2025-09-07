<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

session_start();

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_ids'])) {
    $selected_ids = $_POST['selected_ids'];
    $success_count = 0;
    $error_count = 0;
    $already_sent_count = 0;
    
    // Prepare statement to get participant data
    $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
    $select_sql = "SELECT * FROM solo_participants WHERE id IN ($placeholders)";
    $select_stmt = $conn->prepare($select_sql);
    
    // Bind parameters
    $types = str_repeat('i', count($selected_ids));
    $select_stmt->bind_param($types, ...$selected_ids);
    $select_stmt->execute();
    $result = $select_stmt->get_result();
    
    // Prepare update statement
    $update_sql = "UPDATE solo_participants SET mail_status = 'sent' WHERE id = ? AND mail_status = 'not sent'";
    $update_stmt = $conn->prepare($update_sql);
    
    while ($participant = $result->fetch_assoc()) {
        // Skip if mail was already sent (unless we want to resend)
        if ($participant['mail_status'] === 'sent') {
            $already_sent_count++;
            continue;
        }
        
        // Prepare email content
        $subject = EMAIL_SUBJECT_PREFIX . " " . $participant['event_name'] . " - 2nd DRMC National Math Summit";
        
        $body = "
            <p>Dear " . htmlspecialchars($participant['participant_name']) . ",</p>
            <p>Thank you for registering for the " . htmlspecialchars($participant['event_name']) . " at the 2nd DRMC National Math Summit, taking place from October 09-11, 2025. We are thrilled to have you join us for this landmark event.</p>
            
            <p><strong>Event Date:</strong> 09 October 2025 - 11 October 2025</p>
            <p><strong>Event Time:</strong> Will be mentioned in the Event Schedule. Event Schedule will be posted in the event page.</p>
            
            <p>Further instructions, schedules, and important updates will be shared on our official Facebook event page. Please make sure to follow it closely and check your email regularly for any updates.</p>
            
            <p><strong>Registration Details:</strong></p>
            <ul>
                <li>Registration Number: " . htmlspecialchars($participant['registration_number']) . "</li>
                <li>Participant Name: " . htmlspecialchars($participant['participant_name']) . "</li>
                <li>Institution Name: " . htmlspecialchars($participant['institution']) . "</li>
                <li>Bkash Transaction ID: " . htmlspecialchars($participant['bkash_transaction_id']) . "</li>
                <li>Category: " . htmlspecialchars($participant['category']) . "</li>
            </ul>
            
            <p>If you have any questions or need assistance, feel free to reach out to us through:</p>
            <p>Our Facebook page: <a href='https://www.facebook.com/drmcmathclub'>https://www.facebook.com/drmcmathclub</a></p>
            <p>Our website: <a href='http://www.drmcmathclub.com'>http://www.drmcmathclub.com</a></p>
            
            <p>We look forward to welcoming you to the summit and wish you the best of luck in the " . htmlspecialchars($participant['event_name']) . "!</p>
            
            <p>Best regards,<br>DRMC Math Club</p>
        ";
        
        // Send email
        $email_sent = sendEmail($participant['email'], $subject, $body);
        
        if ($email_sent) {
            // Update status in database
            $update_stmt->bind_param('i', $participant['id']);
            $update_stmt->execute();
            $success_count++;
        } else {
            $error_count++;
        }
    }
    
    $_SESSION['message'] = "Emails sent: $success_count. Errors: $error_count. Already sent: $already_sent_count";
    header('Location: manage.php');
    exit;
} else {
    header('Location: manage.php');
    exit;
}
?>