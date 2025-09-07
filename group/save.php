<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$success_count = 0;
$error_count = 0;
$duplicate_count = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['data'])) {
    $data = $_POST['data'];

    foreach ($data as $json_row) {
        $row = json_decode($json_row, true);

        // 1. Check for duplicate registration number
        $check_sql = "SELECT id FROM group_participants WHERE registration_number = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('s', $row['registration_number']);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $duplicate_count++;
            continue;
        }

        // 2. Insert new row (23 values)
        $insert_sql = "
            INSERT INTO group_participants (
                registration_number, email, team_name,
                team_member1, institution1, contact1,
                team_member2, institution2, contact2,
                team_member3, institution3, contact3,
                team_member4, institution4, contact4,
                team_member5, institution5, contact5,
                event_name, category, bkash_transaction_id,
                event_date, event_time
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ";

        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param(
            str_repeat('s', 23),
            $row['registration_number'],
            $row['email'],
            $row['team_name'],
            $row['team_member1'],
            $row['institution1'],
            $row['contact1'],
            $row['team_member2'],
            $row['institution2'],
            $row['contact2'],
            $row['team_member3'],
            $row['institution3'],
            $row['contact3'],
            $row['team_member4'],
            $row['institution4'],
            $row['contact4'],
            $row['team_member5'],
            $row['institution5'],
            $row['contact5'],
            $row['event_name'],
            $row['category'],
            $row['bkash_transaction_id'],
            $row['event_date'],
            $row['event_time']
        );

        if ($insert_stmt->execute()) {
            $success_count++;
        } else {
            $error_count++;
        }
    }

    $_SESSION['message'] = "Saved: $success_count — Errors: $error_count — Duplicates: $duplicate_count";
    header('Location: manage.php');
    exit;
}

header('Location: upload.php');
exit;
?>
