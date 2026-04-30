<?php
session_start();

include 'header.php';


include 'db_config.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch the total number of registered students
$stmt = $conn->prepare("SELECT COUNT(*) as total_students FROM students");
$stmt->execute();
$result = $stmt->get_result();
$total_students = $result->fetch_assoc()['total_students'];
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    
</head>
<body>
    <div class="dashboard-wrapper">
        <div class="sidebar">
            <h2>Welcome, Admin</h2>
            <div class="link-container">
                <a href="manage_students.php">Manage Students</a>
                <a href="student_list.php">Student List</a>
                <a href="seating_arrangement.php">Generate Seating</a>
                <a href="exam_schedule.php">Create/View Exam Schedule</a>
                <a href="upload_question.php">Upload/Manage Question Papers</a>
                <a href="upload_results.php">Upload Results</a>
                <a href="view_results.php">View Results</a>
                <a href="feedback.php">View Feedback</a>
                <a href="notifications.php">Send Notifications</a>
                <a href="reports.php">Result Reports</a>
            </div>
        </div>

        <div class="main-content">
            <div class="card">
                <h3>Student Overview</h3>
                <p>Total Registered Students</p>
                <div class="stat"><?php echo htmlspecialchars($total_students); ?></div>
            </div>
            <div class="card">
                <h3>Exam Status</h3>
                <p>Upcoming Exams This Month</p>
                <div class="stat">8</div>
            </div>
            <div class="card">
                <h3>Pending Tasks</h3>
                <p>Results to Upload</p>
                <div class="stat">3</div>
            </div>
            <div class="card">
                <h3>Feedback Summary</h3>
                <p>New Feedback Received</p>
                <div class="stat">15</div>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>
