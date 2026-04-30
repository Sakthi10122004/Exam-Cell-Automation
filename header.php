<?php
$homeLink = '#'; // Default fallback
if (isset($_SESSION['user_role'])) { // Changed to user_role
    switch (strtolower($_SESSION['user_role'])) { // Convert to lowercase for consistency
        case 'admin':
            $homeLink = 'admin_dashboard.php';
            break;
        case 'teacher':
            $homeLink = 'teacher_dashboard.php';
            break;
        case 'student':
            $homeLink = 'student_dashboard.php';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Cell Automation</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="left-section">
                <div class="logo">
                    <img src="logo.png" alt="Company Logo" width="60">
                </div>
                <h1>Exam Cell Automation</h1>
            </div>
            <div class="right-section">
                <?php
                if (isset($_SESSION['user_role'])) { // Changed to user_role
                    echo "<a href='$homeLink'>Home</a>";
                } else {
                    echo "<a href='login.php'>Home</a>";
                }
                ?>
                <a href="about.php">About Us</a>
                <a href="services.php">Services</a>
                <a href="contact.php">Contact</a>
                <a href="logout.php">Logout</a>

                <?php
                if (isset($_SESSION['user_role'])) { // Changed to user_role
                    echo "<a href='logout.php'>Logout</a>";
                }
                ?>
            </div>
        </div>
    </header>
</body>
</html>