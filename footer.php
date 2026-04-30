<?php
$currentYear = date("Y"); // Get current year dynamically
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
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="services.php">Services</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h3>Follow Us</h3>
            <div class="social-icons">
                <a href="https://www.facebook.com/dbcyelagiri" class="icon"><i class="fab fa-facebook-f"></i></a>
                <a href="https://twitter.com/dbcyelagiri1" class="icon"><i class="fab fa-twitter"></i></a>
                <a href="https://in.linkedin.com/company/don-bosco-college-co-ed-yelagiri-hills" class="icon"><i class="fab fa-linkedin-in"></i></a>
                <a href="https://www.instagram.com/dbcyelagiri" class="icon"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; <?php echo $currentYear; ?> Exam Cell Automation. All Rights Reserved.</p>
    </div>
</footer>