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

// Validate session
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['student', 'teacher', 'admin'])) {
    header("Location: login.php");
    exit();
}

// Check database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// CSRF token generation (already present, but verified)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Strengthen Role-Based Access Control for Students (add user ID check)
if ($_SESSION['role'] == 'student') {
    $stmt = $conn->prepare("SELECT id FROM students WHERE roll_number = ?");
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    if (!$student) {
        header("Location: login.php");
        exit();
    }
    $_SESSION['student_id'] = $student['id']; // Store for later use
}

// Fix 1: Centralize Grade Calculation Logic
function calculateGrade($marks) {
    if ($marks >= 45) return 'A+';
    if ($marks >= 40) return 'A';
    if ($marks >= 35) return 'B+';
    if ($marks >= 30) return 'B';
    if ($marks >= 25) return 'C';
    return 'FAIL';
}

// Handle PDF download for student using FPDF
if (isset($_POST['download_pdf']) && $_SESSION['role'] == 'student') {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }

    $stmt = $conn->prepare("SELECT r.exam_name, r.subject, r.marks FROM results r JOIN students s ON r.student_id = s.id WHERE s.roll_number = ? AND s.id = ?");
    if ($stmt === false) die("PDF query prepare failed: " . $conn->error);
    $stmt->bind_param("si", $_SESSION['username'], $_SESSION['student_id']);
    $stmt->execute();
    $results = $stmt->get_result();

    require('fpdf.php'); // Ensure FPDF is included
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Student Result', 0, 1, 'C');
    $pdf->Ln(10);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(60, 10, 'Exam Name', 1);
    $pdf->Cell(60, 10, 'Subject', 1);
    $pdf->Cell(30, 10, 'Marks', 1);
    $pdf->Cell(30, 10, 'Grade', 1);
    $pdf->Ln();

    $pdf->SetFont('Arial', '', 12);
    if ($results->num_rows > 0) {
        while ($row = $results->fetch_assoc()) {
            $grade = calculateGrade($row['marks']); // Use centralized function
            $pdf->Cell(60, 10, $row['exam_name'], 1);
            $pdf->Cell(60, 10, $row['subject'], 1);
            $pdf->Cell(30, 10, $row['marks'], 1);
            $pdf->Cell(30, 10, $grade, 1);
            $pdf->Ln();
        }
    } else {
        $pdf->Cell(180, 10, 'No results found', 1, 1, 'C');
    }

    logAction($conn, "Downloaded PDF");
    $pdf->Output('D', 'result.pdf');
    exit();
}

// Fix 2: Apply Filters to Excel Download
if (isset($_POST['download_excel']) && in_array($_SESSION['role'], ['admin', 'teacher'])) {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }

    // Use the same filters as the displayed table
    $where = "WHERE 1=1";
    $params = [];
    $types = "";
    if (!empty($_POST['class'])) {
        $where .= " AND s.class = ?";
        $params[] = $_POST['class'];
        $types .= "s";
    }
    if (!empty($_POST['roll_number'])) {
        $where .= " AND s.roll_number LIKE ?";
        $params[] = "%" . $_POST['roll_number'] . "%";
        $types .= "s";
    }

    $sql = "SELECT s.roll_number, s.name, s.class, r.exam_name, r.subject, r.marks FROM results r JOIN students s ON r.student_id = s.id $where";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) die("Excel query prepare failed: " . $conn->error);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $results = $stmt->get_result();

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="results.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Roll Number', 'Name', 'Class', 'Exam Name', 'Subject', 'Marks', 'Grade']);

    if ($results->num_rows > 0) {
        while ($row = $results->fetch_assoc()) {
            $grade = calculateGrade($row['marks']); // Use centralized function
            fputcsv($output, [$row['roll_number'], $row['name'], $row['class'], $row['exam_name'], $row['subject'], $row['marks'], $grade]);
        }
    } else {
        fputcsv($output, ['No results found']);
    }
    fclose($output);
    logAction($conn, "Downloaded Excel");
    exit();
}

// CSRF validation for form submission (already present, but verified)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token'])) {
    die("Invalid CSRF token");
}

// Debug session state - moved after PDF/Excel checks to avoid output
if (!isset($_POST['download_pdf']) && !isset($_POST['download_excel'])) {
    echo "<!-- Session: role=" . (isset($_SESSION['role']) ? $_SESSION['role'] : 'not set') . ", username=" . (isset($_SESSION['username']) ? $_SESSION['username'] : 'not set') . " -->";
}

// Filtering logic for table display
$where = "";
$params = [];
$types = "";

if ($_SESSION['role'] == 'student') {
    $where = "WHERE s.roll_number = ? AND s.id = ?";
    $params = [$_SESSION['username'], $_SESSION['student_id']];
    $types = "si";
} elseif (in_array($_SESSION['role'], ['admin', 'teacher']) && (isset($_POST['class']) || isset($_POST['roll_number']))) {
    $where = "WHERE 1=1";
    if (!empty($_POST['class'])) {
        $where .= " AND s.class = ?";
        $params[] = $_POST['class'];
        $types .= "s";
    }
    if (!empty($_POST['roll_number'])) {
        $where .= " AND s.roll_number LIKE ?";
        $params[] = "%" . $_POST['roll_number'] . "%";
        $types .= "s";
    }
}

$sql = "SELECT s.roll_number, s.name, s.class, r.exam_name, r.subject, r.marks FROM results r JOIN students s ON r.student_id = s.id $where";
$stmt = $conn->prepare($sql);
if ($stmt === false) die("Main query prepare failed: " . $conn->error . "<br>SQL: " . $sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$results = $stmt->get_result();

// Cache classes
if (!isset($_SESSION['classes'])) {
    $classes_result = $conn->query("SELECT DISTINCT class FROM students");
    if ($classes_result === false) die("Classes query failed: " . $conn->error);
    $_SESSION['classes'] = [];
    while ($class = $classes_result->fetch_assoc()) {
        $_SESSION['classes'][] = $class['class'];
    }
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
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Results</title>
    
</head>
<body>
    <div class="loading">Loading...</div>
    <h2>Results</h2>
    <?php if (in_array($_SESSION['role'], ['admin', 'teacher'])) { ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <select name="class">
                <option value="">All Classes</option>
                <?php foreach ($_SESSION['classes'] as $class) { ?>
                    <option value="<?php echo htmlspecialchars($class); ?>" <?php echo isset($_POST['class']) && $_POST['class'] == $class ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($class); ?>
                    </option>
                <?php } ?>
            </select>
            <input type="text" name="roll_number" placeholder="Search Roll Number" value="<?php echo isset($_POST['roll_number']) ? htmlspecialchars($_POST['roll_number']) : ''; ?>">
            <button type="submit">Filter</button>
        </form>
    <?php } ?>

    <?php if ($results->num_rows > 0) { ?>
        <table border="1">
            <tr>
                <?php if (in_array($_SESSION['role'], ['admin', 'teacher'])) { ?>
                    <th class="sortable">Roll Number</th><th class="sortable">Name</th><th class="sortable">Class</th>
                <?php } ?>
                <th class="sortable">Exam Name</th><th class="sortable">Subject</th><th class="sortable">Marks</th><th class="sortable">Grade</th>
            </tr>
            <?php while ($row = $results->fetch_assoc()) { ?>
                <tr>
                    <?php if (in_array($_SESSION['role'], ['admin', 'teacher'])) { ?>
                        <td><?php echo htmlspecialchars($row['roll_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['class']); ?></td>
                    <?php } ?>
                    <td><?php echo htmlspecialchars($row['exam_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                    <td><?php echo htmlspecialchars($row['marks']); ?></td>
                    <td><?php echo htmlspecialchars(calculateGrade($row['marks'])); // Use centralized function ?></td>
                </tr>
            <?php } ?>
        </table>
    <?php } else { ?>
        <div class="no-results">No results found</div>
    <?php } ?>

    <?php if ($_SESSION['role'] == 'student') { ?>
        <br>
        <form method="POST">
            <input type="hidden" name="download_pdf" value="1">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <button type="submit">Download as PDF</button>
        </form>
    <?php } elseif (in_array($_SESSION['role'], ['admin', 'teacher'])) { ?>
        <br>
        <form method="POST">
            <input type="hidden" name="download_excel" value="1">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <button type="submit">Download as Excel (CSV)</button>
        </form>
    <?php } ?>
    <br><a href="<?php echo htmlspecialchars($_SESSION['role'] == 'admin' ? 'admin_dashboard.php' : ($_SESSION['role'] == 'teacher' ? 'teacher_dashboard.php' : 'student_dashboard.php')); ?>">Back</a>

    <script>
        // Loading indicator
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                document.querySelector('.loading').style.display = 'block';
            });
        });

        // Table sorting
        function sortTable(n) {
            const table = document.querySelector('table');
            let rows, switching = true, i, shouldSwitch, dir = "asc", switchcount = 0;
            while (switching) {
                switching = false;
                rows = table.rows;
                for (i = 1; i < (rows.length - 1); i++) {
                    shouldSwitch = false;
                    const x = rows[i].getElementsByTagName("TD")[n];
                    const y = rows[i + 1].getElementsByTagName("TD")[n];
                    let cmpX = isNaN(parseInt(x.innerHTML)) ? x.innerHTML.toLowerCase() : parseInt(x.innerHTML);
                    let cmpY = isNaN(parseInt(y.innerHTML)) ? y.innerHTML.toLowerCase() : parseInt(y.innerHTML);
                    if (dir == "asc" && cmpX > cmpY) {
                        shouldSwitch = true;
                        break;
                    } else if (dir == "desc" && cmpX < cmpY) {
                        shouldSwitch = true;
                        break;
                    }
                }
                if (shouldSwitch) {
                    rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                    switching = true;
                    switchcount++;
                } else if (switchcount == 0 && dir == "asc") {
                    dir = "desc";
                    switching = true;
                }
            }
        }

        document.querySelectorAll('.sortable').forEach((th, index) => {
            th.addEventListener('click', () => sortTable(index));
        });
    </script>
    <?php include 'footer.php'; ?>

</body>
</html>
