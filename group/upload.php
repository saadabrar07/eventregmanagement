<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

$error = '';
$preview_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $file_path = $file['tmp_name'];
        
        if (($handle = fopen($file_path, 'r')) !== FALSE) {
            // Skip header row
            fgetcsv($handle);
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                if (count($data) >= 22) {
                    $preview_data[] = [
                        'registration_number' => $data[0],
                        'email' => $data[1],
                        'team_name' => $data[2],
                        'team_member1' => $data[3],
                        'institution1' => $data[4],
                        'contact1' => $data[5],
                        'team_member2' => $data[6],
                        'institution2' => $data[7],
                        'contact2' => $data[8],
                        'team_member3' => $data[9],
                        'institution3' => $data[10],
                        'contact3' => $data[11],
                        'team_member4' => $data[12],
                        'institution4' => $data[13],
                        'contact4' => $data[14],
                        'team_member5' => $data[15],
                        'institution5' => $data[16],
                        'contact5' => $data[17],
                        'event_name' => $data[18],
                        'category' => $data[19],
                        'bkash_transaction_id' => $data[20],
                        'event_date' => $data[21],
                        'event_time' => $data[22]
                    ];
                }
            }
            fclose($handle);
        } else {
            $error = 'Error reading CSV file.';
        }
    } else {
        $error = 'Error uploading file.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Participant Upload - DRMC Math Summit</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php require_once '../includes/header.php'; ?>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-blue-600 py-4 px-6">
                <h1 class="text-2xl font-bold text-white">Group Participant Upload</h1>
            </div>
            
            <div class="p-6">
                <?php if (!empty($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form action="" method="post" enctype="multipart/form-data" class="mb-8">
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-2" for="csv_file">Upload CSV File:</label>
                        <div class="flex items-center">
                            <input type="file" name="csv_file" id="csv_file" accept=".csv" required
                                   class="border border-gray-300 rounded px-3 py-2 w-full">
                        </div>
                        <p class="text-gray-500 text-sm mt-1">CSV format: Registration Number, Email, Team Name, Team Member 1-5 with Institution and Contact, Event Name, Category, Bkash Transaction ID, Event Date, Event Time</p>
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded">
                        <i class="fas fa-eye mr-2"></i>Preview Data
                    </button>
                </form>
                
                <?php if (!empty($preview_data)): ?>
                    <h2 class="text-xl font-semibold mb-4 text-gray-800">Preview Data</h2>
                    <form action="save.php" method="post">
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white border border-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="py-2 px-4 border-b">Reg No</th>
                                        <th class="py-2 px-4 border-b">Team Name</th>
                                        <th class="py-2 px-4 border-b">Email</th>
                                        <th class="py-2 px-4 border-b">Event</th>
                                        <th class="py-2 px-4 border-b">Members</th>
                                        <th class="py-2 px-4 border-b">Date</th>
                                        <th class="py-2 px-4 border-b">Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($preview_data as $row): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($row['registration_number']); ?></td>
                                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($row['team_name']); ?></td>
                                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($row['email']); ?></td>
                                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($row['event_name']); ?></td>
                                            <td class="py-2 px-4 border-b">
                                                <?php 
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        if (!empty($row['team_member'.$i])) {
                                                            echo htmlspecialchars($row['team_member'.$i])."<br>";
                                                        }
                                                    }
                                                ?>
                                            </td>
                                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($row['event_date']); ?></td>
                                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($row['event_time']); ?></td>
                                        </tr>
                                        <input type="hidden" name="data[]" value="<?php echo htmlspecialchars(json_encode($row)); ?>">
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded">
                                <i class="fas fa-save mr-2"></i>Save to Database
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>