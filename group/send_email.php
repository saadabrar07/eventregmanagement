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
    $select_sql = "SELECT * FROM group_participants WHERE id IN ($placeholders)";
    $select_stmt = $conn->prepare($select_sql);
    
    // Bind parameters
    $types = str_repeat('i', count($selected_ids));
    $select_stmt->bind_param($types, ...$selected_ids);
    $select_stmt->execute();
    $result = $select_stmt->get_result();
    
    // Prepare update statement
    $update_sql = "UPDATE group_participants SET mail_status = 'sent' WHERE id = ? AND mail_status = 'not sent'";
    $update_stmt = $conn->prepare($update_sql);
    
    while ($participant = $result->fetch_assoc()) {
        // Skip if mail was already sent
        if ($participant['mail_status'] === 'sent') {
            $already_sent_count++;
            continue;
        }
        
        // Prepare email content
        $subject = EMAIL_SUBJECT_PREFIX . " " . $participant['event_name'] . " - 2nd DRMC National Math Summit";
        
        // Build team members list
        $team_members = "";
        for ($i = 1; $i <= 5; $i++) {
            $member_field = "team_member$i";
            $institution_field = "institution$i";
            $contact_field = "contact$i";
            
            if (!empty($participant[$member_field])) {
                $team_members .= "<li>Team Member $i - " . htmlspecialchars($participant[$member_field]) . 
                                " - " . htmlspecialchars($participant[$institution_field]) . 
                                " - " . htmlspecialchars($participant[$contact_field]) . "</li>";
            }
        }
        
        $body = "
            <p>Dear <strong>" . htmlspecialchars($participant['team_name']) . "</strong>,</p>

<p>Thank you for registering for the <strong>" . htmlspecialchars($participant['event_name']) . "</strong> at the <strong>2nd DRMC National Math Summit</strong>, taking place from <strong>October 09–11, 2025</strong>. We are thrilled to have you join us for this landmark event.</p>

<p><strong>Event Date:</strong> 09 October 2025 - 11 October 2025</p>
            <p><strong>Event Time:</strong> Will be mentioned in the Event Schedule. Event Schedule will be posted in the event page.</p>

<p>Further <strong>instructions</strong>, <strong>schedules</strong>, and <strong>important updates</strong> will be shared on our official <strong>Facebook event page</strong>. Please make sure to follow it closely and check your <strong>email</strong> regularly for any updates.</p>

<p><strong>Registration Details:</strong></p>
<ul>
    <li><strong>Registration Number:</strong> " . htmlspecialchars($participant['registration_number']) . "</li>
    <li><strong>Team Name:</strong> " . htmlspecialchars($participant['team_name']) . "</li>
    <li><strong>Bkash Transaction ID:</strong> " . htmlspecialchars($participant['bkash_transaction_id']) . "</li>
    <li><strong>Category:</strong> " . htmlspecialchars($participant['category']) . "</li>
</ul>

<p><strong>Team Member Details:</strong></p>
<ul>
    $team_members
</ul>

<p>If you have any questions or need <strong>assistance</strong>, feel free to reach out to us through:</p>
<p><strong>Facebook Page:</strong> <a href='https://www.facebook.com/drmcmathclub'>https://www.facebook.com/drmcmathclub</a></p>
<p><strong>Website:</strong> <a href='http://www.drmcmathclub.com'>http://www.drmcmathclub.com</a></p>

<p>We look forward to welcoming you to the <strong>Summit</strong> and wish you the best of luck in the <strong>" . htmlspecialchars($participant['event_name']) . "</strong>!</p>

<p><strong>Best regards,</strong><br><strong>DRMC Math Club</strong></p>

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