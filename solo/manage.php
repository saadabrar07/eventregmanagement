<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

session_start();

$db = new Database();
$conn = $db->getConnection();

$filter_event = isset($_GET['event_filter']) ? sanitizeInput($_GET['event_filter']) : '';
$search_term = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

$where_clause = '';
$params = [];
$types = '';

if (!empty($filter_event)) {
    $where_clause .= " WHERE event_name = ?";
    $params[] = $filter_event;
    $types .= 's';
}

if (!empty($search_term)) {
    $where_clause .= empty($where_clause) ? " WHERE " : " AND ";
    $where_clause .= "(registration_number LIKE ? OR participant_name LIKE ? OR email LIKE ? OR contact_number LIKE ? OR institution LIKE ? OR bkash_transaction_id LIKE ?)";
    $search_param = "%$search_term%";
    $params = array_merge($params, array_fill(0, 6, $search_param));
    $types .= str_repeat('s', 6);
}

// Get all unique event names for filter dropdown
$event_names = [];
$event_sql = "SELECT DISTINCT event_name FROM solo_participants ORDER BY event_name";
$event_result = $conn->query($event_sql);
while ($row = $event_result->fetch_assoc()) {
    $event_names[] = $row['event_name'];
}

// Get participants data
$sql = "SELECT * FROM solo_participants $where_clause ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$participants = $result->fetch_all(MYSQLI_ASSOC);

// Handle export requests
if (isset($_GET['export'])) {
    $export_type = sanitizeInput($_GET['export']);
    
    if ($export_type === 'pdf') {
        $html = '<h1>Solo Participants</h1>';
        $html .= '<table border="1" cellpadding="5">';
        $html .= '<tr><th>Reg No</th><th>Name</th><th>Email</th><th>Contact</th><th>Institution</th><th>Event</th><th>Date</th><th>Time</th><th>Status</th></tr>';
        
        foreach ($participants as $p) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($p['registration_number']) . '</td>';
            $html .= '<td>' . htmlspecialchars($p['participant_name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($p['email']) . '</td>';
            $html .= '<td>' . htmlspecialchars($p['contact_number']) . '</td>';
            $html .= '<td>' . htmlspecialchars($p['institution']) . '</td>';
            $html .= '<td>' . htmlspecialchars($p['event_name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($p['event_date']) . '</td>';
            $html .= '<td>' . htmlspecialchars($p['event_time']) . '</td>';
            $html .= '<td>' . htmlspecialchars($p['mail_status']) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        generatePDF($html, 'solo_participants.pdf');
    } elseif ($export_type === 'csv') {
        exportToCSV($participants, 'solo_participants.csv');
    }
}

// Handle email sending preparation
$selected_ids = [];
if (isset($_POST['selected_ids'])) {
    $selected_ids = $_POST['selected_ids'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Solo Participants - DRMC Math Summit</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/script.js"></script>
</head>
<body>
    <?php require_once '../includes/header.php'; ?>
    <div class="container">
        <h1>Manage Solo Participants</h1>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        
        <div class="filters">
            <form method="get" action="">
                <div class="form-group">
                    <label for="event_filter">Filter by Event:</label>
                    <select name="event_filter" id="event_filter">
                        <option value="">All Events</option>
                        <?php foreach ($event_names as $event): ?>
                            <option value="<?php echo htmlspecialchars($event); ?>" <?php echo $filter_event === $event ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($event); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="search">Search:</label>
                    <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search_term); ?>">
                </div>
                <button type="submit" class="btn">Apply Filters</button>
                <a href="manage.php" class="btn">Reset Filters</a>
            </form>
        </div>
        
        <div class="export-buttons">
            <a href="?export=pdf&<?php echo http_build_query($_GET); ?>" class="btn">Export as PDF</a>
            <a href="?export=csv&<?php echo http_build_query($_GET); ?>" class="btn">Export as CSV</a>
                        <a href="upload.php" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i>Add New Participants
                        </a>
        </div>
        
        <form id="emailForm" method="post" action="send_email.php">
            <div class="email-controls">
                <button type="button" id="selectNotSent" class="btn">Select All Not Sent</button>
                <button type="button" id="selectAll" class="btn">Select All</button>
                <button type="button" id="deselectAll" class="btn">Deselect All</button>
                <button type="submit" id="sendEmails" class="btn btn-primary" style="display: none;">Send Selected Emails</button>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Select</th>
                        <th>Reg No</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Institution</th>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($participants as $p): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="selected_ids[]" value="<?php echo $p['id']; ?>" 
                                    data-status="<?php echo $p['mail_status']; ?>"
                                    <?php echo in_array($p['id'], $selected_ids) ? 'checked' : ''; ?>>
                            </td>
                            <td><?php echo htmlspecialchars($p['registration_number']); ?></td>
                            <td><?php echo htmlspecialchars($p['participant_name']); ?></td>
                            <td><?php echo htmlspecialchars($p['email']); ?></td>
                            <td><?php echo htmlspecialchars($p['contact_number']); ?></td>
                            <td><?php echo htmlspecialchars($p['institution']); ?></td>
                            <td><?php echo htmlspecialchars($p['event_name']); ?></td>
                            <td><?php echo htmlspecialchars($p['event_date']); ?></td>
                            <td><?php echo htmlspecialchars($p['event_time']); ?></td>
                            <td><?php echo htmlspecialchars($p['mail_status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle Send Emails button based on selections
            const checkboxes = document.querySelectorAll('input[type="checkbox"][name="selected_ids[]"]');
            const sendEmailsBtn = document.getElementById('sendEmails');
            
            function updateSendButton() {
                const checked = document.querySelectorAll('input[type="checkbox"][name="selected_ids[]"]:checked').length > 0;
                sendEmailsBtn.style.display = checked ? 'inline-block' : 'none';
            }
            
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSendButton);
            });
            
            // Select all not sent emails
            document.getElementById('selectNotSent').addEventListener('click', function() {
                checkboxes.forEach(checkbox => {
                    if (checkbox.dataset.status === 'not sent') {
                        checkbox.checked = true;
                    } else {
                        checkbox.checked = false;
                    }
                });
                updateSendButton();
            });
            
            // Select all emails
            document.getElementById('selectAll').addEventListener('click', function() {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = true;
                });
                updateSendButton();
            });
            
            // Deselect all emails
            document.getElementById('deselectAll').addEventListener('click', function() {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                updateSendButton();
            });
            
            // Initialize button state
            updateSendButton();
        });
    </script>
</body>
</html>