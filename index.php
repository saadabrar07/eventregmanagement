<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | DRMC National Math Summit</title>

    <!-- Google Font: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Main stylesheet -->
    <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>
<header class="header">
    <h1 class="brand">DRMC National Math Summit <span>Admin</span></h1>
</header>

<main class="container">

    <section class="grid">

        <!-- Solo -->
        <article class="card">
            <h2>Solo Participants</h2>
            <a class="btn" href="solo/upload.php">Upload CSV</a>
            <a class="btn-outline" href="solo/manage.php">Manage Participants</a>
        </article>

        <!-- Group -->
        <article class="card">
            <h2>Group Participants</h2>
            <a class="btn" href="group/upload.php">Upload CSV</a>
            <a class="btn-outline" href="group/manage.php">Manage Teams</a>
        </article>

        <!-- Transactions -->
        <article class="card">
            <h2>Transactions</h2>
            <a class="btn" href="transaction/upload.php">Upload Transaction</a>
            <a class="btn-outline" href="transaction/verify.php">Verify Transaction</a>
            <a class="btn-outline" href="transaction/dashboard.php">Dashboard</a>
        </article>

        <!-- Registration Dashboard -->
        <article class="card">
            <h2>Registration Dashboard</h2>
            <a class="btn" href="registration_dashboard.php">View All Registrations</a>
        </article>

    </section>
</main>

<footer class="footer">
    © <?= date('Y') ?> DRMC Math Club
</footer>
</body>
</html>
