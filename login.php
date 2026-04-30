<?php
session_start();
include 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $username; // Store username in session

            if ($user['role'] == 'admin') {
                header("Location: admin_dashboard.php");
            } elseif ($user['role'] == 'teacher') {
                header("Location: teacher_dashboard.php");
            } else {
                header("Location: student_dashboard.php");
            }
            exit();
        } else {
            echo "Invalid password!";
        }
    } else {
        echo "Invalid username!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Access</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="ambient-orb orb-1"></div>
    <div class="ambient-orb orb-2"></div>
    <div class="bg-grid"></div>

    <div class="auth-container">
        <div class="auth-header">
            <h2>Welcome Back</h2>
            <p>Access your exam management dashboard.</p>
        </div>

        <form method="POST">
            <div class="input-group">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <input type="text" name="username" id="username" placeholder="Enter your username" required autocomplete="off">
                </div>
            </div>
            
            <div class="input-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input type="password" name="password" id="password" placeholder="Enter your password" required>
                    <i class="fas fa-eye password-toggle" id="eye-icon"></i>
                </div>
            </div>
            
            <button type="submit">Authenticate</button>
        </form>

        <div class="auth-footer">
            New to the platform? <a href="register.php">Create an account</a>
        </div>
    </div>

    <script>
        const passwordField = document.getElementById('password');
        const eyeIcon = document.getElementById('eye-icon');

        eyeIcon.addEventListener('click', function () {
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        });
        
        // Show error msg if URL contains error param (standard PHP fallback simulation)
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('error')) {
            const errorBox = document.createElement('div');
            errorBox.className = 'error-msg visible';
            errorBox.textContent = 'Invalid username or password.';
            document.querySelector('.auth-header').insertAdjacentElement('afterend', errorBox);
        }
    </script>
</body>
</html>