
<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout Confirmation</title>
    
</head>
<body>
    <!-- Overlay -->
    <div class="overlay"></div>

    <!-- Popup -->
    <div class="popup">
        <h2>Are you sure you want to log out?</h2>
        <div class="buttons">
            <button class="yes" onclick="logout()">Yes</button>
            <button class="no" onclick="cancelLogout()">No</button>
        </div>
    </div>

    <script>
        function logout() {
            window.location.href = "logout_process.php"; // Redirect to actual logout process
        }

        function cancelLogout() {
            window.history.back(); // Return to the previous page
        }
    </script>
</body>
</html>
