<?php
// Start session and output buffering at the very beginning
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/db.php';
require_once '../includes/auth.php';

// Create auth instance
$auth = new Auth($pdo);

// Check admin access
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    // Use safe redirect
    if (headers_sent()) {
        echo '<script>window.location.href = "../dashboard.php";</script>';
        exit();
    } else {
        header("Location: ../dashboard.php");
        exit();
    }
}

$page_title = "Manage Quizzes";

// Handle actions before any output
$action = $_GET['action'] ?? 'list';
$quiz_id = $_GET['id'] ?? 0;
$message = '';

// Handle quiz deletion
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM quizzes WHERE id = ?");
    if ($stmt->execute([$delete_id])) {
        $message = "Quiz deleted successfully!";
    }
    
    // Redirect to avoid resubmission
    if (headers_sent()) {
        echo '<script>window.location.href = "quizzes.php?deleted=1";</script>';
        exit();
    } else {
        header("Location: quizzes.php?deleted=1");
        exit();
    }
}

// Handle form submission for create/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $total_marks = (int)$_POST['total_marks'];
    $time_limit = (int)$_POST['time_limit'];
    
    if ($action === 'create') {
        $stmt = $pdo->prepare("
            INSERT INTO quizzes (title, description, total_marks, time_limit, created_by) 
            VALUES (?, ?, ?, ?, ?)
        ");
        if ($stmt->execute([$title, $description, $total_marks, $time_limit, $_SESSION['user_id']])) {
            $quiz_id = $pdo->lastInsertId();
            $message = "Quiz created successfully!";
            
            // Redirect to questions page
            if (headers_sent()) {
                echo '<script>window.location.href = "questions.php?quiz_id=' . $quiz_id . '";</script>';
                exit();
            } else {
                header("Location: questions.php?quiz_id=$quiz_id");
                exit();
            }
        }
    } else {
        $stmt = $pdo->prepare("
            UPDATE quizzes SET title = ?, description = ?, total_marks = ?, time_limit = ? 
            WHERE id = ?
        ");
        if ($stmt->execute([$title, $description, $total_marks, $time_limit, $quiz_id])) {
            $message = "Quiz updated successfully!";
            
            // Redirect to avoid resubmission
            if (headers_sent()) {
                echo '<script>window.location.href = "quizzes.php?updated=1";</script>';
                exit();
            } else {
                header("Location: quizzes.php?updated=1");
                exit();
            }
        }
    }
}

// Now include the header AFTER all processing
require_once 'header.php';

// Fetch quiz data for editing
$quiz = [];
if ($action === 'edit' && $quiz_id) {
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch();
    
    if (!$quiz) {
        // Redirect if quiz not found
        if (headers_sent()) {
            echo '<script>window.location.href = "quizzes.php";</script>';
            exit();
        } else {
            header("Location: quizzes.php");
            exit();
        }
    }
}

// Show success message if redirected with parameter
if (isset($_GET['deleted'])) {
    echo '<div class="alert alert-success">Quiz deleted successfully!</div>';
}
if (isset($_GET['updated'])) {
    echo '<div class="alert alert-success">Quiz updated successfully!</div>';
}
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $action === 'list' ? 'active' : ''; ?>" href="quizzes.php">
                            <i class="fas fa-list me-2"></i>All Quizzes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $action === 'create' ? 'active' : ''; ?>" href="quizzes.php?action=create">
                            <i class="fas fa-plus me-2"></i>Create New
                        </a>
                    </li>
                    <?php if ($action === 'edit'): ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="#">
                            <i class="fas fa-edit me-2"></i>Edit Quiz
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="questions.php?quiz_id=<?php echo $quiz_id; ?>">
                            <i class="fas fa-question me-2"></i>Manage Questions
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-question-circle me-2"></i>
                    <?php 
                    if ($action === 'create') {
                        echo 'Create New Quiz';
                    } elseif ($action === 'edit') {
                        echo 'Edit Quiz: ' . sanitize($quiz['title']);
                    } else {
                        echo 'Manage Quizzes';
                    }
                    ?>
                </h1>
                <?php if ($action === 'list'): ?>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="?action=create" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create New Quiz
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if ($action === 'create' || $action === 'edit'): ?>
            <!-- Create/Edit Form -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">
                                <i class="fas fa-question-circle me-2"></i>
                                <?php echo $action === 'create' ? 'Create New Quiz' : 'Edit Quiz'; ?>
                            </h4>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Quiz Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" required
                                           value="<?php echo $quiz['title'] ?? ''; ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo $quiz['description'] ?? ''; ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="total_marks" class="form-label">Total Marks *</label>
                                        <input type="number" class="form-control" id="total_marks" name="total_marks" 
                                               value="<?php echo $quiz['total_marks'] ?? 100; ?>" required min="1">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="time_limit" class="form-label">Time Limit (minutes) *</label>
                                        <input type="number" class="form-control" id="time_limit" name="time_limit" 
                                               value="<?php echo $quiz['time_limit'] ?? 30; ?>" required min="1">
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="quizzes.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        <?php echo $action === 'create' ? 'Create Quiz' : 'Update Quiz'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php else: ?>
            <!-- List All Quizzes -->
            <div class="card">
                <div class="card-body">
                    <?php
                    // Fetch all quizzes
                    $stmt = $pdo->query("
                        SELECT q.*, u.name as creator_name, 
                               COUNT(qu.id) as question_count,
                               COUNT(r.id) as attempt_count
                        FROM quizzes q 
                        LEFT JOIN users u ON q.created_by = u.id 
                        LEFT JOIN questions qu ON q.id = qu.quiz_id
                        LEFT JOIN results r ON q.id = r.quiz_id
                        GROUP BY q.id
                        ORDER BY q.created_at DESC
                    ");
                    $quizzes = $stmt->fetchAll();
                    ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Questions</th>
                                    <th>Attempts</th>
                                    <th>Time Limit</th>
                                    <th>Total Marks</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($quizzes)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                                        <h4>No Quizzes Found</h4>
                                        <p class="text-muted">Create your first quiz to get started.</p>
                                        <a href="?action=create" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Create First Quiz
                                        </a>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($quizzes as $quiz): ?>
                                    <tr>
                                        <td><?php echo $quiz['id']; ?></td>
                                        <td>
                                            <strong><?php echo sanitize($quiz['title']); ?></strong>
                                            <?php if ($quiz['description']): ?>
                                                <br><small class="text-muted"><?php echo substr(sanitize($quiz['description']), 0, 50); ?>...</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $quiz['question_count']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo $quiz['attempt_count']; ?></span>
                                        </td>
                                        <td><?php echo $quiz['time_limit']; ?> min</td>
                                        <td><?php echo $quiz['total_marks']; ?> pts</td>
                                        <td><?php echo sanitize($quiz['creator_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($quiz['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="questions.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn btn-outline-primary" title="Questions">
                                                    <i class="fas fa-question"></i>
                                                </a>
                                                <a href="?action=edit&id=<?php echo $quiz['id']; ?>" class="btn btn-outline-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?delete=<?php echo $quiz['id']; ?>" 
                                                   class="btn btn-outline-danger" 
                                                   onclick="return confirm('Delete this quiz? This will also delete all questions and results.')"
                                                   title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.sidebar {
    background: #f8f9fa;
    border-right: 1px solid #dee2e6;
    min-height: calc(100vh - 56px);
    padding-top: 20px;
}

.nav-link {
    color: #495057;
    padding: 0.75rem 1rem;
    border-radius: 0.375rem;
    margin-bottom: 0.25rem;
    transition: all 0.3s;
}

.nav-link:hover {
    background-color: #e9ecef;
    color: #4a6fa5;
}

.nav-link.active {
    background-color: #4a6fa5;
    color: white !important;
}

@media (max-width: 768px) {
    .sidebar {
        min-height: auto;
        border-right: none;
        border-bottom: 1px solid #dee2e6;
        margin-bottom: 1rem;
    }
    
    .col-md-9 {
        padding-left: 15px !important;
        padding-right: 15px !important;
    }
}
</style>

<?php 
// Include footer
require_once 'footer.php'; 
?>