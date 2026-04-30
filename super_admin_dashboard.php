<?php
session_start();
include 'header.php';
include 'db_config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'superadmin') {
    header("Location: login.php");
    exit();
}

// Fetch current settings
$headerLinks = [];
$footerLinks = [];

$result = $conn->query("SELECT setting_key, setting_value FROM site_settings");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row['setting_key'] == 'header_links') {
            $headerLinks = json_decode($row['setting_value'], true);
        } else if ($row['setting_key'] == 'footer_social') {
            $footerLinks = json_decode($row['setting_value'], true);
        }
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_links'])) {
    
    // Process Header Links
    $newHeaderLinks = [];
    if (isset($_POST['h_labels']) && is_array($_POST['h_labels'])) {
        for ($i = 0; $i < count($_POST['h_labels']); $i++) {
            if (!empty($_POST['h_labels'][$i]) && !empty($_POST['h_urls'][$i])) {
                $newHeaderLinks[] = [
                    "label" => $_POST['h_labels'][$i],
                    "url" => $_POST['h_urls'][$i]
                ];
            }
        }
    }
    
    // Process Footer Links
    $newFooterLinks = [];
    if (isset($_POST['f_labels']) && is_array($_POST['f_labels'])) {
        for ($i = 0; $i < count($_POST['f_labels']); $i++) {
            if (!empty($_POST['f_labels'][$i]) && !empty($_POST['f_urls'][$i])) {
                $newFooterLinks[] = [
                    "label" => $_POST['f_labels'][$i],
                    "url" => $_POST['f_urls'][$i],
                    "icon" => $_POST['f_icons'][$i]
                ];
            }
        }
    }
    
    // Save to database
    $headerJson = json_encode($newHeaderLinks);
    $footerJson = json_encode($newFooterLinks);
    
    $stmt1 = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('header_links', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt1->bind_param("ss", $headerJson, $headerJson);
    $stmt1->execute();
    
    $stmt2 = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('footer_social', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt2->bind_param("ss", $footerJson, $footerJson);
    $stmt2->execute();
    
    echo "<script>alert('Settings updated successfully!'); window.location.href='super_admin_dashboard.php';</script>";
    exit();
}
?>

<div class="dashboard-wrapper">
    <div class="sidebar">
        <h2>Super Admin</h2>
        <div class="link-container">
            <a href="super_admin_dashboard.php" class="active">Site Settings</a>
            <a href="admin_dashboard.php">Go to Admin Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="main-content" style="display: block;">
        <div class="header">
            <h1>Global Site Settings</h1>
            <p style="color: var(--text-muted); margin-bottom: 24px;">Manage navigation links without editing code.</p>
        </div>

        <form method="POST" action="">
            <div class="card" style="margin-bottom: 24px; max-width: 100%;">
                <h3>Header Navigation Links</h3>
                <p>Manage the links that appear in the top navigation bar.</p>
                
                <table id="headerTable">
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>URL</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($headerLinks as $link): ?>
                        <tr>
                            <td><input type="text" name="h_labels[]" value="<?php echo htmlspecialchars($link['label']); ?>" required></td>
                            <td><input type="text" name="h_urls[]" value="<?php echo htmlspecialchars($link['url']); ?>" required></td>
                            <td><button type="button" class="btn btn-secondary" onclick="this.parentElement.parentElement.remove()">Remove</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <br>
                <button type="button" class="btn btn-secondary" onclick="addHeaderRow()">+ Add Link</button>
            </div>

            <div class="card" style="margin-bottom: 24px; max-width: 100%;">
                <h3>Footer Social Links</h3>
                <p>Manage the social media icons in the footer.</p>
                
                <table id="footerTable">
                    <thead>
                        <tr>
                            <th>Platform Name</th>
                            <th>URL</th>
                            <th>FontAwesome Icon Class</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($footerLinks as $link): ?>
                        <tr>
                            <td><input type="text" name="f_labels[]" value="<?php echo htmlspecialchars($link['label']); ?>" required></td>
                            <td><input type="text" name="f_urls[]" value="<?php echo htmlspecialchars($link['url']); ?>" required></td>
                            <td><input type="text" name="f_icons[]" value="<?php echo htmlspecialchars($link['icon']); ?>" required placeholder="e.g. fab fa-facebook-f"></td>
                            <td><button type="button" class="btn btn-secondary" onclick="this.parentElement.parentElement.remove()">Remove</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <br>
                <button type="button" class="btn btn-secondary" onclick="addFooterRow()">+ Add Social Link</button>
            </div>

            <button type="submit" name="update_links" class="btn" style="width: 100%;">Save Global Settings</button>
        </form>
    </div>
</div>

<script>
function addHeaderRow() {
    const tbody = document.querySelector('#headerTable tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" name="h_labels[]" required></td>
        <td><input type="text" name="h_urls[]" required></td>
        <td><button type="button" class="btn btn-secondary" onclick="this.parentElement.parentElement.remove()">Remove</button></td>
    `;
    tbody.appendChild(tr);
}

function addFooterRow() {
    const tbody = document.querySelector('#footerTable tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" name="f_labels[]" required></td>
        <td><input type="text" name="f_urls[]" required></td>
        <td><input type="text" name="f_icons[]" required placeholder="e.g. fab fa-twitter"></td>
        <td><button type="button" class="btn btn-secondary" onclick="this.parentElement.parentElement.remove()">Remove</button></td>
    `;
    tbody.appendChild(tr);
}
</script>

<?php include 'footer.php'; ?>
