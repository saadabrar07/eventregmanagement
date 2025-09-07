<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

$error = '';
$success = '';
$transaction = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transaction_id'])) {
    $transaction_id = sanitizeInput($_POST['transaction_id']);
    
    // Search for transaction
    $sql = "SELECT * FROM transactions WHERE transaction_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $transaction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $transaction = $result->fetch_assoc();
    } else {
        $error = 'Transaction not found.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    $transaction_id = sanitizeInput($_POST['transaction_id']);
    
    // Update transaction status
    $sql = "UPDATE transactions SET status = 'Verified' WHERE transaction_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $transaction_id);
    
    if ($stmt->execute()) {
        $success = 'Transaction verified successfully!';
        
        // Refresh transaction data
        $sql = "SELECT * FROM transactions WHERE transaction_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $transaction_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $transaction = $result->fetch_assoc();
    } else {
        $error = 'Error verifying transaction: ' . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Transaction - DRMC Math Summit</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php require_once '../includes/header.php'; ?>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-blue-600 py-4 px-6">
                <h1 class="text-2xl font-bold text-white">Verify Transaction</h1>
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
                        <label class="block text-gray-700 font-medium mb-2" for="transaction_id">Enter Transaction ID:</label>
                        <input type="text" name="transaction_id" id="transaction_id" required 
                               value="<?php echo isset($_POST['transaction_id']) ? htmlspecialchars($_POST['transaction_id']) : ''; ?>"
                               class="border border-gray-300 rounded px-3 py-2 w-full">
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded">
                        <i class="fas fa-search mr-2"></i>Search Transaction
                    </button>
                </form>
                
                <?php if ($transaction): ?>
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h2 class="text-xl font-semibold mb-4 text-gray-800">Transaction Details</h2>
                        <table class="w-full border-collapse">
                            <tbody>
                                <tr class="border-b border-gray-200">
                                    <th class="py-2 px-4 text-right font-medium text-gray-700 bg-gray-100">Received Amount:</th>
                                    <td class="py-2 px-4"><?php echo number_format($transaction['received_amount'], 2); ?></td>
                                </tr>
                                <tr class="border-b border-gray-200">
                                    <th class="py-2 px-4 text-right font-medium text-gray-700 bg-gray-100">Bkash Number:</th>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($transaction['bkash_number']); ?></td>
                                </tr>
                                <tr class="border-b border-gray-200">
                                    <th class="py-2 px-4 text-right font-medium text-gray-700 bg-gray-100">Fee Amount:</th>
                                    <td class="py-2 px-4"><?php echo number_format($transaction['fee_amount'], 2); ?></td>
                                </tr>
                                <tr class="border-b border-gray-200">
                                    <th class="py-2 px-4 text-right font-medium text-gray-700 bg-gray-100">Transaction ID:</th>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($transaction['transaction_id']); ?></td>
                                </tr>
                                <tr class="border-b border-gray-200">
                                    <th class="py-2 px-4 text-right font-medium text-gray-700 bg-gray-100">Transaction Time:</th>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($transaction['transaction_time']); ?></td>
                                </tr>
                                <tr class="border-b border-gray-200">
                                    <th class="py-2 px-4 text-right font-medium text-gray-700 bg-gray-100">Status:</th>
                                    <td class="py-2 px-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium 
                                            <?php echo $transaction['status'] === 'Verified' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <?php echo htmlspecialchars($transaction['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <?php if ($transaction['status'] === 'Not Verified'): ?>
                            <form method="post" action="" class="mt-4">
                                <input type="hidden" name="transaction_id" value="<?php echo htmlspecialchars($transaction['transaction_id']); ?>">
                                <button type="submit" name="verify" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded">
                                    <i class="fas fa-check-circle mr-2"></i>Verify Transaction
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>