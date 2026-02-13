<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/auth.php';
$auth->requireAdmin();

$page_title = "Manage Questions";
require_once 'header.php';



$quiz_id = $_GET['quiz_id'] ?? 0;
$question_id = $_GET['id'] ?? 0;
$action = $_GET['action'] ?? 'list';

// Get quiz info
if ($quiz_id) {
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch();
}

if ($action === 'add' || $action === 'edit') {
    // Handle question creation/editing
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $question_text = sanitize($_POST['question_text']);
        $marks = (int)$_POST['marks'];
        $question_order = (int)$_POST['question_order'];
        $quiz_id = (int)$_POST['quiz_id'];
        
        if ($action === 'add') {
            $stmt = $pdo->prepare("
                INSERT INTO questions (quiz_id, question_text, marks, question_order) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$quiz_id, $question_text, $marks, $question_order]);
            $question_id = $pdo->lastInsertId();
            
            // Insert options
            $options = $_POST['options'];
            $is_correct = $_POST['is_correct'];
            
            foreach ($options as $index => $option_text) {
                if (trim($option_text) !== '') {
                    $stmt = $pdo->prepare("
                        INSERT INTO options (question_id, option_text, is_correct, option_order) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $question_id, 
                        sanitize($option_text), 
                        ($is_correct == $index) ? 1 : 0,
                        $index + 1
                    ]);
                }
            }
            
            header("Location: questions.php?quiz_id=$quiz_id&added=1");
            exit();
        } else {
            // Update question
            $stmt = $pdo->prepare("
                UPDATE questions SET question_text = ?, marks = ?, question_order = ? 
                WHERE id = ?
            ");
            $stmt->execute([$question_text, $marks, $question_order, $question_id]);
            
            // Delete old options and insert new ones
            $stmt = $pdo->prepare("DELETE FROM options WHERE question_id = ?");
            $stmt->execute([$question_id]);
            
            $options = $_POST['options'];
            $is_correct = $_POST['is_correct'];
            
            foreach ($options as $index => $option_text) {
                if (trim($option_text) !== '') {
                    $stmt = $pdo->prepare("
                        INSERT INTO options (question_id, option_text, is_correct, option_order) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $question_id, 
                        sanitize($option_text), 
                        ($is_correct == $index) ? 1 : 0,
                        $index + 1
                    ]);
                }
            }
            
            header("Location: questions.php?quiz_id=$quiz_id&updated=1");
            exit();
        }
    }
    
    $question = [];
    $options = [];
    if ($action === 'edit' && $question_id) {
        $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
        $stmt->execute([$question_id]);
        $question = $stmt->fetch();
        
        $stmt = $pdo->prepare("SELECT * FROM options WHERE question_id = ? ORDER BY option_order");
        $stmt->execute([$question_id]);
        $options = $stmt->fetchAll();
        
        $quiz_id = $question['quiz_id'];
        
        $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
        $stmt->execute([$quiz_id]);
        $quiz = $stmt->fetch();
    }
    ?>
    
    <div class="row">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="quizzes.php">Quizzes</a></li>
                    <li class="breadcrumb-item"><a href="questions.php?quiz_id=<?php echo $quiz_id; ?>">
                        <?php echo sanitize($quiz['title']); ?>
                    </a></li>
                    <li class="breadcrumb-item active">
                        <?php echo $action === 'add' ? 'Add Question' : 'Edit Question'; ?>
                    </li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-question me-2"></i>
                        <?php echo $action === 'add' ? 'Add New Question' : 'Edit Question'; ?>
                        <small class="float-end">Quiz: <?php echo sanitize($quiz['title']); ?></small>
                    </h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
                        
                        <div class="mb-4">
                            <h5>Question Details</h5>
                            <div class="mb-3">
                                <label for="question_text" class="form-label">Question Text *</label>
                                <textarea class="form-control" id="question_text" name="question_text" rows="3" required><?php echo $question['question_text'] ?? ''; ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="marks" class="form-label">Marks *</label>
                                    <input type="number" class="form-control" id="marks" name="marks" 
                                           value="<?php echo $question['marks'] ?? 10; ?>" required min="1">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="question_order" class="form-label">Question Order *</label>
                                    <input type="number" class="form-control" id="question_order" name="question_order" 
                                           value="<?php echo $question['question_order'] ?? 1; ?>" required min="1">
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5>Answer Options</h5>
                            <p class="text-muted">Select the correct answer by clicking the radio button.</p>
                            
                            <?php for ($i = 0; $i < 4; $i++): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-1 text-center">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" 
                                                       name="is_correct" value="<?php echo $i; ?>" 
                                                       id="correct<?php echo $i; ?>"
                                                       <?php 
                                                       if ($action === 'edit' && isset($options[$i]) && $options[$i]['is_correct']) {
                                                           echo 'checked';
                                                       } elseif ($i === 0 && $action === 'add') {
                                                           echo 'checked';
                                                       }
                                                       ?> required>
                                                <label class="form-check-label " for="correct<?php echo $i; ?>">
                                                    Correct
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-11">
                                            <label for="option<?php echo $i; ?>" class="form-label p-4">
                                                Option <?php echo $i + 1; ?> *
                                            </label>
                                            <input type="text" class="form-control" id="option<?php echo $i; ?>" 
                                                   name="options[<?php echo $i; ?>]" 
                                                   value="<?php echo $options[$i]['option_text'] ?? ''; ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endfor; ?>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="questions.php?quiz_id=<?php echo $quiz_id; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                <?php echo $action === 'add' ? 'Add Question' : 'Update Question'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php
} else {
    // List questions for a quiz
    if (!$quiz_id) {
        header("Location: quizzes.php");
        exit();
    }
    
    if (isset($_GET['delete'])) {
        $question_id = (int)$_GET['delete'];
        $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
        $stmt->execute([$question_id]);
        header("Location: questions.php?quiz_id=$quiz_id&deleted=1");
        exit();
    }
    
    $stmt = $pdo->prepare("
        SELECT q.*, 
               (SELECT COUNT(*) FROM options WHERE question_id = q.id) as option_count,
               (SELECT COUNT(*) FROM options WHERE question_id = q.id AND is_correct = 1) as correct_count
        FROM questions q 
        WHERE q.quiz_id = ? 
        ORDER BY q.question_order ASC
    ");
    $stmt->execute([$quiz_id]);
    $questions = $stmt->fetchAll();
    ?>
    
    <div class="row">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="quizzes.php">Quizzes</a></li>
                    <li class="breadcrumb-item active"><?php echo sanitize($quiz['title']); ?></li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-question me-2"></i>Manage Questions</h2>
            <p class="text-muted mb-0">Quiz: <strong><?php echo sanitize($quiz['title']); ?></strong></p>
        </div>
        <div>
            <a href="quizzes.php" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left me-2"></i>Back to Quizzes
            </a>
            <a href="?quiz_id=<?php echo $quiz_id; ?>&action=add" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add Question
            </a>
        </div>
    </div>
    
    <?php if (isset($_GET['added'])): ?>
        <div class="alert alert-success">Question added successfully!</div>
    <?php endif; ?>
    
    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">Question updated successfully!</div>
    <?php endif; ?>
    
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Question deleted successfully!</div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Quiz Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <p><strong>Title:</strong> <?php echo sanitize($quiz['title']); ?></p>
                </div>
                <div class="col-md-3">
                    <p><strong>Description:</strong> <?php echo sanitize($quiz['description'] ?? 'N/A'); ?></p>
                </div>
                <div class="col-md-2">
                    <p><strong>Total Marks:</strong> <?php echo $quiz['total_marks']; ?></p>
                </div>
                <div class="col-md-2">
                    <p><strong>Time Limit:</strong> <?php echo $quiz['time_limit']; ?> mins</p>
                </div>
                <div class="col-md-2">
                    <p><strong>Questions:</strong> <?php echo count($questions); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (empty($questions)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-question-circle fa-4x text-muted mb-3"></i>
            <h4>No Questions Yet</h4>
            <p class="text-muted">Add questions to make this quiz available for users.</p>
            <a href="?quiz_id=<?php echo $quiz_id; ?>&action=add" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add First Question
            </a>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Question</th>
                            <th>Options</th>
                            <th>Correct</th>
                            <th>Marks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questions as $question): 
                            $stmt = $pdo->prepare("SELECT * FROM options WHERE question_id = ? ORDER BY option_order");
                            $stmt->execute([$question['id']]);
                            $options = $stmt->fetchAll();
                        ?>
                        <tr>
                            <td>
                                <span class="badge bg-primary"><?php echo $question['question_order']; ?></span>
                            </td>
                            <td>
                                <strong><?php echo substr(sanitize($question['question_text']), 0, 80); ?>...</strong>
                            </td>
                            <td>
                                <ol class="mb-0 ps-3">
                                    <?php foreach ($options as $option): ?>
                                    <li>
                                        <?php echo substr(sanitize($option['option_text']), 0, 50); ?>
                                        <?php if ($option['is_correct']): ?>
                                            <span class="badge bg-success ms-1">Correct</span>
                                        <?php endif; ?>
                                    </li>
                                    <?php endforeach; ?>
                                </ol>
                            </td>
                            <td>
                                <span class="badge bg-success"><?php echo $question['correct_count']; ?>/<?php echo $question['option_count']; ?></span>
                            </td>
                            <td><?php echo $question['marks']; ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="?action=edit&id=<?php echo $question['id']; ?>" class="btn btn-outline-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?quiz_id=<?php echo $quiz_id; ?>&delete=<?php echo $question['id']; ?>" 
                                       class="btn btn-outline-danger" 
                                       onclick="return confirm('Delete this question?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Calculate total marks -->
            <?php
            $total_marks = array_sum(array_column($questions, 'marks'));
            $marks_diff = $quiz['total_marks'] - $total_marks;
            ?>
            
            <div class="alert alert-<?php echo ($marks_diff == 0) ? 'success' : (($marks_diff > 0) ? 'warning' : 'danger'); ?> mt-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Total Questions:</strong> <?php echo count($questions); ?> |
                        <strong>Quiz Total Marks:</strong> <?php echo $quiz['total_marks']; ?> |
                        <strong>Questions Total Marks:</strong> <?php echo $total_marks; ?>
                    </div>
                    <div>
                        <?php if ($marks_diff == 0): ?>
                            <span class="badge bg-success">Marks match perfectly!</span>
                        <?php elseif ($marks_diff > 0): ?>
                            <span class="badge bg-warning"><?php echo $marks_diff; ?> marks remaining</span>
                        <?php else: ?>
                            <span class="badge bg-danger"><?php echo abs($marks_diff); ?> marks over limit</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php
}

require_once 'footer.php';
?>