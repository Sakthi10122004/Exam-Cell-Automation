<?php
session_start();
include 'header.php';

include 'db_config.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch student list
$s_list = $conn->query("SELECT s.*, u.username FROM students s LEFT JOIN users u ON s.user_id = u.id");
if ($s_list === false) {
    die("Error fetching students: " . $conn->error);
}

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        header("Location: student_list.php?message=Student deleted successfully");
        exit();
    } else {
        $error = "Error deleting student: " . $conn->error;
    }
    $stmt->close();
}

// Handle edit action
$edit_student = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT s.*, u.username FROM students s LEFT JOIN users u ON s.user_id = u.id WHERE s.id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_student = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Handle edit form submission
if (isset($_POST['update_student'])) {
    $id = $_POST['id'];
    $roll_number = $_POST['roll_number'];
    $name = $_POST['name'];
    $class = $_POST['class'];
    $department = $_POST['department'];

    $stmt = $conn->prepare("UPDATE students SET roll_number = ?, name = ?, class = ?, department = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $roll_number, $name, $class, $department, $id);
    if ($stmt->execute()) {
        header("Location: student_list.php?message=Student updated successfully");
        exit();
    } else {
        $error = "Error updating student: " . $conn->error;
    }
    $stmt->close();
}

$message = isset($_GET['message']) ? $_GET['message'] : '';
$error = isset($error) ? $error : '';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student List</title>
    
</head>
<body>
    <br><br><br><h2>Student List</h2>
    <?php if ($message) { ?>
        <div class="message success"><?php echo htmlspecialchars($message); ?></div>
    <?php } elseif ($error) { ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php } ?>

    <?php if ($edit_student) { ?>
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $edit_student['id']; ?>">
            <input type="text" name="roll_number" value="<?php echo htmlspecialchars($edit_student['roll_number']); ?>" required>
            <input type="text" name="name" value="<?php echo htmlspecialchars($edit_student['name']); ?>" required>
            <input type="text" name="class" value="<?php echo htmlspecialchars($edit_student['class']); ?>" required>
            <select name="department" required>
                <option value="Computer Science" <?php echo $edit_student['department'] === 'Computer Science' ? 'selected' : ''; ?>>Computer Science</option>
                <option value="Computer Application" <?php echo $edit_student['department'] === 'Computer Application' ? 'selected' : ''; ?>>Computer Application</option>
                <option value="Mathematics" <?php echo $edit_student['department'] === 'Mathematics' ? 'selected' : ''; ?>>Mathematics</option>
                <option value="Commerce" <?php echo $edit_student['department'] === 'Commerce' ? 'selected' : ''; ?>>Commerce</option>
                <option value="Business Administration" <?php echo $edit_student['department'] === 'Business Administration' ? 'selected' : ''; ?>>Business Administration</option>
                <option value="English" <?php echo $edit_student['department'] === 'English' ? 'selected' : ''; ?>>English</option>
            </select>
            <button type="submit" name="update_student">Update Student</button>
        </form>
    <?php } ?>

    <table><br><br><br>
        <tr>
            <th>Roll Number</th>
            <th>Name</th>
            <th>Class</th>
            <th>Department</th>
            <th>Username</th>
            <th>Actions</th>
        </tr>
        <?php while ($student = $s_list->fetch_assoc()) { ?>
            <tr>
                <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                <td><?php echo htmlspecialchars($student['name']); ?></td>
                <td><?php echo htmlspecialchars($student['class']); ?></td>
                <td><?php echo htmlspecialchars($student['department']); ?></td>
                <td><?php echo htmlspecialchars($student['username'] ?? 'N/A'); ?></td>
                <td class="action-links">
                    <a href="student_list.php?edit=<?php echo $student['id']; ?>">Edit</a>
                    <a href="student_list.php?delete=<?php echo $student['id']; ?>" onclick="return confirm('Are you sure you want to delete this student?');">Delete</a>
                </td>
            </tr>
        <?php } ?>
    </table>
    <a href="admin_dashboard.php">Back to Dashboard</a>
    <?php include 'footer.php'; ?>
</body>
</html>
