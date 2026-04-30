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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Epilogue:wght@400;500;600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-base: #050505;
            --accent-1: #6E10FF;
            --accent-2: #FF3366;
            --text-main: #FFFFFF;
            --text-muted: #888888;
            --border-subtle: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg-base);
            color: var(--text-main);
            font-family: 'Epilogue', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
            padding: 20px;
        }

        /* Ambient Background Elements */
        .ambient-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(120px);
            z-index: 0;
            animation: pulse 10s ease-in-out infinite alternate;
        }
        .orb-1 {
            width: 400px;
            height: 400px;
            background: rgba(110, 16, 255, 0.25);
            bottom: -10%;
            left: -10%;
        }
        .orb-2 {
            width: 500px;
            height: 500px;
            background: rgba(255, 51, 102, 0.15);
            top: -20%;
            right: -10%;
            animation-delay: -5s;
        }

        /* Grid Pattern Overlay */
        .bg-grid {
            position: absolute;
            inset: 0;
            background-image: 
                linear-gradient(var(--border-subtle) 1px, transparent 1px),
                linear-gradient(90deg, var(--border-subtle) 1px, transparent 1px);
            background-size: 50px 50px;
            opacity: 0.3;
            z-index: 0;
            mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
            -webkit-mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
        }

        .auth-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 480px;
            padding: 48px;
            background: rgba(10, 10, 10, 0.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-subtle);
            border-radius: 24px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255,255,255,0.05);
            animation: slideUpFade 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .auth-header {
            margin-bottom: 32px;
            text-align: center;
        }

        .auth-header h2 {
            font-family: 'Syne', sans-serif;
            font-size: 2.2rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1.1;
            margin-bottom: 12px;
            background: linear-gradient(135deg, #FFF 0%, #AAA 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .auth-header p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .input-group {
            margin-bottom: 20px;
            position: relative;
        }

        .input-group label {
            display: block;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            margin-bottom: 8px;
            font-weight: 600;
        }

        .input-wrapper {
            position: relative;
        }

        input[type="text"],
        input[type="password"],
        select {
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-subtle);
            padding: 14px 16px;
            border-radius: 12px;
            color: var(--text-main);
            font-family: 'Epilogue', sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
            outline: none;
            appearance: none; /* For select */
        }

        input[type="text"]:focus,
        input[type="password"]:focus,
        select:focus {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--accent-1);
            box-shadow: 0 0 0 4px rgba(110, 16, 255, 0.15);
        }

        input::placeholder {
            color: #555;
        }
        
        select {
            cursor: pointer;
            background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23888888%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E");
            background-repeat: no-repeat;
            background-position: right 16px top 50%;
            background-size: 10px auto;
        }
        
        select option {
            background-color: var(--bg-base);
            color: var(--text-main);
        }

        button[type="submit"] {
            width: 100%;
            padding: 16px;
            margin-top: 14px;
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            color: white;
            border: none;
            border-radius: 12px;
            font-family: 'Syne', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            cursor: pointer;
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s;
            position: relative;
            overflow: hidden;
        }

        button[type="submit"]::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to right, transparent, rgba(255,255,255,0.2), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s;
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(110, 16, 255, 0.3);
        }

        button[type="submit"]:hover::after {
            transform: translateX(100%);
        }

        .auth-footer {
            margin-top: 24px;
            text-align: center;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .auth-footer a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            position: relative;
            transition: color 0.2s;
        }

        .auth-footer a::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -2px;
            width: 100%;
            height: 1px;
            background: var(--accent-1);
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.3s ease;
        }

        .auth-footer a:hover::after {
            transform: scaleX(1);
            transform-origin: left;
        }

        @keyframes slideUpFade {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.5; }
            100% { transform: scale(1.1); opacity: 0.8; }
        }
        
        .row-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        @media (max-width: 480px) {
            .auth-container {
                padding: 32px 24px;
                border-radius: 16px;
            }
            .auth-header h2 { font-size: 1.8rem; }
            .row-group { grid-template-columns: 1fr; gap: 0; }
        }
    </style>
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