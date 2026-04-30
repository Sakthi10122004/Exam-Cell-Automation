<?php
session_start(); // Start session to manage CSRF token and role check
include 'db_config.php';

// Session Timeout Mechanism
$timeout_duration = 1800; // 30 minutes in seconds
// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if ANY superadmins or admins exist
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role IN ('admin', 'superadmin')");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$adminCount = $row['count'];
$stmt->close();

$isFirstUser = ($adminCount == 0);

if (!$isFirstUser) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
        header("Location: login.php");
        exit();
    }
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

    if ($isFirstUser) {
        $role = 'superadmin';
    } else {
        $role = $_POST['role'];
        if (!in_array($role, ['admin', 'teacher'])) {
            echo "<script>alert('Invalid role selected! Only Admin or Teacher roles are allowed.'); window.history.back();</script>";
            exit();
        }
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
                
                <?php if (!$isFirstUser): ?>
                <div class="input-group">
                    <label for="role">Account Role</label>
                    <div class="input-wrapper">
                        <select name="role" id="role" required>
                            <option value="admin">Administrator</option>
                            <option value="teacher">Teacher</option>
                        </select>
                    </div>
                </div>
                <?php else: ?>
                <input type="hidden" name="role" value="superadmin">
                <div class="input-group">
                    <label for="role">Account Role</label>
                    <div class="input-wrapper">
                        <input type="text" value="Super Administrator (Initial Setup)" disabled>
                    </div>
                </div>
                <?php endif; ?>
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