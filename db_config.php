<?php
$host = 'sql301.infinityfree.com';
$db = 'if0_41140025_exam_cell_automation';
$user = 'if0_41140025';
$pass = 'Sakthi10122004';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($host, $user, $pass, $db);
} catch (mysqli_sql_exception $e) {
    die("<h3>Database Connection Failed!</h3><p>" . $e->getMessage() . "</p><p><b>Please update db_config.php with your InfinityFree MySQL credentials!</b></p>");
}
?>