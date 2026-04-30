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
<html>
<head>
    <title>Register</title>
    <style>
        /* Reset default styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background: linear-gradient(135deg, rgba(30, 30, 47, 0.8), rgba(42, 42, 74, 0.8)), 
                        url('background.jpeg') no-repeat center center fixed;
            background-size: cover;
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            padding: 20px;
            position: relative;
        }

        /* Logo styling */
        .logo {
            width: 100px;
            height: 100px;
            margin-bottom: 20px;
            background: url('logo.png') no-repeat center center;
            background-size: contain;
            border-radius: 50%;
            box-shadow: 0 0 15px rgba(96, 165, 250, 0.5);
        }

        /* Header styling */
        h2 {
            font-size: 2.2rem;
            color: #60a5fa;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 0 0 10px rgba(96, 165, 250, 0.5);
            margin-bottom: 30px;
            text-align: center;
        }

        /* Form container */
        form {
            background: rgba(42, 42, 74, 0.9); /* Slightly transparent for background image visibility */
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 400px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            transition: transform 0.3s ease;
        }

        form:hover {
            transform: translateY(-5px);
        }

        /* Input fields */
        input[type="text"],
        input[type="password"],
        input[type="hidden"],
        select {
            width: 100%;
            padding: 12px 15px;
            border: none;
            border-radius: 8px;
            background: #3b3b5a;
            color: #ffffff;
            font-size: 1rem;
            outline: none;
            transition: background 0.3s ease, box-shadow 0.3s ease;
        }

        input[type="text"]::placeholder,
        input[type="password"]::placeholder {
            color: #b0b0c0;
        }

        input[type="text"]:focus,
        input[type="password"]:focus,
        select:focus {
            background: #454570;
            box-shadow: 0 0 8px rgba(96, 165, 250, 0.5);
        }

        select {
            appearance: none;
            background: #3b3b5a url('data:image/svg+xml;utf8,<svg fill="%23b0b0c0" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>') no-repeat right 10px center;
            cursor: pointer;
        }

        /* Submit button */
        button[type="submit"] {
            padding: 12px;
            background: #60a5fa;
            color: #1e1e2f;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        button[type="submit"]:hover {
            background: #3b82f6;
            transform: scale(1.05);
        }

        button[type="submit"]:active {
            transform: scale(0.98);
        }

        /* Back to Login link */
        a {
            margin-top: 20px;
            color: #60a5fa;
            text-decoration: none;
            font-size: 1rem;
            transition: color 0.3s ease;
        }

        a:hover {
            color: #3b82f6;
            text-decoration: underline;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .logo {
                width: 80px;
                height: 80px;
            }
            h2 {
                font-size: 1.8rem;
            }
            form {
                padding: 30px;
                max-width: 100%;
            }
            input[type="text"],
            input[type="password"],
            select,
            button[type="submit"] {
                font-size: 0.9rem;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="logo"></div>
    <h2>Register</h2>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="text" name="name" placeholder="Full Name" required>
        <select name="role" required>
            <option value="admin">Admin</option>
            <option value="teacher">Teacher</option>
        </select>
        <button type="submit">Register</button>
    </form>
    <a href="login.php">Back to Login</a>
</body>
</html>