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
    $where_clause .= "(registration_number LIKE ? OR team_name LIKE ? OR email LIKE ? OR contact1 LIKE ? OR institution1 LIKE ? OR bkash_transaction_id LIKE ?)";
    $search_param = "%$search_term%";
    $params = array_merge($params, array_fill(0, 6, $search_param));
    $types .= str_repeat('s', 6);
}

// Get all unique event names for filter dropdown
$event_names = [];
$event_sql = "SELECT DISTINCT event_name FROM group_participants ORDER BY event_name";
$event_result = $conn->query($event_sql);
while ($row = $event_result->fetch_assoc()) {
    $event_names[] = $row['event_name'];
}

// Get participants data
$sql = "SELECT * FROM group_participants $where_clause ORDER BY created_at DESC";
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
        $html = '<h1>Group Participants</h1>';
        $html .= '<table border="1" cellpadding="5">';
        $html .= '<tr><th>Reg No</th><th>Team Name</th><th>Email</th><th>Contact</th><th>Institution</th><th>Event</th><th>Date</th><th>Time</th><th>Status</th></tr>';
        
        foreach ($participants as $p) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($p['registration_number']) . '</td>';
            $html .= '<td>' . htmlspecialchars($p['team_name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($p['email']) . '</td>';
            $html .= '<td>' . htmlspecialchars($p['contact1']) . '</td>';
            $html .= '<td>' . htmlspecialchars($p['institution1']) . '</td>';
            $html .= '<td>' . htmlspecialchars($p['event_name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($p['event_date']) . '</td>';
            $html .= '<td>' . htmlspecialchars($p['event_time']) . '</td>';
            $html .= '<td>' . htmlspecialchars($p['mail_status']) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        generatePDF($html, 'group_participants.pdf');
    } elseif ($export_type === 'csv') {
        exportToCSV($participants, 'group_participants.csv');
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
    <title>Manage Group Participants - DRMC Math Summit</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
    /* Modal styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        overflow-y: auto;
    }
    
    .modal.active {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        padding-top: 50px;
    }
    
    .modal-content {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        width: 90%;
        max-width: 800px;
        max-height: 90vh;
        overflow-y: auto;
        animation: modalFadeIn 0.3s ease-out;
    }
    
    @keyframes modalFadeIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .loader {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #3498db;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
        margin: 0 auto;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Team details styles */
    .team-details {
        padding: 20px;
    }
    
    .team-details h4 {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: #2d3748;
    }
    
    .detail-row {
        display: flex;
        margin-bottom: 0.75rem;
    }
    
    .detail-label {
        font-weight: 600;
        color: #4a5568;
        min-width: 150px;
    }
    
    .detail-value {
        color: #2d3748;
    }
    
    .team-members {
        margin-top: 1.5rem;
    }
    
    .member-card {
        background-color: #f8fafc;
        border-radius: 6px;
        padding: 1rem;
        margin-bottom: 1rem;
        border-left: 4px solid #4299e1;
    }
</style>
</head>
<body class="bg-gray-100">
    <?php require_once '../includes/header.php'; ?>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto card">
            <div class="card-header">
                <h1 class="text-2xl font-bold text-white">Manage Group Participants</h1>
            </div>
            
            <div class="card-body">
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success animate-fade">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="bg-gray-50 p-4 rounded-lg mb-6">
                    <form method="get" action="" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2" for="event_filter">Filter by Event:</label>
                            <select name="event_filter" id="event_filter" class="form-control">
                                <option value="">All Events</option>
                                <?php foreach ($event_names as $event): ?>
                                    <option value="<?php echo htmlspecialchars($event); ?>" <?php echo $filter_event === $event ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($event); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2" for="search">Search:</label>
                            <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search_term); ?>"
                                   class="form-control" placeholder="Search by any field...">
                        </div>
                        <div class="flex items-end space-x-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter mr-2"></i>Apply
                            </button>
                            <a href="manage.php" class="btn btn-info">
                                <i class="fas fa-sync-alt mr-2"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>
                
                <div class="flex flex-wrap justify-between items-center mb-4 gap-2">
                    <div class="flex flex-wrap gap-2">
                        <a href="?export=pdf&<?php echo http_build_query($_GET); ?>" class="btn btn-warning">
                            <i class="fas fa-file-pdf mr-2"></i>Export PDF
                        </a>
                        <a href="?export=csv&<?php echo http_build_query($_GET); ?>" class="btn btn-success">
                            <i class="fas fa-file-csv mr-2"></i>Export CSV
                        </a>
                    </div>
                    <div>
                        <a href="upload.php" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i>Add New Team
                        </a>
                    </div>
                </div>
                
                <form id="emailForm" method="post" action="send_email.php">
                    <div class="bg-blue-50 p-3 rounded-lg mb-4 flex flex-wrap gap-2">
                        <button type="button" id="selectNotSent" class="btn btn-warning">
                            <i class="fas fa-envelope mr-2"></i>Select Not Sent
                        </button>
                        <button type="button" id="selectAll" class="btn btn-info">
                            <i class="fas fa-check-circle mr-2"></i>Select All
                        </button>
                        <button type="button" id="deselectAll" class="btn btn-secondary">
                            <i class="fas fa-times-circle mr-2"></i>Deselect All
                        </button>
                        <button type="submit" id="sendEmails" class="btn btn-success hidden">
                            <i class="fas fa-paper-plane mr-2"></i>Send Selected Emails
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th class="text-center">Select</th>
                                    <th>Reg No</th>
                                    <th>Team Name</th>
                                    <th>Email</th>
                                    <th>Contact</th>
                                    <th>Event</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($participants as $p): ?>
                                    <tr>
                                        <td class="text-center"><center>
                                            <input type="checkbox" name="selected_ids[]" value="<?php echo $p['id']; ?>" 
                                                data-status="<?php echo $p['mail_status']; ?>"
                                                <?php echo in_array($p['id'], $selected_ids) ? 'checked' : ''; ?>
                                                class="form-checkbox h-5 w-5 text-blue-600"></center>
                                        </td>
                                        <td><?php echo htmlspecialchars($p['registration_number']); ?></td>
                                        <td><?php echo htmlspecialchars($p['team_name']); ?></td>
                                        <td><?php echo htmlspecialchars($p['email']); ?></td>
                                        <td><?php echo htmlspecialchars($p['contact1']); ?></td>
                                        <td><?php echo htmlspecialchars($p['event_name']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $p['mail_status'] === 'sent' ? 'badge-success' : 'badge-warning'; ?>">
                                                <?php echo htmlspecialchars($p['mail_status']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" onclick="showTeamDetails(<?php echo $p['id']; ?>)" 
                                                    class="text-blue-600 hover:text-blue-800 mr-2">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Team Details Modal -->
<div id="teamModal" class="modal">
    <div class="modal-content">
        <div class="flex justify-between items-center border-b px-6 py-4">
            <h3 class="text-xl font-bold text-gray-800">Team Details</h3>
            <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 text-2xl">
                &times;
            </button>
        </div>
        <div class="p-6" id="teamDetailsContent">
            <div class="text-center py-8">
                <div class="loader mx-auto"></div>
                <p class="mt-2 text-gray-600">Loading team details...</p>
            </div>
        </div>
    </div>
</div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle Send Emails button based on selections
            const checkboxes = document.querySelectorAll('input[type="checkbox"][name="selected_ids[]"]');
            const sendEmailsBtn = document.getElementById('sendEmails');
            
            function updateSendButton() {
                const checked = document.querySelectorAll('input[type="checkbox"][name="selected_ids[]"]:checked').length > 0;
                sendEmailsBtn.classList.toggle('hidden', !checked);
            }
            
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSendButton);
            });
            
            // Select all not sent emails
            document.getElementById('selectNotSent').addEventListener('click', function() {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = checkbox.dataset.status === 'not sent';
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

            function showTeamDetails(teamId) {
        const modal = document.getElementById('teamModal');
        const content = document.getElementById('teamDetailsContent');
        
        // Show loading state
        content.innerHTML = `
            <div class="text-center py-8">
                <div class="loader mx-auto"></div>
                <p class="mt-2 text-gray-600">Loading team details...</p>
            </div>
        `;
        
        // Show modal
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Fetch team details
        fetch(`get_team_details.php?id=${teamId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(html => {
                content.innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
                content.innerHTML = `
                    <div class="text-center py-8 text-red-500">
                        <i class="fas fa-exclamation-circle text-2xl mb-2"></i>
                        <p>Error loading team details. Please try again.</p>
                        <button onclick="closeModal()" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                            Close
                        </button>
                    </div>
                `;
            });
    }

    function closeModal() {
        document.getElementById('teamModal').classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    // Close modal when clicking outside or pressing ESC
    document.addEventListener('click', function(e) {
        if (e.target === document.getElementById('teamModal')) {
            closeModal();
        }
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.getElementById('teamModal').classList.contains('active')) {
            closeModal();
        }
    });
    </script>
</body>
</html>