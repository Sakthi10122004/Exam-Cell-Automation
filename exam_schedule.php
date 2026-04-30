<?php
session_start();

include 'header.php';

include 'db_config.php';

// Validate session and role
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'student', 'teacher'])) {
    header("Location: login.php");
    exit();
}

// CSRF token generation (for admin form submission)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Automatically delete expired schedules (runs on every page load)
$current_date = date('Y-m-d'); // Current date in YYYY-MM-DD format
$sql = "DELETE FROM exam_schedule WHERE exam_date < ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("s", $current_date);
    $stmt->execute();
    $stmt->close();
} else {
    // Log error if needed, but don't interrupt the page
    error_log("Failed to prepare delete statement: " . $conn->error);
}

// Handle adding schedule (admin only)
$message = '';
if (isset($_POST['add_schedule']) && $_SESSION['role'] === 'admin' && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $exam_name = $_POST['exam_name'];
    $subject = $_POST['subject'];
    $exam_date = $_POST['exam_date'];
    $class = $_POST['class'];
    $department = $_POST['department'];

    $sql = "INSERT INTO exam_schedule (exam_name, subject, exam_date, class, department) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sssss", $exam_name, $subject, $exam_date, $class, $department);
        if ($stmt->execute()) {
            $message = "Schedule added successfully!";
        } else {
            $message = "Error: Failed to add schedule.";
        }
        $stmt->close();
    } else {
        $message = "Error: Database prepare failed.";
    }
}

// Fetch schedules (viewable by all roles)
$schedules = $conn->query("SELECT * FROM exam_schedule");
if ($schedules === false) {
    die("Error fetching schedules: " . $conn->error);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Exam Schedule</title>
    
</head>
<body>
    <h2>Exam Schedule</h2>
    <?php if ($message && $_SESSION['role'] === 'admin') { ?>
        <div class="message <?php echo strpos($message, 'Error') === false ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php } ?>
    <?php if ($_SESSION['role'] === 'admin') { ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <select name="exam_name" required>
                <option value="" disabled selected>Select Exam</option>
                <option value="CIE1">CIE1</option>
                <option value="CIE2">CIE2</option>
                <option value="CIE3">CIE3</option>
            </select>
            <input type="text" name="subject" placeholder="Subject" required>
            <input type="date" name="exam_date" required>
            <select name="department" id="department" required>
                <option value="" disabled selected>Select Department</option>
                <option value="Computer Science">Computer Science</option>
                <option value="Computer Application">Computer Application</option>
                <option value="Mathematics">Mathematics</option>
                <option value="Commerce">Commerce</option>
                <option value="Business Administration">Business Administration</option>
                <option value="English">English</option>
            </select>
            <select name="class" id="class" required>
                <option value="" disabled selected>Select Class</option>
            </select>
            <button type="submit" name="add_schedule">Add Schedule</button>
        </form>
    <?php } ?>
    <h3>Schedule List</h3>
    <table border="1">
        <tr><th>Exam Name</th><th>Subject</th><th>Date</th><th>Class</th><th>Department</th></tr>
        <?php while ($row = $schedules->fetch_assoc()) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['exam_name']); ?></td>
                <td><?php echo htmlspecialchars($row['subject']); ?></td>
                <td><?php echo htmlspecialchars($row['exam_date']); ?></td>
                <td><?php echo htmlspecialchars($row['class']); ?></td>
                <td><?php echo htmlspecialchars($row['department']); ?></td>
            </tr>
        <?php } ?>
    </table>
    <a href="<?php echo $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : ($_SESSION['role'] === 'teacher' ? 'teacher_dashboard.php' : 'student_dashboard.php'); ?>">Back</a>

    <script>
        const departmentSelect = document.getElementById('department');
        const classSelect = document.getElementById('class');

        const classOptions = {
            'Computer Science': ['1CS', '2CS', '3CS'],
            'Computer Application': ['1CA', '2CA', '3CA'],
            'Mathematics': ['1MATH', '2MATH', '3MATH'],
            'Commerce': ['1COM', '2COM', '3COM'],
            'Business Administration': ['1BBA', '2BBA', '3BBA'],
            'English': ['1EN', '2EN', '3EN']
        };

        departmentSelect.addEventListener('change', function() {
            const selectedDept = this.value;
            classSelect.innerHTML = '<option value="" disabled selected>Select Class</option>';

            if (classOptions[selectedDept]) {
                classOptions[selectedDept].forEach(className => {
                    const option = document.createElement('option');
                    option.value = className;
                    option.textContent = className;
                    classSelect.appendChild(option);
                });
            }
        });

        // Prevent form submission if dropdowns are not selected
        document.querySelector('form').addEventListener('submit', function(e) {
            const examName = document.querySelector('select[name="exam_name"]').value;
            const department = document.querySelector('select[name="department"]').value;
            const classField = document.querySelector('select[name="class"]').value;

            if (!examName || !department || !classField) {
                e.preventDefault();
                alert('Please select an Exam Name, Department, and Class.');
            }
        });
    </script>
    <?php include 'footer.php'; ?>

</body>
</html>
