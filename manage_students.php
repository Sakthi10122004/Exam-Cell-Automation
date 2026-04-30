<?php
session_start();
include 'header.php';

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

// Role Check
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Generate CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Add single student
if (isset($_POST['add_student'])) {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }

    $roll_number = $_POST['roll_number'];
    $name = $_POST['name'];
    $class = $_POST['class'];
    $department = $_POST['department'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check for duplicate username
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo "<script>alert('Username already exists!'); window.history.back();</script>";
        exit();
    }

    // Proceed with insertion
    $password = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, password, role, name) VALUES (?, ?, 'student', ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $password, $name);
    $stmt->execute();
    $user_id = $conn->insert_id;

    $sql = "INSERT INTO students (roll_number, name, class, department) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $roll_number, $name, $class, $department);
    $stmt->execute();

    // Set success message
    $_SESSION['success_message'] = "Student added successfully!";

    // Redirect to avoid form resubmission
    header("Location: manage_students.php");
    exit();
}

// Bulk add via CSV
if (isset($_FILES['csv_file'])) {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }

    // Validate file type and size
    $file = $_FILES['csv_file'];
    $allowed_types = ['text/csv', 'application/csv', 'text/plain'];
    $max_size = 5 * 1024 * 1024; // 5MB
    $errors = [];

    if (!in_array($file['type'], $allowed_types) || pathinfo($file['name'], PATHINFO_EXTENSION) !== 'csv') {
        $errors[] = "Invalid file type. Only CSV files are allowed.";
    }
    if ($file['size'] > $max_size) {
        $errors[] = "File size exceeds the maximum limit of 5MB.";
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "File upload error: " . $file['error'];
    }

    if (empty($errors)) {
        $handle = fopen($file['tmp_name'], "r");
        if ($handle === false) {
            $errors[] = "Failed to open the CSV file.";
        } else {
            $header = fgetcsv($handle); // Read header
            $expected_header = ['roll_number', 'name', 'class', 'department', 'username', 'password'];
            if ($header !== $expected_header) {
                $errors[] = "Invalid CSV format. Expected header: " . implode(',', $expected_header);
            } else {
                while (($data = fgetcsv($handle)) !== false) {
                    // Validate CSV row
                    if (count($data) !== 6) {
                        $errors[] = "Invalid row format: " . implode(',', $data);
                        continue;
                    }

                    $roll_number = trim($data[0]);
                    $name = trim($data[1]);
                    $class = trim($data[2]);
                    $department = trim($data[3]);
                    $username = trim($data[4]);
                    $password = trim($data[5]);

                    // Validate required fields
                    if (empty($roll_number) || empty($name) || empty($class) || empty($department) || empty($username) || empty($password)) {
                        $errors[] = "Missing required fields in row: " . implode(',', $data);
                        continue;
                    }

                    // Check for duplicate username
                    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    if ($stmt->get_result()->num_rows > 0) {
                        $errors[] = "Username '$username' already exists for student '$name'.";
                        continue;
                    }

                    $password = password_hash($password, PASSWORD_DEFAULT);

                    $sql = "INSERT INTO users (username, password, role, name) VALUES (?, ?, 'student', ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sss", $username, $password, $name);
                    $stmt->execute();

                    $sql = "INSERT INTO students (roll_number, name, class, department) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssss", $roll_number, $name, $class, $department);
                    $stmt->execute();
                }
                // Set success message
                $_SESSION['success_message'] = "Students added successfully!";
            }
            fclose($handle);
        }
    }

    // Display errors if any, then redirect
    if (!empty($errors)) {
        echo "<script>alert('" . implode("\\n", array_map('addslashes', $errors)) . "');</script>";
    }

    // Redirect to avoid form resubmission
    header("Location: manage_students.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Students</title>
    
</head>
<body>
    <h2>Manage Students</h2>
    
    <?php if (isset($_SESSION['success_message'])) {
        echo "<div class='success-message'>" . $_SESSION['success_message'] . "</div>";
        unset($_SESSION['success_message']);
    } ?>

    <h3>Add Single Student</h3>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="text" name="roll_number" placeholder="Roll Number" required>
        <input type="text" name="name" placeholder="Name" required>
        <input type="text" name="class" placeholder="Class" required>
        <select name="department" required>
            <option value="" disabled selected>Select Department</option>
            <option value="Computer Science">Computer Science</option>
            <option value="Computer Application">Computer Application</option>
            <option value="Mathematics">Mathematics</option>
            <option value="Commerce">Commerce</option>
            <option value="Business Administration">Business Administration</option>
            <option value="English">English</option>
        </select>
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="add_student">Add Student</button>
    </form>

    <h3>Bulk Add via CSV</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="file" name="csv_file" accept=".csv" required>
        <small>CSV Format: roll_number,name,class,department,username,password</small>
        <button type="submit">Upload CSV</button>
    </form>

    <a href="admin_dashboard.php" class="back-link">Back</a>
</body>
<script>
    // Prevent form submission if department is not selected
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const department = this.querySelector('select[name="department"]');
            if (department && department.value === "") {
                e.preventDefault();
                alert('Please select a department.');
                department.focus();
            }
        });
    });

    // Add a subtle animation on department selection
    document.querySelectorAll('select[name="department"]').forEach(select => {
        select.addEventListener('change', function() {
            this.style.transition = 'box-shadow 0.3s ease';
            this.style.boxShadow = '0 0 10px rgba(96, 165, 250, 0.8)';
            setTimeout(() => {
                this.style.boxShadow = '0 0 8px rgba(96, 165, 250, 0.5)';
            }, 300);
        });
    });
</script>
<?php include 'footer.php'; ?>
</html>
