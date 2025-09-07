<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

$error = '';
$success = '';
$preview_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transaction_text'])) {
    $text = trim($_POST['transaction_text']);
    
    // Parse the transaction text
    $pattern1 = '/You have received Tk ([0-9,]+\.\d{2}) from (\d+). Fee Tk ([0-9,]+\.\d{2}). Balance Tk ([0-9,]+\.\d{2}). TrxID (\w+) at (.+)/';
    $pattern2 = '/Cash In Tk ([0-9,]+\.\d{2}) from (\d+) successful. Fee Tk ([0-9,]+\.\d{2}). Balance Tk ([0-9,]+\.\d{2}). TrxID (\w+) at (.+?)\./';
    
    if (preg_match($pattern1, $text, $matches) || preg_match($pattern2, $text, $matches)) {
    // Convert date/time to MySQL format
    $date_time = DateTime::createFromFormat('d/m/Y H:i', $matches[6]);
    if (!$date_time) {
        $date_time = DateTime::createFromFormat('d/m/Y H:i:s', $matches[6]);
    }
    
    $mysql_datetime = $date_time ? $date_time->format('Y-m-d H:i:s') : date('Y-m-d H:i:s');
    
    $preview_data = [
        'received_amount' => str_replace(',', '', $matches[1]),
        'bkash_number' => $matches[2],
        'fee_amount' => str_replace(',', '', $matches[3]),
        'current_balance' => str_replace(',', '', $matches[4]),
        'transaction_id' => $matches[5],
        'transaction_time' => $mysql_datetime
    ];
    } elseif (preg_match($pattern2, $text, $matches)) {
        $preview_data = [
            'received_amount' => str_replace(',', '', $matches[1]),
            'bkash_number' => $matches[2],
            'fee_amount' => str_replace(',', '', $matches[3]),
            'current_balance' => str_replace(',', '', $matches[4]),
            'transaction_id' => $matches[5],
            'transaction_time' => $matches[6]
        ];
    } else {
        $error = 'Could not parse transaction text. Please check the format.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save']) && !empty($preview_data)) {
    // Check for duplicate transaction ID
    $check_sql = "SELECT id FROM transactions WHERE transaction_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('s', $preview_data['transaction_id']);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        $error = 'This transaction ID already exists in the database.';
    } else {
        $insert_sql = "INSERT INTO transactions (
            received_amount, bkash_number, fee_amount, current_balance,
            transaction_id, transaction_time, status
        ) VALUES (?, ?, ?, ?, ?, ?, 'Not Verified')";
        
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param(
            'ssssss',
            $preview_data['received_amount'],
            $preview_data['bkash_number'],
            $preview_data['fee_amount'],
            $preview_data['current_balance'],
            $preview_data['transaction_id'],
            $preview_data['transaction_time']
        );
        
        if ($insert_stmt->execute()) {
            $success = 'Transaction saved successfully!';
            $preview_data = [];
        } else {
            $error = 'Error saving transaction: ' . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Transaction - DRMC Math Summit</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php require_once '../includes/header.php'; ?>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-blue-600 py-4 px-6">
                <h1 class="text-2xl font-bold text-white">Upload Transaction</h1>
            </div>
            
            <div class="p-6">
                <?php if (!empty($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="" class="mb-8">
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-2" for="transaction_text">Paste Transaction Text:</label>
                        <textarea name="transaction_text" id="transaction_text" rows="4" required
                                  class="border border-gray-300 rounded px-3 py-2 w-full"><?php echo isset($_POST['transaction_text']) ? htmlspecialchars($_POST['transaction_text']) : ''; ?></textarea>
                        <p class="text-gray-500 text-sm mt-1">Example: You have received Tk 2,800.00 from 01973781005. Fee Tk 0.00. Balance Tk 2,825.00. TrxID CD858UYSJL at 08/04/2025 12:29</p>
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded">
                        <i class="fas fa-eye mr-2"></i>Preview Transaction
                    </button>
                </form>
                
                <?php if (!empty($preview_data)): ?>
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h2 class="text-xl font-semibold mb-4 text-gray-800">Transaction Preview</h2>
                        <table class="w-full border-collapse">
                            <tbody>
                                <tr class="border-b border-gray-200">
                                    <th class="py-2 px-4 text-right font-medium text-gray-700 bg-gray-100">Received Amount:</th>
                                    <td class="py-2 px-4"><?php echo number_format($preview_data['received_amount'], 2); ?></td>
                                </tr>
                                <tr class="border-b border-gray-200">
                                    <th class="py-2 px-4 text-right font-medium text-gray-700 bg-gray-100">Bkash Number:</th>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($preview_data['bkash_number']); ?></td>
                                </tr>
                                <tr class="border-b border-gray-200">
                                    <th class="py-2 px-4 text-right font-medium text-gray-700 bg-gray-100">Fee Amount:</th>
                                    <td class="py-2 px-4"><?php echo number_format($preview_data['fee_amount'], 2); ?></td>
                                </tr>
                                <tr class="border-b border-gray-200">
                                    <th class="py-2 px-4 text-right font-medium text-gray-700 bg-gray-100">Current Balance:</th>
                                    <td class="py-2 px-4"><?php echo number_format($preview_data['current_balance'], 2); ?></td>
                                </tr>
                                <tr class="border-b border-gray-200">
                                    <th class="py-2 px-4 text-right font-medium text-gray-700 bg-gray-100">Transaction ID:</th>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($preview_data['transaction_id']); ?></td>
                                </tr>
                                <tr class="border-b border-gray-200">
                                    <th class="py-2 px-4 text-right font-medium text-gray-700 bg-gray-100">Transaction Time:</th>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($preview_data['transaction_time']); ?></td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <form method="post" action="" class="mt-4">
                            <input type="hidden" name="transaction_text" value="<?php echo isset($_POST['transaction_text']) ? htmlspecialchars($_POST['transaction_text']) : ''; ?>">
                            <button type="submit" name="save" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded">
                                <i class="fas fa-save mr-2"></i>Save Transaction
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>