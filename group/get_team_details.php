<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "SELECT * FROM group_participants WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $team = $result->fetch_assoc();

    if ($team) {
        echo '<div class="space-y-4">';
        echo '<div class="bg-blue-50 p-4 rounded-lg">';
        echo '<h4 class="font-bold text-lg text-blue-800 mb-2">Team Information</h4>';
        echo '<div class="grid grid-cols-2 gap-4">';
        echo '<div><span class="font-medium">Registration Number:</span> '.htmlspecialchars($team['registration_number']).'</div>';
        echo '<div><span class="font-medium">Team Name:</span> '.htmlspecialchars($team['team_name']).'</div>';
        echo '<div><span class="font-medium">Email:</span> '.htmlspecialchars($team['email']).'</div>';
        echo '<div><span class="font-medium">Event:</span> '.htmlspecialchars($team['event_name']).'</div>';
        echo '<div><span class="font-medium">Category:</span> '.htmlspecialchars($team['category']).'</div>';
        echo '<div><span class="font-medium">Bkash Transaction ID:</span> '.htmlspecialchars($team['bkash_transaction_id']).'</div>';
        echo '<div><span class="font-medium">Event Date:</span> '.htmlspecialchars($team['event_date']).'</div>';
        echo '<div><span class="font-medium">Event Time:</span> '.htmlspecialchars($team['event_time']).'</div>';
        echo '</div></div>';

        echo '<div class="bg-gray-50 p-4 rounded-lg">';
        echo '<h4 class="font-bold text-lg text-gray-800 mb-2">Team Members</h4>';
        echo '<div class="space-y-3">';
        
        for ($i = 1; $i <= 5; $i++) {
            if (!empty($team['team_member'.$i])) {
                echo '<div class="border-b pb-2 last:border-0">';
                echo '<h5 class="font-medium text-blue-600">Member '.$i.'</h5>';
                echo '<div class="grid grid-cols-2 gap-2 mt-1">';
                echo '<div><span class="text-gray-600">Name:</span> '.htmlspecialchars($team['team_member'.$i]).'</div>';
                echo '<div><span class="text-gray-600">Institution:</span> '.htmlspecialchars($team['institution'.$i]).'</div>';
                echo '<div><span class="text-gray-600">Contact:</span> '.htmlspecialchars($team['contact'.$i]).'</div>';
                echo '</div></div>';
            }
        }
        
        echo '</div></div>';
        echo '</div>';
    } else {
        echo '<div class="text-center py-8 text-gray-500">Team not found</div>';
    }
} else {
    echo '<div class="text-center py-8 text-gray-500">Invalid request</div>';
}
?>