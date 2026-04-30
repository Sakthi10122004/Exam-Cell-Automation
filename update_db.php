<?php
require_once 'db_config.php';

try {
    echo "<h3>Database Upgrade Script</h3>";
    
    // Update ENUM for users table
    $sql1 = "ALTER TABLE users MODIFY COLUMN role ENUM('superadmin', 'admin', 'teacher', 'student') NOT NULL";
    if ($conn->query($sql1) === TRUE) {
        echo "<p>Users table ENUM updated to include superadmin.</p>";
    } else {
        echo "<p>Error updating users ENUM: " . $conn->error . "</p>";
    }

    // Update ENUM for logs table
    $sql2 = "ALTER TABLE logs MODIFY COLUMN user_role ENUM('superadmin', 'admin', 'teacher', 'student') NOT NULL";
    if ($conn->query($sql2) === TRUE) {
        echo "<p>Logs table ENUM updated.</p>";
    } else {
        echo "<p>Error updating logs ENUM: " . $conn->error . "</p>";
    }

    // Create site_settings table
    $sql3 = "CREATE TABLE IF NOT EXISTS `site_settings` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `setting_key` varchar(100) NOT NULL,
      `setting_value` text NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `setting_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if ($conn->query($sql3) === TRUE) {
        echo "<p>site_settings table created successfully.</p>";
    } else {
        echo "<p>Error creating site_settings: " . $conn->error . "</p>";
    }

    // Insert default navigation links if empty
    $defaultHeader = json_encode([
        ["label" => "Home", "url" => "index.php"],
        ["label" => "About Us", "url" => "about.php"],
        ["label" => "Services", "url" => "services.php"],
        ["label" => "Contact", "url" => "contact.php"]
    ]);
    
    $defaultFooter = json_encode([
        ["label" => "Facebook", "url" => "https://www.facebook.com/dbcyelagiri", "icon" => "fab fa-facebook-f"],
        ["label" => "Twitter", "url" => "https://twitter.com/dbcyelagiri1", "icon" => "fab fa-twitter"],
        ["label" => "LinkedIn", "url" => "https://in.linkedin.com/company/don-bosco-college-co-ed-yelagiri-hills", "icon" => "fab fa-linkedin-in"],
        ["label" => "Instagram", "url" => "https://www.instagram.com/dbcyelagiri", "icon" => "fab fa-instagram"]
    ]);

    $stmt = $conn->prepare("INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES ('header_links', ?), ('footer_social', ?)");
    $stmt->bind_param("ss", $defaultHeader, $defaultFooter);
    
    if ($stmt->execute()) {
        echo "<p>Default settings seeded successfully.</p>";
    } else {
        echo "<p>Error seeding settings: " . $stmt->error . "</p>";
    }
    
    echo "<p><strong>Upgrade Complete. Please delete this file.</strong></p>";

} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
