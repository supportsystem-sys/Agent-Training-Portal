<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isLearner()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get current user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($name) || empty($email)) {
        $error = 'Name and email are required.';
    } else {
        try {
            // Check if email already exists for another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            
            if ($stmt->fetch()) {
                $error = 'Email already exists for another user.';
            } else {
                // Verify current password if changing password
                if (!empty($new_password)) {
                    if (!verifyPassword($current_password, $user['password'])) {
                        $error = 'Current password is incorrect.';
                    } elseif ($new_password !== $confirm_password) {
                        $error = 'New passwords do not match.';
                    } elseif (strlen($new_password) < 6) {
                        $error = 'Password must be at least 6 characters long.';
                    } else {
                        // Update user information and password
                        $hashed_password = hashPassword($new_password);
                        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
                        $stmt->execute([$name, $email, $hashed_password, $user_id]);
                        $success = "Profile and password updated successfully.";
                    }
                } else {
                    // Update user information only
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $user_id]);
                    $success = "Profile updated successfully.";
                }
                
                if ($success) {
                    // Update session data
                    $_SESSION['name'] = $name;
                    $_SESSION['email'] = $email;
                    
                    // Refresh user data
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch();
                }
            }
        } catch (Exception $e) {
            $error = "An error occurred while updating profile.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - LMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-page">
    <div class="header learner-header">
        <div class="header-content">
            <div class="header-left">
                <img src="../assets/images/logo.png" alt="Halcom Marketing Logo" class="header-logo">
            </div>
            <div class="header-center">
                <h1 class="welcome-text">Welcome to Halcom Training Module</h1>
            </div>
            <div class="header-right">
                <div class="nav">
                    <a href="dashboard.php">Dashboard</a>
                    <a href="../auth/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="d-flex justify-between align-center mb-20">
            <div>
                <h1>Edit Profile</h1>
                <p>Update your personal information and password</p>
            </div>
            <a href="profile.php" class="btn btn-secondary">Back to Profile</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                Personal Information
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="name">Full Name:</label>
                        <input type="text" id="name" name="name" required 
                               value="<?php echo htmlspecialchars($user['name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo htmlspecialchars($user['email']); ?>">
                    </div>
                    
                    <div class="d-flex gap-10">
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                        <a href="profile.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                Change Password
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="current_password">Current Password:</label>
                        <input type="password" id="current_password" name="current_password" 
                               placeholder="Enter your current password">
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password:</label>
                        <input type="password" id="new_password" name="new_password" 
                               placeholder="Enter new password">
                        <small>Password must be at least 6 characters long.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               placeholder="Confirm new password">
                    </div>
                    
                    <div class="d-flex gap-10">
                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                        <a href="profile.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Account Information -->
        <div class="card">
            <div class="card-header">
                Account Information
            </div>
            <div class="card-body">
                <div class="d-flex gap-20">
                    <div>
                        <strong>Member Since:</strong> <?php echo formatDate($user['created_at']); ?>
                    </div>
                    <div>
                        <strong>User ID:</strong> #<?php echo $user['id']; ?>
                    </div>
                    <div>
                        <strong>Role:</strong> <?php echo ucfirst($user['role']); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password confirmation validation
        document.getElementById('new_password').addEventListener('input', function() {
            const confirmPassword = document.getElementById('confirm_password');
            const currentPassword = document.getElementById('current_password');
            
            if (this.value.length > 0) {
                confirmPassword.required = true;
                currentPassword.required = true;
            } else {
                confirmPassword.required = false;
                currentPassword.required = false;
                confirmPassword.value = '';
                currentPassword.value = '';
            }
        });

        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password');
            if (this.value !== newPassword.value) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
    <footer class="footer">
        <div class="footer-content">
            Copyright Halcom Group 2025
        </div>
    </footer>
</body>
</html>
