<?php
session_start();
include 'header.php';

include 'db_config.php';

// Validate session and role
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'student', 'teacher'])) {
    header("Location: login.php");
    exit();
}

// CSRF token generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Logging function
function logAction($conn, $action) {
    $stmt = $conn->prepare("INSERT INTO logs (user_role, username, action, timestamp) VALUES (?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("sss", $_SESSION['role'], $_SESSION['username'], $action);
        $stmt->execute();
        $stmt->close();
    }
}

// Handle adding seating arrangement (admin only)
$message = '';
if (isset($_POST['generate_seating']) && $_SESSION['role'] === 'admin' && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $exam_id = $_POST['exam_id'];
    $room_name = $_POST['room_name'];

    // Fetch exam details
    $stmt = $conn->prepare("SELECT class, department FROM exam_schedule WHERE id = ?");
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exam = $result->fetch_assoc();
    $stmt->close();

    if ($exam) {
        $class = $exam['class'];
        $department = $exam['department'];

        // Fetch students
        $stmt = $conn->prepare("SELECT id, roll_number, name FROM students WHERE class = ? AND department = ?");
        $stmt->bind_param("ss", $class, $department);
        $stmt->execute();
        $result = $stmt->get_result();
        $student_list = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (empty($student_list)) {
            $message = "Error: No students found for class '$class' and department '$department'.";
        } else {
            shuffle($student_list);

            // Group students by year
            $students_by_year = [];
            foreach ($student_list as $student) {
                preg_match('/^B(\d{2})/', $student['roll_number'], $matches);
                $year = $matches[1] ?? 'Unknown';
                $students_by_year[$year][] = $student;
            }

            // Determine room capacity and group size
            $capacity = 0;
            $group_size = 0;
            if (preg_match('/^[12]CO$/', $room_name)) {
                $capacity = 36;
                $group_size = 3;
            } elseif (preg_match('/^JH-[A-E]$/', $room_name)) {
                $capacity = 100;
                $group_size = 20;
            } elseif (preg_match('/^EH-[A-H]$/', $room_name)) {
                $capacity = 200;
                $group_size = 25;
            } else {
                $message = "Error: Invalid room name '$room_name'.";
            }

            if (!$message) {
                if (count($student_list) > $capacity) {
                    $message = "Error: Too many students (" . count($student_list) . ") for room '$room_name' (capacity: $capacity).";
                } else {
                    $seating_data = [];
                    $index = 0;
                    $group_count = ceil($capacity / $group_size);

                    for ($group = 1; $group <= $group_count; $group++) {
                        $current_group = [];
                        for ($pos = 1; $pos <= $group_size && $index < count($student_list); $pos++) {
                            $student = $student_list[$index];
                            $year = preg_match('/^B(\d{2})/', $student['roll_number'], $matches) ? $matches[1] : 'Unknown';
                            $current_group[$pos] = ['student' => $student, 'year' => $year];

                            if ($pos > 1) {
                                $prev_year = $current_group[$pos - 1]['year'];
                                if ($year === $prev_year && $pos !== 2 && !($year === '22' && $pos === 3 && $prev_year === '23' && isset($current_group[1]) && $current_group[1]['year'] === '22')) {
                                    if ($index + 1 < count($student_list)) {
                                        $next_student = $student_list[$index + 1];
                                        $next_year = preg_match('/^B(\d{2})/', $next_student['roll_number'], $matches) ? $matches[1] : 'Unknown';
                                        if ($next_year !== $prev_year) {
                                            $student_list[$index] = $next_student;
                                            $student_list[$index + 1] = $student;
                                            continue;
                                        }
                                    }
                                    $message = "Error: Seating rule violated (same year students together at position $pos in group G$group).";
                                    break 2;
                                }
                            }

                            $seating_data[] = [
                                'student_id' => $student['id'],
                                'seat_group' => "G$group",
                                'position_in_group' => $pos
                            ];
                            $index++;
                        }
                    }

                    if (!$message && $index < count($student_list)) {
                        $message = "Error: Not all students could be seated due to rule constraints. Seated: $index, Total: " . count($student_list) . ".";
                    } elseif (!$message) {
                        $conn->begin_transaction();
                        try {
                            foreach ($seating_data as $seat) {
                                $sql = "INSERT INTO seating_arrangements (exam_id, student_id, room_name, seat_group, position_in_group) VALUES (?, ?, ?, ?, ?)";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("iissi", $exam_id, $seat['student_id'], $room_name, $seat['seat_group'], $seat['position_in_group']);
                                $stmt->execute();
                                $stmt->close();
                            }
                            $conn->commit();
                            $message = "Seating arrangement generated successfully!";
                            logAction($conn, "Generated seating arrangement for exam ID $exam_id in room $room_name");
                        } catch (Exception $e) {
                            $conn->rollback();
                            $message = "Error: Failed to generate seating arrangement. " . $e->getMessage();
                        }
                    }
                }
            }
        }
    } else {
        $message = "Error: Invalid exam ID.";
    }
}

// Handle deleting seating arrangement (admin only)
if (isset($_POST['delete_seating']) && $_SESSION['role'] === 'admin' && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $exam_id = $_POST['exam_id'];
    $room_name = $_POST['room_name'];

    $stmt = $conn->prepare("DELETE FROM seating_arrangements WHERE exam_id = ? AND room_name = ?");
    $stmt->bind_param("is", $exam_id, $room_name);
    if ($stmt->execute()) {
        $message = "Seating arrangement deleted successfully!";
        logAction($conn, "Deleted seating arrangement for exam ID $exam_id in room $room_name");
    } else {
        $message = "Error: Failed to delete seating arrangement.";
    }
    $stmt->close();
}

// Fetch exams for admin form
$exams = $conn->query("SELECT id, exam_name, subject FROM exam_schedule");

// Fetch seating arrangements based on role
if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') {
    $seatings = $conn->query("SELECT sa.*, e.exam_name, e.subject, s.roll_number, s.name 
                              FROM seating_arrangements sa 
                              JOIN exam_schedule e ON sa.exam_id = e.id 
                              JOIN students s ON sa.student_id = s.id 
                              ORDER BY sa.room_name, sa.seat_group, sa.position_in_group");
} else { // Student view
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT sa.*, e.exam_name, e.subject 
                            FROM seating_arrangements sa 
                            JOIN exam_schedule e ON sa.exam_id = e.id 
                            JOIN students s ON sa.student_id = s.id 
                            WHERE s.user_id = ? 
                            ORDER BY sa.room_name, sa.seat_group, sa.position_in_group");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $seatings = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Seating Arrangement</title>
    
</head>
<body>
    <h2>Seating Arrangement</h2>
    <?php if ($message) { ?>
        <div class="message <?php echo strpos($message, 'Error') === false ? 'success' : 'error'; ?>">
            <?php echo nl2br(htmlspecialchars($message)); ?>
        </div>
    <?php } ?>
    <?php if ($_SESSION['role'] === 'admin') { ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <select name="exam_id" required>
                <option value="">Select Exam</option>
                <?php 
                $exams->data_seek(0); // Reset pointer
                while ($exam = $exams->fetch_assoc()) { ?>
                    <option value="<?php echo $exam['id']; ?>">
                        <?php echo htmlspecialchars($exam['exam_name'] . " - " . $exam['subject']); ?>
                    </option>
                <?php } ?>
            </select>
            <select name="room_name" required>
                <option value="">Select Room</option>
                <optgroup label="Classrooms">
                    <option value="1CO">1CO</option>
                    <option value="2CO">2CO</option>
                </optgroup>
                <optgroup label="Jubilee Hall">
                    <?php for ($i = 'A'; $i <= 'E'; $i++) { ?>
                        <option value="JH-<?php echo $i; ?>">JH-<?php echo $i; ?></option>
                    <?php } ?>
                </optgroup>
                <optgroup label="Examination Hall">
                    <?php for ($i = 'A'; $i <= 'H'; $i++) { ?>
                        <option value="EH-<?php echo $i; ?>">EH-<?php echo $i; ?></option>
                    <?php } ?>
                </optgroup>
            </select>
            <button type="submit" name="generate_seating">Generate Seating</button>
            <button type="submit" name="delete_seating" class="delete-btn" onclick="return confirm('Are you sure you want to delete this seating arrangement?');">Delete Seating</button>
        </form>
    <?php } ?>
    <h3><?php echo $_SESSION['role'] === 'student' ? 'Your Seating Arrangements' : 'Seating Arrangements'; ?></h3>
    <table border="1">
        <tr>
            <th>Exam Name</th>
            <th>Subject</th>
            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') { ?>
                <th>Roll Number</th>
                <th>Student Name</th>
            <?php } ?>
            <th>Room</th>
            <th>Seat Group</th>
            <th>Position</th>
        </tr>
        <?php while ($row = $seatings->fetch_assoc()) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['exam_name']); ?></td>
                <td><?php echo htmlspecialchars($row['subject']); ?></td>
                <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') { ?>
                    <td><?php echo htmlspecialchars($row['roll_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                <?php } ?>
                <td><?php echo htmlspecialchars($row['room_name']); ?></td>
                <td><?php echo htmlspecialchars($row['seat_group']); ?></td>
                <td><?php echo htmlspecialchars($row['position_in_group']); ?></td>
            </tr>
        <?php } ?>
    </table>
    <a href="<?php echo $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : ($_SESSION['role'] === 'teacher' ? 'teacher_dashboard.php' : 'student_dashboard.php'); ?>">Back</a>
    <?php include 'footer.php'; ?>

</body>
</html>
