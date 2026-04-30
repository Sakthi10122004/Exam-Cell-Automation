<?php
$host = 'localhost';
$db = 'exam_cell_automation';
$user = 'root';
$pass = '';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($host, $user, $pass, $db);
} catch (mysqli_sql_exception $e) {
    die("<h3>Database Connection Failed!</h3><p>" . $e->getMessage() . "</p><p><b>Please update db_config.php with your InfinityFree MySQL credentials!</b></p>");
}
?>