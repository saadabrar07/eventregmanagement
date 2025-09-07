<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$event_filter = isset($_GET['event']) ? sanitizeInput($_GET['event']) : '';

// Get all unique event names for filter dropdown
$event_names = [];
$event_sql = "(SELECT event_name FROM solo_participants GROUP BY event_name) UNION (SELECT event_name FROM group_participants GROUP BY event_name)";
$event_result = $conn->query($event_sql);
while ($row = $event_result->fetch_assoc()) {
    $event_names[] = $row['event_name'];
}

// Build the query
$where_clause = '';
$params = [];
$types = '';

if (!empty($search)) {
    $where_clause = " WHERE (sp.registration_number LIKE ? OR sp.participant_name LIKE ? OR sp.email LIKE ? OR sp.contact_number LIKE ? OR sp.institution LIKE ? OR sp.bkash_transaction_id LIKE ? OR gp.registration_number LIKE ? OR gp.team_name LIKE ? OR gp.email LIKE ? OR gp.bkash_transaction_id LIKE ? OR t.transaction_id LIKE ? OR t.bkash_number LIKE ?)";
    $search_param = "%$search%";
    $params = array_fill(0, 12, $search_param);
    $types = str_repeat('s', 12);
}

if (!empty($event_filter)) {
    $where_clause .= empty($where_clause) ? " WHERE " : " AND ";
    $where_clause .= "(sp.event_name = ? OR gp.event_name = ?)";
    $params[] = $event_filter;
    $params[] = $event_filter;
    $types .= 'ss';
}

// Handle export requests
if (isset($_GET['export'])) {
    $export_type = sanitizeInput($_GET['export']);
    
    $sql = "SELECT 
                COALESCE(sp.registration_number, gp.registration_number) AS registration_number,
                COALESCE(sp.email, gp.email) AS email,
                COALESCE(sp.participant_name, gp.team_name) AS participant_name,
                COALESCE(sp.contact_number, gp.contact1) AS contact_number,
                COALESCE(sp.institution, gp.institution1) AS institution,
                COALESCE(sp.bkash_transaction_id, gp.bkash_transaction_id) AS bkash_transaction_id,
                t.received_amount,
                t.bkash_number,
                t.transaction_time,
                COALESCE(sp.event_name, gp.event_name) AS event_name,
                CASE WHEN sp.id IS NOT NULL THEN 'Solo' ELSE 'Group' END AS participant_type
            FROM transactions t
            LEFT JOIN solo_participants sp ON sp.bkash_transaction_id = t.transaction_id
            LEFT JOIN group_participants gp ON gp.bkash_transaction_id = t.transaction_id
            $where_clause
            ORDER BY t.transaction_time DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $registrations = $result->fetch_all(MYSQLI_ASSOC);
    
    if ($export_type === 'pdf') {
        $html = '<h1>Registration Dashboard</h1>';
        $html .= '<table border="1" cellpadding="5">';
        $html .= '<tr><th>Reg No</th><th>Name/Team</th><th>Email</th><th>Contact</th><th>Institution</th><th>Event</th><th>Type</th><th>Amount</th><th>Bkash No</th><th>Trx ID</th><th>Time</th></tr>';
        
        foreach ($registrations as $r) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($r['registration_number']) . '</td>';
            $html .= '<td>' . htmlspecialchars($r['participant_name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($r['email']) . '</td>';
            $html .= '<td>' . htmlspecialchars($r['contact_number']) . '</td>';
            $html .= '<td>' . htmlspecialchars($r['institution']) . '</td>';
            $html .= '<td>' . htmlspecialchars($r['event_name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($r['participant_type']) . '</td>';
            $html .= '<td>' . number_format($r['received_amount'], 2) . '</td>';
            $html .= '<td>' . htmlspecialchars($r['bkash_number']) . '</td>';
            $html .= '<td>' . htmlspecialchars($r['bkash_transaction_id']) . '</td>';
            $html .= '<td>' . htmlspecialchars($r['transaction_time']) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        generatePDF($html, 'registrations.pdf');
    } elseif ($export_type === 'csv') {
        exportToCSV($registrations, 'registrations.csv');
    }
}

// Get registrations for display
$sql = "SELECT 
            COALESCE(sp.registration_number, gp.registration_number) AS registration_number,
            COALESCE(sp.email, gp.email) AS email,
            COALESCE(sp.participant_name, gp.team_name) AS participant_name,
            COALESCE(sp.contact_number, gp.contact1) AS contact_number,
            COALESCE(sp.institution, gp.institution1) AS institution,
            COALESCE(sp.bkash_transaction_id, gp.bkash_transaction_id) AS bkash_transaction_id,
            t.received_amount,
            t.bkash_number,
            t.transaction_time,
            COALESCE(sp.event_name, gp.event_name) AS event_name,
            CASE WHEN sp.id IS NOT NULL THEN 'Solo' ELSE 'Group' END AS participant_type
        FROM transactions t
        LEFT JOIN solo_participants sp ON sp.bkash_transaction_id = t.transaction_id
        LEFT JOIN group_participants gp ON gp.bkash_transaction_id = t.transaction_id
        $where_clause
        ORDER BY t.transaction_time DESC
        LIMIT 100";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$registrations = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Dashboard - DRMC Math Summit</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php require_once 'includes/header1.php'; ?>
    <div class="container">
        <h1>Registration Dashboard</h1>
        
        <div class="filters">
            <form method="get" action="">
                <div class="form-group">
                    <label for="search">Search:</label>
                    <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="form-group">
                    <label for="event">Event:</label>
                    <select name="event" id="event">
                        <option value="">All Events</option>
                        <?php foreach ($event_names as $event): ?>
                            <option value="<?php echo htmlspecialchars($event); ?>" <?php echo $event_filter === $event ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($event); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn">Apply Filters</button>
                <a href="registration_dashboard.php" class="btn">Reset Filters</a>
            </form>
        </div>
        
        <div class="export-buttons">
            <a href="?export=pdf&<?php echo http_build_query($_GET); ?>" class="btn">Export as PDF</a>
            <a href="?export=csv&<?php echo http_build_query($_GET); ?>" class="btn">Export as CSV</a>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Reg No</th>
                    <th>Name/Team</th>
                    <th>Email</th>
                    <th>Contact</th>
                    <th>Institution</th>
                    <th>Event</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Bkash No</th>
                    <th>Trx ID</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registrations as $r): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($r['registration_number']); ?></td>
                        <td><?php echo htmlspecialchars($r['participant_name']); ?></td>
                        <td><?php echo htmlspecialchars($r['email']); ?></td>
                        <td><?php echo htmlspecialchars($r['contact_number']); ?></td>
                        <td><?php echo htmlspecialchars($r['institution']); ?></td>
                        <td><?php echo htmlspecialchars($r['event_name']); ?></td>
                        <td><?php echo htmlspecialchars($r['participant_type']); ?></td>
                        <td><?php echo number_format($r['received_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($r['bkash_number']); ?></td>
                        <td><?php echo htmlspecialchars($r['bkash_transaction_id']); ?></td>
                        <td><?php echo htmlspecialchars($r['transaction_time']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>