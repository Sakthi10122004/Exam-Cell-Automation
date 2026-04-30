<?php
session_start();
include 'header.php';

include 'db_config.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Teacher Dashboard</title>
    
</head>
<body>
    <div class="dashboard-wrapper">
        <div class="sidebar">
            <div class="title">Teacher Portal</div>
            <ul>
                <li><a href="exam_schedule.php">View Exam Schedule</a></li>
                <li><a href="upload_question.php">Upload Question Paper</a></li>
                <li><a href="upload_results.php">Upload Results</a></li>
                <li><a href="view_results.php">View Results</a></li>
                <li><a href="notifications.php">View Notifications</a></li>
                <li><a href="feedback.php">Submit Feedback</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
        <div class="content">
            <div class="header">
                <h1>Dashboard</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo $_SESSION['username']; ?></span>
                    <div class="avatar">T</div>
                </div>
            </div>
            <div class="cards">
                <div class="card">
                    <h3>Exam Schedule</h3>
                    <p>Next scheduled exam</p>
                    <div class="stat">March 20, 2025</div>
                    <p>Subject: Physics</p>
                </div>
                <div class="card">
                    <h3>Pending Uploads</h3>
                    <p>Question papers to upload</p>
                    <div class="stat">2</div>
                    <p>Due by March 15</p>
                </div>
                <div class="card">
                    <h3>Results Status</h3>
                    <p>Last uploaded result</p>
                    <div class="stat">Uploaded</div>
                    <p>Mar 10, 2025</p>
                </div>
                <div class="card">
                    <h3>Notifications</h3>
                    <p>Unread messages</p>
                    <div class="stat">3</div>
                    <p>From Admin</p>
                </div>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>
