<?php
// Start the session
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page with a simple styled confirmation
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out</title>
    
</head>
<body>
    <div class="logout-container">
        <h1>Logged Out</h1>
        <p>You have been successfully logged out. Redirecting you shortly...</p>
        <a href="login.php" class="btn">Back to Login</a>
    </div>

    <script>
        // Redirect to login page after 2 seconds
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 2000);
    </script>
</body>
</html>
