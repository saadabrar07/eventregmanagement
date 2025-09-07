<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

$where_clause = '';
$params = [];
$types = '';

if (!empty($search)) {
    $where_clause = " WHERE (bkash_number LIKE ? OR transaction_id LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param];
    $types = 'ss';
}

if (!empty($status_filter)) {
    $where_clause .= empty($where_clause) ? " WHERE " : " AND ";
    $where_clause .= "status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

// Handle export requests
if (isset($_GET['export'])) {
    $export_type = sanitizeInput($_GET['export']);
    
    // Get all transactions for export
    $sql = "SELECT * FROM transactions $where_clause ORDER BY transaction_time DESC";
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $transactions = $result->fetch_all(MYSQLI_ASSOC);
    
    if ($export_type === 'pdf') {
        $html = '<h1>Transaction Dashboard</h1>';
        $html .= '<table border="1" cellpadding="5">';
        $html .= '<tr><th>Amount</th><th>Bkash No</th><th>Fee</th><th>Trx ID</th><th>Time</th><th>Status</th></tr>';
        
        foreach ($transactions as $t) {
            $html .= '<tr>';
            $html .= '<td>' . number_format($t['received_amount'], 2) . '</td>';
            $html .= '<td>' . htmlspecialchars($t['bkash_number']) . '</td>';
            $html .= '<td>' . number_format($t['fee_amount'], 2) . '</td>';
            $html .= '<td>' . htmlspecialchars($t['transaction_id']) . '</td>';
            $html .= '<td>' . htmlspecialchars($t['transaction_time']) . '</td>';
            $html .= '<td>' . htmlspecialchars($t['status']) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        generatePDF($html, 'transactions.pdf');
    } elseif ($export_type === 'csv') {
        exportToCSV($transactions, 'transactions.csv');
    }
}

// Get transactions for display
$sql = "SELECT * FROM transactions $where_clause ORDER BY transaction_time DESC LIMIT 100";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$transactions = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Dashboard - DRMC Math Summit</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php require_once '../includes/header.php'; ?>
    <div class="container">
        <h1>Transaction Dashboard</h1>
        
        <div class="filters">
            <form method="get" action="">
                <div class="form-group">
                    <label for="search">Search (Number or TrxID):</label>
                    <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select name="status" id="status">
                        <option value="">All</option>
                        <option value="Verified" <?php echo $status_filter === 'Verified' ? 'selected' : ''; ?>>Verified</option>
                        <option value="Not Verified" <?php echo $status_filter === 'Not Verified' ? 'selected' : ''; ?>>Not Verified</option>
                    </select>
                </div>
                <button type="submit" class="btn">Apply Filters</button>
                <a href="dashboard.php" class="btn">Reset Filters</a>
            </form>
        </div>
        
        <div class="export-buttons">
            <a href="?export=pdf&<?php echo http_build_query($_GET); ?>" class="btn">Export as PDF</a>
            <a href="?export=csv&<?php echo http_build_query($_GET); ?>" class="btn">Export as CSV</a>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Amount</th>
                    <th>Bkash No</th>
                    <th>Fee</th>
                    <th>Balance</th>
                    <th>Trx ID</th>
                    <th>Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $t): ?>
                    <tr>
                        <td><?php echo number_format($t['received_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($t['bkash_number']); ?></td>
                        <td><?php echo number_format($t['fee_amount'], 2); ?></td>
                        <td><?php echo number_format($t['current_balance'], 2); ?></td>
                        <td><?php echo htmlspecialchars($t['transaction_id']); ?></td>
                        <td><?php echo htmlspecialchars($t['transaction_time']); ?></td>
                        <td><?php echo htmlspecialchars($t['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>