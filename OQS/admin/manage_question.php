<?php
// Start output buffering and session at the VERY beginning
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/db.php';
require_once '../includes/auth.php';

// Create auth instance
$auth = new Auth($pdo);

// Check admin access - handle redirects BEFORE any output
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    if (headers_sent()) {
        echo '<script>window.location.href = "../dashboard.php";</script>';
        exit();
    } else {
        header("Location: ../dashboard.php");
        exit();
    }
}

$page_title = "Manage Question";

// Handle actions before any output
$action = $_GET['action'] ?? 'add';
$question_id = $_GET['id'] ?? 0;
$quiz_id = $_GET['quiz_id'] ?? 0;
$question = [];
$options = [];
$quiz = [];

// Validate quiz_id for new questions
if ($action === 'add' && !$quiz_id) {
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    if (headers_sent()) {
        echo '<script>window.location.href = "quizzes.php";</script>';
        exit();
    } else {
        header("Location: quizzes.php");
        exit();
    }
}

// Fetch quiz data
if ($quiz_id) {
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch();
    
    if (!$quiz) {
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        if (headers_sent()) {
            echo '<script>window.location.href = "quizzes.php";</script>';
            exit();
        } else {
            header("Location: quizzes.php");
            exit();
        }
    }
}

// Fetch question data for editing
if ($action === 'edit' && $question_id) {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
    $stmt->execute([$question_id]);
    $question = $stmt->fetch();
    
    if (!$question) {
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        if (headers_sent()) {
            echo '<script>window.location.href = "questions.php";</script>';
            exit();
        } else {
            header("Location: questions.php");
            exit();
        }
    }
    
    $quiz_id = $question['quiz_id'];
    
    // Fetch quiz data again
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch();
    
    // Fetch options for this question
    $stmt = $pdo->prepare("SELECT * FROM options WHERE question_id = ? ORDER BY option_order");
    $stmt->execute([$question_id]);
    $options = $stmt->fetchAll();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question_text = sanitize($_POST['question_text']);
    $marks = (int)$_POST['marks'];
    $question_order = (int)$_POST['question_order'];
    $explanation = sanitize($_POST['explanation'] ?? '');
    $quiz_id = (int)$_POST['quiz_id'];
    
    if ($action === 'add') {
        // Insert new question
        $stmt = $pdo->prepare("
            INSERT INTO questions (quiz_id, question_text, marks, question_order, explanation) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$quiz_id, $question_text, $marks, $question_order, $explanation]);
        $question_id = $pdo->lastInsertId();
        
        // Insert options
        $option_texts = $_POST['option_text'];
        $is_correct = $_POST['is_correct'];
        
        for ($i = 0; $i < count($option_texts); $i++) {
            if (trim($option_texts[$i]) !== '') {
                $stmt = $pdo->prepare("
                    INSERT INTO options (question_id, option_text, is_correct, option_order) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $question_id,
                    sanitize($option_texts[$i]),
                    ($is_correct == $i) ? 1 : 0,
                    $i + 1
                ]);
            }
        }
        
        $_SESSION['success'] = "Question added successfully!";
        
        // Clear any output buffers before redirect
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        if (headers_sent()) {
            echo '<script>window.location.href = "manage_question.php?action=edit&id=' . $question_id . '";</script>';
            exit();
        } else {
            header("Location: manage_question.php?action=edit&id=$question_id");
            exit();
        }
        
    } else {
        // Update existing question
        $stmt = $pdo->prepare("
            UPDATE questions SET 
                question_text = ?, 
                marks = ?, 
                question_order = ?, 
                explanation = ? 
            WHERE id = ?
        ");
        $stmt->execute([$question_text, $marks, $question_order, $explanation, $question_id]);
        
        // Delete old options
        $stmt = $pdo->prepare("DELETE FROM options WHERE question_id = ?");
        $stmt->execute([$question_id]);
        
        // Insert new options
        $option_texts = $_POST['option_text'];
        $is_correct = $_POST['is_correct'];
        
        for ($i = 0; $i < count($option_texts); $i++) {
            if (trim($option_texts[$i]) !== '') {
                $stmt = $pdo->prepare("
                    INSERT INTO options (question_id, option_text, is_correct, option_order) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $question_id,
                    sanitize($option_texts[$i]),
                    ($is_correct == $i) ? 1 : 0,
                    $i + 1
                ]);
            }
        }
        
        $_SESSION['success'] = "Question updated successfully!";
        
        // Clear any output buffers before redirect
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        if (headers_sent()) {
            echo '<script>window.location.href = "manage_question.php?action=edit&id=' . $question_id . '";</script>';
            exit();
        } else {
            header("Location: manage_question.php?action=edit&id=$question_id");
            exit();
        }
    }
}

// Handle question deletion
if (isset($_GET['delete'])) {
    $confirm = $_GET['confirm'] ?? false;
    
    if ($confirm === 'true') {
        $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
        $stmt->execute([$question_id]);
        
        $_SESSION['success'] = "Question deleted successfully!";
        
        // Clear any output buffers before redirect
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        if (headers_sent()) {
            echo '<script>window.location.href = "questions.php?quiz_id=' . $quiz_id . '";</script>';
            exit();
        } else {
            header("Location: questions.php?quiz_id=$quiz_id");
            exit();
        }
    }
}

// Now include the header AFTER all processing
require_once 'header.php';

// Show success message if redirected with parameter
if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo $_SESSION['success']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

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
                        <a class="nav-link" href="questions.php?quiz_id=<?php echo $quiz_id; ?>">
                            <i class="fas fa-arrow-left me-2"></i>Back to Questions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#">
                            <i class="fas fa-edit me-2"></i>
                            <?php echo $action === 'add' ? 'Add Question' : 'Edit Question'; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_question.php?action=add&quiz_id=<?php echo $quiz_id; ?>">
                            <i class="fas fa-plus me-2"></i>Add Another
                        </a>
                    </li>
                    <?php if ($action === 'edit'): ?>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="fas fa-trash me-2"></i>Delete Question
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <hr class="my-3">
                
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title">Quiz Info</h6>
                        <div class="small">
                            <div class="fw-bold mb-1"><?php echo sanitize($quiz['title']); ?></div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Marks:</span>
                                <span><?php echo $quiz['total_marks'] ?? 0; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Time:</span>
                                <span><?php echo $quiz['time_limit'] ?? 0; ?> mins</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Status:</span>
                                <span class="badge bg-<?php echo ($quiz['is_active'] ?? 0) ? 'success' : 'warning'; ?>">
                                    <?php echo ($quiz['is_active'] ?? 0) ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($action === 'edit'): ?>
                <div class="card bg-light mt-3">
                    <div class="card-body">
                        <h6 class="card-title">Question Stats</h6>
                        <div class="small">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Marks:</span>
                                <span class="badge bg-info"><?php echo $question['marks']; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Order:</span>
                                <span class="badge bg-primary"><?php echo $question['question_order']; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Options:</span>
                                <span><?php echo count($options); ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Correct:</span>
                                <span>
                                    <?php 
                                    $correct_count = 0;
                                    foreach ($options as $opt) {
                                        if ($opt['is_correct']) $correct_count++;
                                    }
                                    echo $correct_count;
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Navigation Help -->
                <div class="card bg-light mt-3">
                    <div class="card-body">
                        <h6 class="card-title">Quick Tips</h6>
                        <div class="small">
                            <ul class="mb-0 ps-3">
                                <li>Mark correct answer with radio button</li>
                                <li>At least 2 options required</li>
                                <li>Only one correct answer per question</li>
                                <li>Explanation is optional</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-question me-2"></i>
                    <?php echo $action === 'add' ? 'Add New Question' : 'Edit Question'; ?>
                    <small class="text-muted fs-6">(Quiz: <?php echo sanitize($quiz['title']); ?>)</small>
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <?php if ($action === 'edit'): ?>
                    <div class="btn-group me-2">
                        <a href="questions.php?quiz_id=<?php echo $quiz_id; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-list me-1"></i>All Questions
                        </a>
                        <a href="manage_question.php?action=add&quiz_id=<?php echo $quiz_id; ?>" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-plus me-1"></i>Add New
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Question Form -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 fw-bold text-primary">Question Details</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" id="questionForm">
                                <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
                                
                                <div class="mb-4">
                                    <h6 class="border-bottom pb-2 mb-3">Basic Information</h6>
                                    
                                    <div class="mb-3">
                                        <label for="question_text" class="form-label">Question Text *</label>
                                        <textarea class="form-control" id="question_text" name="question_text" 
                                                  rows="4" required><?php echo $question['question_text'] ?? ''; ?></textarea>
                                        <small class="text-muted">Enter the question text clearly and concisely</small>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="marks" class="form-label">Marks *</label>
                                            <input type="number" class="form-control" id="marks" name="marks" 
                                                   value="<?php echo $question['marks'] ?? 10; ?>" min="1" max="100" required>
                                            <small class="text-muted">Points for this question</small>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="question_order" class="form-label">Question Order *</label>
                                            <input type="number" class="form-control" id="question_order" name="question_order" 
                                                   value="<?php echo $question['question_order'] ?? 1; ?>" min="1" required>
                                            <small class="text-muted">Display order in the quiz</small>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="explanation" class="form-label">Explanation (Optional)</label>
                                        <textarea class="form-control" id="explanation" name="explanation" rows="2"><?php echo $question['explanation'] ?? ''; ?></textarea>
                                        <small class="text-muted">Explanation shown after answering (optional)</small>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <h6 class="border-bottom pb-2 mb-3">Answer Options</h6>
                                    <p class="text-muted">Select the correct answer by clicking the radio button.</p>
                                    
                                    <?php for ($i = 0; $i < 4; $i++): ?>
                                    <div class="option-card card mb-3">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-auto">
                                                    <div class="option-number rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                                         style="width: 36px; height: 36px;">
                                                        <?php echo $i + 1; ?>
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <div class="mb-2">
                                                        <label for="option<?php echo $i; ?>" class="form-label">Option <?php echo $i + 1; ?> *</label>
                                                        <input type="text" class="form-control option-input" 
                                                               id="option<?php echo $i; ?>" 
                                                               name="option_text[<?php echo $i; ?>]" 
                                                               value="<?php echo $options[$i]['option_text'] ?? ''; ?>" 
                                                               required>
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="form-check">
                                                        <input class="form-check-input correct-option" 
                                                               type="radio" 
                                                               name="is_correct" 
                                                               value="<?php echo $i; ?>" 
                                                               id="correct<?php echo $i; ?>"
                                                               <?php 
                                                               if ($action === 'edit' && isset($options[$i]) && $options[$i]['is_correct']) {
                                                                   echo 'checked';
                                                               } elseif ($i === 0 && $action === 'add') {
                                                                   echo 'checked';
                                                               }
                                                               ?> required>
                                                        <label class="form-check-label" for="correct<?php echo $i; ?>">
                                                            Correct Answer
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endfor; ?>
                                    
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Note:</strong> All 4 options are required. Mark the correct answer by selecting the radio button.
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="questions.php?quiz_id=<?php echo $quiz_id; ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        <?php echo $action === 'add' ? 'Add Question' : 'Update Question'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Question Preview -->
                    <?php if ($action === 'edit'): ?>
                    <div class="card shadow">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 fw-bold text-success">Question Preview</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshPreview()">
                                <i class="fas fa-sync-alt me-1"></i>Refresh
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="question-preview">
                                <h5 class="mb-3">
                                    <span class="badge bg-primary me-2">Q<?php echo $question['question_order']; ?></span>
                                    <?php echo sanitize($question['question_text']); ?>
                                    <small class="text-muted float-end"><?php echo $question['marks']; ?> marks</small>
                                </h5>
                                
                                <div class="options-preview">
                                    <?php foreach ($options as $index => $option): ?>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" 
                                               id="preview_option<?php echo $index; ?>" 
                                               name="preview_answer" 
                                               <?php echo $option['is_correct'] ? 'checked' : ''; ?>
                                               disabled>
                                        <label class="form-check-label" for="preview_option<?php echo $index; ?>">
                                            <?php echo sanitize($option['option_text']); ?>
                                            <?php if ($option['is_correct']): ?>
                                                <span class="badge bg-success ms-2">Correct</span>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <?php if ($question['explanation']): ?>
                                <div class="alert alert-info mt-3">
                                    <strong><i class="fas fa-lightbulb me-2"></i>Explanation:</strong>
                                    <?php echo sanitize($question['explanation']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-lg-4">
                    <!-- Quick Actions -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 fw-bold text-dark">Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="manage_question.php?action=add&quiz_id=<?php echo $quiz_id; ?>" class="btn btn-success">
                                    <i class="fas fa-plus me-2"></i>Add Another
                                </a>
                                <a href="questions.php?quiz_id=<?php echo $quiz_id; ?>" class="btn btn-primary">
                                    <i class="fas fa-list me-2"></i>View All Questions
                                </a>
                                <a href="../attempt.php?id=<?php echo $quiz_id; ?>" class="btn btn-info" target="_blank">
                                    <i class="fas fa-eye me-2"></i>Preview Quiz
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quiz Info -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 fw-bold text-info">Quiz Information</h6>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo sanitize($quiz['title']); ?></h5>
                            <p class="card-text text-muted"><?php echo sanitize($quiz['description']); ?></p>
                            
                            <div class="row text-center">
                                <div class="col-6 mb-2">
                                    <div class="card bg-light">
                                        <div class="card-body py-2">
                                            <small class="text-muted d-block">Total Marks</small>
                                            <div class="h6 mb-0"><?php echo $quiz['total_marks']; ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 mb-2">
                                    <div class="card bg-light">
                                        <div class="card-body py-2">
                                            <small class="text-muted d-block">Time Limit</small>
                                            <div class="h6 mb-0"><?php echo $quiz['time_limit']; ?> min</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php
                            // Get total questions count and marks
                            $stmt = $pdo->prepare("SELECT COUNT(*) as count, SUM(marks) as total_marks FROM questions WHERE quiz_id = ?");
                            $stmt->execute([$quiz_id]);
                            $quiz_stats = $stmt->fetch();
                            ?>
                            
                            <div class="mt-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Questions in Quiz:</span>
                                    <span class="badge bg-primary"><?php echo $quiz_stats['count']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Total Questions Marks:</span>
                                    <span class="badge bg-success"><?php echo $quiz_stats['total_marks']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Remaining Marks:</span>
                                    <span class="badge bg-<?php echo ($quiz['total_marks'] - $quiz_stats['total_marks']) >= 0 ? 'warning' : 'danger'; ?>">
                                        <?php echo $quiz['total_marks'] - $quiz_stats['total_marks']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<?php if ($action === 'edit'): ?>
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Delete Question</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-trash fa-4x text-danger mb-3"></i>
                    <h4>Are you sure?</h4>
                    <p>This action will delete the question and all associated options. This cannot be undone.</p>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Warning:</strong> This will delete:
                        <ul class="mb-0 mt-2">
                            <li>Question: <?php echo substr(sanitize($question['question_text']), 0, 50); ?>...</li>
                            <li>Options: <?php echo count($options); ?> answer options</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="manage_question.php?action=edit&id=<?php echo $question_id; ?>&delete=true&confirm=true" class="btn btn-danger">
                    <i class="fas fa-trash me-2"></i>Delete Question
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function refreshPreview() {
    // This function would typically refresh the preview with current form data
    alert('Preview refreshed with current form data');
}

function addOption() {
    alert('Adding option functionality would go here');
}

function clearForm() {
    if (confirm('Are you sure you want to clear all form data?')) {
        document.getElementById('questionForm').reset();
    }
}

function randomizeOptions() {
    alert('Randomize options functionality would go here');
}
</script>
<?php endif; ?>

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

.nav-link.text-danger:hover {
    background-color: #f8d7da;
    color: #dc3545 !important;
}

.option-card {
    border-left: 4px solid var(--primary-color);
    transition: all 0.3s;
}

.option-card:hover {
    border-left-color: var(--accent-color);
    transform: translateX(5px);
}

.option-number {
    font-weight: 600;
    font-size: 1rem;
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
    
    .option-card .row {
        flex-direction: column;
    }
    
    .option-card .col-auto {
        margin-bottom: 10px;
    }
}
</style>

<?php 
// Include footer
require_once 'footer.php'; 
?>