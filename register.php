<?php
session_start(); // Start session to manage CSRF token and role check
include 'db_config.php';

// Session Timeout Mechanism
$timeout_duration = 1800; // 30 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
$_SESSION['last_activity'] = time(); // Update last activity timestamp

// Role Check: Restrict registration to existing admins ONLY IF an admin already exists
$admin_check = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
if ($admin_check->num_rows > 0) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header("Location: login.php");
        exit();
    }
}

// Generate CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }

    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
    $role = $_POST['role'];
    $name = trim($_POST['name']);

    // Fix 1: Role Restriction Feedback (already restricted, but improve feedback)
    if (!in_array($role, ['admin', 'teacher'])) {
        echo "<script>alert('Invalid role selected! Only Admin or Teacher roles are allowed.'); window.history.back();</script>";
        exit();
    }

    // Fix 2: Duplicate Username Handling
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo "<script>alert('Username already exists! Please choose a different username.'); window.history.back();</script>";
        exit();
    }

    // Additional validation: Ensure username and name are not empty
    if (empty($username) || empty($name)) {
        echo "<script>alert('Username and Full Name cannot be empty!'); window.history.back();</script>";
        exit();
    }

    $sql = "INSERT INTO users (username, password, role, name) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $username, $password, $role, $name);
    if ($stmt->execute()) {
        echo "<script>alert('User registered successfully!'); window.location.href='login.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error: " . addslashes($conn->error) . "'); window.history.back();</script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Registration</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="ambient-orb orb-1"></div>
    <div class="ambient-orb orb-2"></div>
    <div class="bg-grid"></div>

    <div class="auth-container">
        <div class="auth-header">
            <h2>Join the Platform</h2>
            <p>Create your administrative profile.</p>
        </div>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="input-group">
                <label for="name">Full Name</label>
                <div class="input-wrapper">
                    <input type="text" name="name" id="name" placeholder="John Doe" required autocomplete="off">
                </div>
            </div>
            
            <div class="row-group">
                <div class="input-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <input type="text" name="username" id="username" placeholder="johndoe" required autocomplete="off">
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="role">Account Role</label>
                    <div class="input-wrapper">
                        <select name="role" id="role" required>
                            <option value="admin">Administrator</option>
                            <option value="teacher">Teacher</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="input-group">
                <label for="password">Security Key</label>
                <div class="input-wrapper">
                    <input type="password" name="password" id="password" placeholder="Create a password" required>
                </div>
            </div>
            
            <button type="submit">Initialize Account</button>
        </form>

        <div class="auth-footer">
            Already registered? <a href="login.php">Return to access</a>
        </div>
    </div>
</body>
</html>