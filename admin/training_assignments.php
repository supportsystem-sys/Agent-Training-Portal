<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();

// Handle assignment creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_training'])) {
    $user_id = $_POST['user_id'];
    $course_id = $_POST['course_id'];
    $notes = $_POST['notes'] ?? '';
    $assigned_by = $_SESSION['user_id'];
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO training_assignments (user_id, course_id, assigned_by, notes)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $course_id, $assigned_by, $notes]);
        $success = "Training assigned successfully!";
    } catch (Exception $e) {
        $error = "Failed to assign training. User may already be assigned to this training.";
    }
}

// Handle assignment status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $assignment_id = $_POST['assignment_id'];
    $status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("
            UPDATE training_assignments 
            SET status = ?
            WHERE id = ?
        ");
        $stmt->execute([$status, $assignment_id]);
        $success = "Assignment status updated successfully!";
    } catch (Exception $e) {
        $error = "Failed to update assignment status.";
    }
}

// Handle unassignment (delete assignment)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_assignment'])) {
    $assignment_id = $_POST['assignment_id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM training_assignments WHERE id = ?");
        $stmt->execute([$assignment_id]);
        $success = "Assignment removed successfully!";
    } catch (Exception $e) {
        $error = "Failed to remove assignment.";
    }
}

// Get all assignments with user and course details
$stmt = $pdo->prepare("
    SELECT ta.*, u.name as user_name, u.email as user_email,
           c.title as course_title, admin.name as assigned_by_name
    FROM training_assignments ta
    JOIN users u ON ta.user_id = u.id
    JOIN courses c ON ta.course_id = c.id
    JOIN users admin ON ta.assigned_by = admin.id
    ORDER BY ta.assigned_at DESC
");
$stmt->execute();
$assignments = $stmt->fetchAll();

// Get all users for assignment form
$stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE role = 'learner' ORDER BY name");
$stmt->execute();
$users = $stmt->fetchAll();

// Get all courses for assignment form
$stmt = $pdo->prepare("SELECT id, title FROM courses ORDER BY title");
$stmt->execute();
$courses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Assignments - LMS Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">Halcom Marketing</div>
            <div class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="trainings.php">Training</a>
                <a href="users.php">Users</a>
                <a href="training_assignments.php" class="active">Assignments</a>
                <a href="../auth/logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="d-flex justify-between align-center mb-20">
            <h1>Training Assignments</h1>
            <button onclick="document.getElementById('assignForm').style.display='block'" class="btn btn-primary">
                Assign Training
            </button>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Assignment Form -->
        <div id="assignForm" class="card" style="display: none; margin-bottom: 20px;">
            <div class="card-header">
                <h3>Assign Training to User</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="user_id">Select User:</label>
                        <select name="user_id" id="user_id" required>
                            <option value="">Choose a user...</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['name'] . ' (' . $user['email'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="course_id">Select Training:</label>
                        <select name="course_id" id="course_id" required>
                            <option value="">Choose a training...</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>">
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes (Optional):</label>
                        <textarea name="notes" id="notes" rows="3" placeholder="Add any notes about this assignment..."></textarea>
                    </div>

                    <div class="d-flex gap-10">
                        <button type="submit" name="assign_training" class="btn btn-primary">Assign Training</button>
                        <button type="button" onclick="document.getElementById('assignForm').style.display='none'" class="btn btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Assignments List -->
        <div class="card">
            <div class="card-header">
                <h3>Current Assignments</h3>
            </div>
            <div class="card-body">
                <?php if (empty($assignments)): ?>
                    <p>No training assignments found.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Training</th>
                                    <th>Status</th>
                                    <th>Assigned By</th>
                                    <th>Assigned Date</th>
                                    <th>Notes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignments as $assignment): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($assignment['user_name']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($assignment['user_email']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($assignment['course_title']); ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                                <select name="status" onchange="this.form.submit()" class="status-select">
                                                    <option value="assigned" <?php echo $assignment['status'] == 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                                                    <option value="started" <?php echo $assignment['status'] == 'started' ? 'selected' : ''; ?>>Started</option>
                                                    <option value="completed" <?php echo $assignment['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                </select>
                                                <input type="hidden" name="update_status" value="1">
                                            </form>
                                        </td>
                                        <td><?php echo htmlspecialchars($assignment['assigned_by_name']); ?></td>
                                        <td><?php echo formatDateTime($assignment['assigned_at']); ?></td>
                                        <td>
                                            <?php if ($assignment['notes']): ?>
                                                <span title="<?php echo htmlspecialchars($assignment['notes']); ?>">
                                                    <?php echo htmlspecialchars(substr($assignment['notes'], 0, 30)) . (strlen($assignment['notes']) > 30 ? '...' : ''); ?>
                                                </span>
                                            <?php else: ?>
                                                <em>No notes</em>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="user_details.php?id=<?php echo $assignment['user_id']; ?>" class="btn btn-sm btn-primary">View User</a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Unassign this training from the learner?');">
                                                <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                                <input type="hidden" name="delete_assignment" value="1">
                                                <button type="submit" class="btn btn-sm btn-secondary">Unassign</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <style>
        .status-select {
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
        }
        
        .status-select option[value="assigned"] { background-color: #fff3cd; }
        .status-select option[value="started"] { background-color: #d1ecf1; }
        .status-select option[value="completed"] { background-color: #d4edda; }
    </style>
</body>
</html>
