<?php
$currentYear = date("Y"); // Get current year dynamically

// Ensure db_config is included safely if not already
if (!isset($conn)) {
    @include_once 'db_config.php';
}

$footerLinks = [
    ["label" => "Facebook", "url" => "https://www.facebook.com/dbcyelagiri", "icon" => "fab fa-facebook-f"],
    ["label" => "Twitter", "url" => "https://twitter.com/dbcyelagiri1", "icon" => "fab fa-twitter"],
    ["label" => "LinkedIn", "url" => "https://in.linkedin.com/company/don-bosco-college-co-ed-yelagiri-hills", "icon" => "fab fa-linkedin-in"],
    ["label" => "Instagram", "url" => "https://www.instagram.com/dbcyelagiri", "icon" => "fab fa-instagram"]
];

$footerNavLinks = [
    ["label" => "Home", "url" => "index.php"],
    ["label" => "About", "url" => "about.php"],
    ["label" => "Services", "url" => "services.php"],
    ["label" => "Contact", "url" => "contact.php"]
];

if (isset($conn) && $conn) {
    // Fetch Social Links
    $result = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key = 'footer_social'");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $footerLinks = json_decode($row['setting_value'], true);
    }
    
    // Fetch Quick Links (Usually same as header, but can be separate)
    $result2 = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key = 'header_links'");
    if ($result2 && $result2->num_rows > 0) {
        $row2 = $result2->fetch_assoc();
        $footerNavLinks = json_decode($row2['setting_value'], true);
    }
}
?>
<footer>
    <div class="footer-container">
        <div class="footer-section">
            <h3>About Us</h3>
            <p>We are dedicated to providing quality services and enhancing user experience with innovation.</p>
        </div>
        
        <div class="footer-section">
            <h3>Quick Links</h3>
            <ul>
                <?php foreach ($footerNavLinks as $link): ?>
                <li><a href="<?php echo htmlspecialchars($link['url']); ?>"><?php echo htmlspecialchars($link['label']); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="footer-section">
            <h3>Follow Us</h3>
            <div class="social-icons">
                <?php foreach ($footerLinks as $link): ?>
                <a href="<?php echo htmlspecialchars($link['url']); ?>" class="icon" title="<?php echo htmlspecialchars($link['label']); ?>">
                    <i class="<?php echo htmlspecialchars($link['icon']); ?>"></i>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; <?php echo $currentYear; ?> Exam Cell Automation. All Rights Reserved.</p>
    </div>
</footer>
</body>
</html>