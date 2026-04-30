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

// Role Check (already present, but verified for consistency)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header("Location: login.php");
    exit();
}

// Generate CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle form submission for uploading marks
if (isset($_POST['upload_result'])) {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }

    $exam_id = $_POST['exam_id'];
    $subject = $_POST['subject'];
    $class = $_POST['class'];

    // Fetch exam_name from exam_schedule based on exam_id (fix 1: improve error handling)
    $stmt = $conn->prepare("SELECT exam_name FROM exam_schedule WHERE id = ?");
    if (!$stmt) {
        echo "<script>alert('Error preparing exam fetch statement: " . addslashes($conn->error) . "'); window.location.href='upload_results.php';</script>";
        exit();
    }
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $exam = $stmt->get_result()->fetch_assoc();
    if (!$exam) {
        echo "<script>alert('No exam found for the selected exam ID. Please select a valid exam.'); window.location.href='upload_results.php';</script>";
        exit();
    }
    $exam_name = $exam['exam_name'];

    // Fetch students for the selected class to validate marks (fix 2: ensure all students have marks)
    $stmt = $conn->prepare("SELECT id, name, roll_number FROM students WHERE class = ?");
    $stmt->bind_param("s", $class);
    $stmt->execute();
    $students = $stmt->get_result();

    $missing_marks = [];
    while ($student = $students->fetch_assoc()) {
        $student_id = $student['id'];
        if (!isset($_POST['marks'][$student_id]) || trim($_POST['marks'][$student_id]) === '') {
            $missing_marks[] = "Student: " . htmlspecialchars($student['name']) . " (Roll Number: " . htmlspecialchars($student['roll_number']) . ")";
        }
    }

    if (!empty($missing_marks)) {
        echo "<script>alert('Marks are missing for the following students:\\n" . implode("\\n", $missing_marks) . "\\nPlease enter marks for all students.'); window.history.back();</script>";
        exit();
    }

    // Loop through the marks for each student
    foreach ($_POST['marks'] as $student_id => $marks) {
        if (is_numeric($marks) && $marks >= 0 && $marks <= 50) {
            // Insert or update marks for each student
            $sql = "INSERT INTO results (student_id, exam_name, subject, marks) 
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE marks = VALUES(marks)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                echo "<script>alert('Error preparing statement: " . addslashes($conn->error) . "'); window.history.back();</script>";
                exit();
            }
            $stmt->bind_param("issi", $student_id, $exam_name, $subject, $marks);
            $stmt->execute();
        } else {
            echo "<script>alert('Invalid marks for student ID $student_id. Marks must be a number between 0 and 50.'); window.history.back();</script>";
            exit();
        }
    }
    echo "<script>alert('Marks uploaded successfully!'); window.location.href = '" . ($_SESSION['role'] == 'admin' ? 'admin_dashboard.php' : 'teacher_dashboard.php') . "';</script>";
    exit(); // Ensure no further code is executed after redirect
}

// Fetch exams and classes
$exams = $conn->query("SELECT * FROM exam_schedule");
$classes = $conn->query("SELECT DISTINCT class FROM exam_schedule");

// Fetch students based on selected class
$students = null;
if (isset($_POST['class']) && !empty($_POST['class'])) {
    $stmt = $conn->prepare("SELECT * FROM students WHERE class = ?");
    $stmt->bind_param("s", $_POST['class']);
    $stmt->execute();
    $students = $stmt->get_result();
}

// Fetch subjects based on selected exam
$subjects = null;
if (isset($_POST['exam_id']) && !empty($_POST['exam_id'])) {
    $stmt = $conn->prepare("SELECT DISTINCT subject FROM exam_schedule WHERE id = ?");
    $stmt->bind_param("i", $_POST['exam_id']);
    $stmt->execute();
    $subjects = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Results</title>
    
    <script>
        function updateSubjects() {
            document.getElementById("filterForm").submit();
        }
    </script>
</head>
<body>
    <h2>Upload Results</h2>
    <form method="POST" id="filterForm">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <!-- Exam Selection -->
        <label for="exam_id">Select Exam:</label>
        <select name="exam_id" id="exam_id" required onchange="updateSubjects()">
            <option value="">Select Exam</option>
            <?php while ($exam = $exams->fetch_assoc()) { ?>
                <option value="<?php echo htmlspecialchars($exam['id']); ?>" <?php echo isset($_POST['exam_id']) && $_POST['exam_id'] == $exam['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($exam['exam_name']); ?>
                </option>
            <?php } ?>
        </select>

        <!-- Subject Selection -->
        <?php if ($subjects && $subjects->num_rows > 0) { ?>
            <label for="subject">Select Subject:</label>
            <select name="subject" id="subject" required>
                <option value="">Select Subject</option>
                <?php while ($subject = $subjects->fetch_assoc()) { ?>
                    <option value="<?php echo htmlspecialchars($subject['subject']); ?>" <?php echo isset($_POST['subject']) && $_POST['subject'] == $subject['subject'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($subject['subject']); ?>
                    </option>
                <?php } ?>
            </select>
        <?php } else { ?>
            <p>No subjects found for the selected exam.</p>
        <?php } ?>

        <!-- Class Selection -->
        <label for="class">Select Class:</label>
        <select name="class" id="class" required onchange="this.form.submit()">
            <option value="">Select Class</option>
            <?php while ($class = $classes->fetch_assoc()) { ?>
                <option value="<?php echo htmlspecialchars($class['class']); ?>" <?php echo isset($_POST['class']) && $_POST['class'] == $class['class'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($class['class']); ?>
                </option>
            <?php } ?>
        </select>

        <!-- Display Students and Marks Input -->
        <?php if ($students && $students->num_rows > 0 && isset($_POST['class']) && isset($_POST['subject'])) { ?>
            <h3>Students in Class: <?php echo htmlspecialchars($_POST['class']); ?></h3>
            <table border="1">
                <thead>
                    <tr>
                        <th>Roll Number</th>
                        <th>Student Name</th>
                        <th>Marks (Max: 50)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($student = $students->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td>
                            <input type="number" name="marks[<?php echo htmlspecialchars($student['id']); ?>]" placeholder="Enter Marks" min="0" max="50" required oninput="if (this.value > 50) this.value = 50">
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <button type="submit" name="upload_result">Upload Marks</button>
        <?php } elseif (isset($_POST['class']) && isset($_POST['subject'])) { ?>
            <p>No students found for the selected class.</p>
        <?php } ?>
    </form>
    <br>
    <a href="<?php echo htmlspecialchars($_SESSION['role'] == 'admin' ? 'admin_dashboard.php' : 'teacher_dashboard.php'); ?>">Back</a>
    <?php include 'footer.php'; ?>

</body>
</html>
