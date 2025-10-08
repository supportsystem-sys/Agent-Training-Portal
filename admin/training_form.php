<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();
$Training = null;
$is_edit = false;

// Handle edit mode
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $is_edit = true;
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $Training = $stmt->fetch();
    
    if (!$Training) {
        redirect('trainings.php');
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    
    if (empty($title) || empty($description)) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            if ($is_edit) {
                $stmt = $pdo->prepare("UPDATE courses SET title = ?, description = ? WHERE id = ?");
                $stmt->execute([$title, $description, $Training['id']]);
                $success = "Training updated successfully.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO courses (title, description, created_by) VALUES (?, ?, ?)");
                $stmt->execute([$title, $description, $_SESSION['user_id']]);
                $course_id = $pdo->lastInsertId();
                $success = "Training created successfully. <a href='lessons.php?course_id=$course_id'>Add lessons now</a>.";
            }
        } catch (Exception $e) {
            $error = "An error occurred: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit' : 'Add'; ?> Training - Halcom Marketing Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">Halcom Marketing Admin</div>
            <div class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="trainings.php">Training</a>
                <a href="users.php">Users</a>
                <a href="training_assignments.php">Assignments</a>
                <a href="../auth/logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="d-flex justify-between align-center mb-20">
            <h1><?php echo $is_edit ? 'Edit Training' : 'Add New Training'; ?></h1>
            <a href="trainings.php" class="btn btn-secondary">Back to Training</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                Training Information
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="title">Training Title:</label>
                        <input type="text" id="title" name="title" required 
                               value="<?php echo $Training ? htmlspecialchars($Training['title']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" rows="5" required><?php echo $Training ? htmlspecialchars($Training['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="d-flex gap-10">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $is_edit ? 'Update Training' : 'Create Training'; ?>
                        </button>
                        <a href="trainings.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
