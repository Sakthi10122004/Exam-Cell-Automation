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

// Role Check (strengthened for consistency and security)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'teacher', 'student'])) {
    header("Location: login.php");
    exit();
}

// Generate CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle sending notifications (admin only)
if (isset($_POST['send_notification']) && $_SESSION['role'] == 'admin') {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }

    $message = trim($_POST['message']);
    $sent_by = $_SESSION['user_id'];

    // Fix 1: Message Length Validation (assuming VARCHAR(255) in the database)
    $max_message_length = 255;
    if (strlen($message) > $max_message_length) {
        echo "<script>alert('Message is too long! Maximum length is $max_message_length characters.'); window.history.back();</script>";
        exit();
    }
    if (empty($message)) {
        echo "<script>alert('Message cannot be empty!'); window.history.back();</script>";
        exit();
    }

    $sql = "INSERT INTO notifications (message, sent_by) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $message, $sent_by);
    $stmt->execute();

    // Redirect to avoid form resubmission
    echo "<script>alert('Notification sent successfully!'); window.location.href='notifications.php';</script>";
    exit();
}

// Fetch notifications (fix 2: use LEFT JOIN to handle missing sender records)
$notifications = $conn->query("SELECT n.message, n.sent_at, u.name FROM notifications n LEFT JOIN users u ON n.sent_by = u.id ORDER BY n.sent_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Notifications</title>
    
</head>
<body>
    <?php if ($_SESSION['role'] == 'admin') { ?>
        <h2>Send Notification</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <textarea name="message" placeholder="Notification Message" required></textarea>
            <button type="submit" name="send_notification">Send</button>
        </form>
    <?php } ?>
    <h2>Notifications</h2>
    <table border="1">
        <tr><th>Message</th><th>Sent By</th><th>Date</th></tr>
        <?php while ($notif = $notifications->fetch_assoc()) { ?>
            <tr>
                <td><?php echo htmlspecialchars($notif['message']); ?></td>
                <td><?php echo htmlspecialchars($notif['name'] ?? 'Unknown Sender'); ?></td>
                <td><?php echo htmlspecialchars($notif['sent_at']); ?></td>
            </tr>
        <?php } ?>
    </table>
    <a href="<?php echo htmlspecialchars($_SESSION['role'] == 'admin' ? 'admin_dashboard.php' : ($_SESSION['role'] == 'teacher' ? 'teacher_dashboard.php' : 'student_dashboard.php')); ?>">Back</a>
    <?php include 'footer.php'; ?>

</body>
</html>
