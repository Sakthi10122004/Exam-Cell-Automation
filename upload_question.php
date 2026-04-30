<?php
session_start();
include 'header.php';

include 'db_config.php';

// Validate session and role
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header("Location: login.php");
    exit();
}

// CSRF token generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle file upload
$message = '';
if (isset($_POST['upload_paper']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $exam_id = $_POST['exam_id'];
    $uploaded_by = $_SESSION['user_id']; // Assuming user_id is set during login
    $file = $_FILES['question_paper']['name'];
    $target = "uploads/" . basename($file);
    $fileType = strtolower(pathinfo($target, PATHINFO_EXTENSION));
    
    // Validate file type (e.g., allow only PDF)
    $allowedTypes = ['pdf'];
    if (!in_array($fileType, $allowedTypes)) {
        $message = "Error: Only PDF files are allowed.";
    } elseif (move_uploaded_file($_FILES['question_paper']['tmp_name'], $target)) {
        $sql = "INSERT INTO question_papers (exam_id, file_path, uploaded_by) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("isi", $exam_id, $target, $uploaded_by);
            if ($stmt->execute()) {
                $message = "Question paper uploaded successfully!";
            } else {
                $message = "Error: Failed to save to database.";
                unlink($target); // Remove file if DB insert fails
            }
            $stmt->close();
        } else {
            $message = "Error: Database prepare failed.";
            unlink($target);
        }
    } else {
        $message = "Error: Failed to upload file.";
    }
}

// Delete question paper (admin only)
if (isset($_GET['delete']) && $_SESSION['role'] == 'admin') {
    $id = (int)$_GET['delete'];
    $sql = "SELECT file_path FROM question_papers WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        if ($result && file_exists($result['file_path'])) {
            unlink($result['file_path']); // Delete file from server
        }
        
        $sql = "DELETE FROM question_papers WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $message = "Question paper deleted successfully!";
            $stmt->close();
        } else {
            $message = "Error: Failed to delete from database.";
        }
    } else {
        $message = "Error: Failed to retrieve file path.";
    }
}

// Fetch exams and papers
$exams = $conn->query("SELECT * FROM exam_schedule");
if ($exams === false) die("Error fetching exams: " . $conn->error);

$papers = $conn->query("SELECT qp.*, e.exam_name, e.subject, u.name 
                        FROM question_papers qp 
                        JOIN exam_schedule e ON qp.exam_id = e.id 
                        JOIN users u ON qp.uploaded_by = u.id");
if ($papers === false) die("Error fetching papers: " . $conn->error);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Question Paper</title>
    
</head>
<body>
    <h2>Upload Question Paper</h2>
    <?php if ($message) { ?>
        <div class="message <?php echo strpos($message, 'Error') === false ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php } ?>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <select name="exam_id" required>
            <option value="">Select Exam</option>
            <?php while ($exam = $exams->fetch_assoc()) { ?>
                <option value="<?php echo $exam['id']; ?>">
                    <?php echo htmlspecialchars($exam['exam_name'] . " - " . $exam['subject']); ?>
                </option>
            <?php } ?>
        </select>
        <input type="file" name="question_paper" accept=".pdf" required>
        <button type="submit" name="upload_paper">Upload</button>
    </form>
    <?php if ($_SESSION['role'] == 'admin') { ?>
        <h3>Manage Question Papers</h3>
        <table border="1">
            <tr><th>Exam Name</th><th>Subject</th><th>File</th><th>Uploaded By</th><th>Action</th></tr>
            <?php while ($paper = $papers->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($paper['exam_name']); ?></td>
                    <td><?php echo htmlspecialchars($paper['subject']); ?></td>
                    <td><a href="<?php echo htmlspecialchars($paper['file_path']); ?>" download>Download</a></td>
                    <td><?php echo htmlspecialchars($paper['name']); ?></td>
                    <td><a href="?delete=<?php echo $paper['id']; ?>" onclick="return confirm('Are you sure you want to delete this question paper?');">Delete</a></td>
                </tr>
            <?php } ?>
        </table>
    <?php } ?>
    <a href="<?php echo $_SESSION['role'] == 'admin' ? 'admin_dashboard.php' : 'teacher_dashboard.php'; ?>" class="back-link">Back</a>
    <?php include 'footer.php'; ?>
</body>
</html>
