<?php
require 'db_config.php';

echo "<h2>Database Setup</h2>";

$sql_file = 'db.sql';

if (!file_exists($sql_file)) {
    die("Error: db.sql file not found.");
}

// Read the SQL file
$sql = file_get_contents($sql_file);

try {
    if ($conn->multi_query($sql)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
        
        echo "<p style='color: green;'>Database tables created successfully from db.sql!</p>";
        echo "<p>Please delete this setup_database.php file for security reasons.</p>";
    } else {
        echo "<p style='color: red;'>Error creating tables: " . $conn->error . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>SQL Error occurred: " . $e->getMessage() . "</p>";
}

$conn->close();
?>
