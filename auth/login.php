<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

function getManagementDatabase() {
    static $managementPdo = null;
    
    if ($managementPdo === null) {
        try {
            $managementConfig = [
                'host' => '127.0.0.1',
                'port' => '3306',
                'username' => 'status_employee_performance',
                'password' => 'gHeESXG^La48',
                'database' => 'status_employee_performance'
            ];
            
            $dsn = "mysql:host={$managementConfig['host']};port={$managementConfig['port']};dbname={$managementConfig['database']};charset=utf8mb4";
            $managementPdo = new PDO($dsn, $managementConfig['username'], $managementConfig['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log("Management database connection failed: " . $e->getMessage());
            return false;
        }
    }
    
    return $managementPdo;
}

// Validate authentication code
function validateAuthCode($code) {
    $managementDb = getManagementDatabase();
    if (!$managementDb) {
        return false;
    }
    
    try {
        $stmt = $managementDb->prepare("
            SELECT * FROM auth_codes 
            WHERE code = ? 
            AND created >= DATE_SUB(NOW(), INTERVAL 2 DAY)
            LIMIT 1
        ");
        $stmt->execute([$code]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Auth code validation failed: " . $e->getMessage());
        return false;
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If user is already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('../admin/dashboard.php');
    } else {
        redirect('../learner/dashboard.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && verifyPassword($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            // Start user session logging
            startUserSession($user['id']);
            
            if ($user['role'] === 'admin') {
                redirect('../admin/dashboard.php');
            } else {
                redirect('../learner/dashboard.php');
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
else if (isset($_GET['code']) && !empty($_GET['code'])) {
    $authCode = trim($_GET['code']);
    
    $authRecord = validateAuthCode($authCode);
    if ($authRecord) {
        // Code is valid and within 2 days, log user in as admin
        session_regenerate_id(true);
        
        // Fetch admin user details from main database
        try {
            
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'admin@test.com'");
                $stmt->execute();
                $user = $stmt->fetch();
                
                if ($user) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Start user session logging
                    startUserSession($user['id']);
                    
                    if ($user['role'] === 'admin') {
                        redirect('../admin/dashboard.php');
                    } else {
                        redirect('../learner/dashboard.php');
                    }
                } else {
                    $error = 'Invalid email or password.';
                }
             
        } catch (PDOException $e) {
            $message= "Wrong UserName OR Password"; 
            header('Location:login.php');
            exit();
        }
        
        // Optional: Mark code as used
        $managementDb = getManagementDatabase();
        if ($managementDb) {
            try {
                $stmt = $managementDb->prepare("UPDATE auth_codes SET used_at = NOW() WHERE code = ?");
                $stmt->execute([$authCode]);
            } catch (PDOException $e) {
                error_log("Failed to mark code as used: " . $e->getMessage());
            }
        }
        
       
        exit();
    } else {
        // Invalid or expired code
        $error = 'Invalid or expired authentication code.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Halcom Marketing</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="login-page">
    <div class="container">
        <div class="auth-form">
            <div class="logo-container">
                <img src="../assets/images/logo.png" alt="Halcom Marketing Logo" class="login-logo">
            </div>
            <h2>Login to Halcom<br>Training Portal</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
    </div>
    </div>
</body>
</html>
