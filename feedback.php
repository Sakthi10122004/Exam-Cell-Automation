<?php
session_start();


include 'db_config.php';
if (!isset($_SESSION['role'])) header("Location: login.php");

if (isset($_POST['submit_feedback']) && in_array($_SESSION['role'], ['teacher', 'student'])) {
    $message = $_POST['message'];
    $user_id = $_SESSION['user_id'];
    
    $sql = "INSERT INTO feedback (user_id, message) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $message);
    $stmt->execute();
}

$feedbacks = $conn->query("SELECT f.message, f.submitted_at, u.name, u.role FROM feedback f JOIN users u ON f.user_id = u.id ORDER BY f.submitted_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Feedback</title>
    
</head>
<body>
    <?php if (in_array($_SESSION['role'], ['teacher', 'student'])) { ?>
        <h2>Submit Feedback</h2>
        <form method="POST">
            <textarea name="message" placeholder="Your Feedback" required></textarea>
            <button type="submit" name="submit_feedback">Submit</button>
        </form>
    <?php } ?>
    <?php if ($_SESSION['role'] == 'admin') { ?>
        <h2>View Feedback</h2>
        <table border="1">
            <tr><th>Message</th><th>Submitted By</th><th>Role</th><th>Date</th></tr>
            <?php while ($feedback = $feedbacks->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $feedback['message']; ?></td>
                    <td><?php echo $feedback['name']; ?></td>
                    <td><?php echo $feedback['role']; ?></td>
                    <td><?php echo $feedback['submitted_at']; ?></td>
                </tr>
            <?php } ?>
        </table>
    <?php } ?>
    <a href="<?php echo $_SESSION['role'] == 'admin' ? 'admin_dashboard.php' : ($_SESSION['role'] == 'teacher' ? 'teacher_dashboard.php' : 'student_dashboard.php'); ?>">Back</a>
    <?php include 'footer.php'; ?>
</body>
</html>

