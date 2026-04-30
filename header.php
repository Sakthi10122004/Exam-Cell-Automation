<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure db_config is included safely if not already
if (!isset($conn)) {
    @include_once 'db_config.php';
}

$homeLink = 'index.php'; // Default fallback
if (isset($_SESSION['role'])) {
    switch (strtolower($_SESSION['role'])) {
        case 'superadmin':
            $homeLink = 'super_admin_dashboard.php';
            break;
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

// Fetch dynamic header links
$navLinks = [
    ["label" => "Home", "url" => "index.php"],
    ["label" => "About Us", "url" => "about.php"],
    ["label" => "Services", "url" => "services.php"],
    ["label" => "Contact", "url" => "contact.php"]
];

if (isset($conn) && $conn) {
    $result = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key = 'header_links'");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $navLinks = json_decode($row['setting_value'], true);
    }
}

$isSuperAdmin = isset($_SESSION['role']) && $_SESSION['role'] == 'superadmin';
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
<body class="<?php echo $isSuperAdmin ? 'has-superadmin' : ''; ?>">
    
    <?php if ($isSuperAdmin): ?>
    <div class="superadmin-banner">
        <i class="fas fa-shield-alt"></i> Super Administrator Mode - You have full access to system settings.
    </div>
    <?php endif; ?>

    <header>
        <div class="header-content">
            <div class="logo-section">
                <img src="logo.png" alt="Company Logo" width="60" onerror="this.style.display='none';">
                <h1>Exam Cell Automation</h1>
            </div>
            <div class="nav-links">
                <?php
                if (isset($_SESSION['role'])) {
                    echo "<a href='$homeLink'>Dashboard</a>";
                }
                
                foreach ($navLinks as $link) {
                    echo "<a href='" . htmlspecialchars($link['url']) . "'>" . htmlspecialchars($link['label']) . "</a>";
                }
                
                if (isset($_SESSION['role'])) {
                    echo "<a href='logout.php' class='btn-logout'><i class='fas fa-sign-out-alt'></i> Logout</a>";
                } else {
                    echo "<a href='login.php' class='btn-logout'><i class='fas fa-sign-in-alt'></i> Login</a>";
                }
                ?>
            </div>
        </div>
    </header>