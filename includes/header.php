<?php
// header.php
?>

    <header class="header">
        <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
        }
        
        .header {
            background-color: #1e88e5; /* Blue background */
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .main-menu {
            display: flex;
            list-style: none;
        }
        
        .main-menu li {
            position: relative;
            margin-right: 20px;
        }
        
        .main-menu li a {
            color: black;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
            transition: background 0.3s;
        }
        
        .main-menu li a:hover {
            background-color: #1565c0;
            border-radius: 4px;
        }
        
        .sub-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background-color: #42a5f5;
            list-style: none;
            min-width: 200px;
            border-radius: 0 0 4px 4px;
            z-index: 100;
        }
        
        .main-menu li:hover .sub-menu {
            display: block;
        }
        
        .sub-menu li a {
            padding: 10px 15px;
            border-bottom: 1px solid #90caf9;
        }
        
        .sub-menu li:last-child a {
            border-bottom: none;
        }
        
        .active {
            background-color: #0d47a1;
            border-radius: 4px;
        }
    </style>
        <div class="container">
            <nav>
                <ul class="main-menu">
                    <li>
                        <a href="../index.php" <?php if(basename($_SERVER['PHP_SELF']) == 'index.php') echo 'class="active"'; ?>>Home Page</a>
                    </li>
                    <li>
                        <a href="../solo/manage.php">Solo Registration</a>
                        <ul class="sub-menu">
                            <li><a href="../solo/upload.php" <?php if(basename($_SERVER['PHP_SELF']) == 'solo_upload.php') echo 'class="active"'; ?>>Upload</a></li>
                            <li><a href="../solo/manage.php" <?php if(basename($_SERVER['PHP_SELF']) == 'solo_manage.php') echo 'class="active"'; ?>>Manage</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="../group/manage.php">Group Registration</a>
                        <ul class="sub-menu">
                            <li><a href="../group/upload.php" <?php if(basename($_SERVER['PHP_SELF']) == 'group_upload.php') echo 'class="active"'; ?>>Upload</a></li>
                            <li><a href="../group/manage.php" <?php if(basename($_SERVER['PHP_SELF']) == 'group_manage.php') echo 'class="active"'; ?>>Manage</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="../transaction/dashboard.php">Transaction Dashboard</a>
                        <ul class="sub-menu">
                            <li><a href="../transaction/upload.php" <?php if(basename($_SERVER['PHP_SELF']) == 'transaction_upload.php') echo 'class="active"'; ?>>Upload</a></li>
                            <li><a href="../transaction/verify.php" <?php if(basename($_SERVER['PHP_SELF']) == 'verify.php') echo 'class="active"'; ?>>Verify Transaction</a></li>
                            <li><a href="../transaction/dashboard.php" <?php if(basename($_SERVER['PHP_SELF']) == 'transaction_dashboard.php') echo 'class="active"'; ?>>Dashboard</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="../registration_dashboard.php" <?php if(basename($_SERVER['PHP_SELF']) == 'registration_dashboard.php') echo 'class="active"'; ?>>Registration Dashboard</a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>
    