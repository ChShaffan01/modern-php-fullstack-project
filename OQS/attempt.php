<?php
// Start session and output buffering at the very beginning
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Create instances
$pdo = $GLOBALS['pdo']; // Get PDO instance from db.php
$auth = new Auth($pdo);
$quizFunctions = new QuizFunctions($pdo);

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    if (headers_sent()) {
        echo '<script>window.location.href = "login.php";</script>';
        exit();
    } else {
        header("Location: login.php");
        exit();
    }
}

$page_title = "Attempt Quiz";

// Check if quiz ID is provided
if (!isset($_GET['id'])) {
    if (headers_sent()) {
        echo '<script>window.location.href = "quiz.php";</script>';
        exit();
    } else {
        header("Location: quiz.php");
        exit();
    }
}

$quiz_id = (int)$_GET['id'];
$quiz = $quizFunctions->getQuiz($quiz_id);
$questions = $quizFunctions->getQuizQuestions($quiz_id);

// Check if quiz exists
if (!$quiz) {
    if (headers_sent()) {
        echo '<script>window.location.href = "quiz.php";</script>';
        exit();
    } else {
        header("Location: quiz.php");
        exit();
    }
}

// Check if quiz is already submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_answers = $_POST['answers'] ?? [];
    $score = $quizFunctions->calculateScore($quiz_id, $user_answers);
    $result_id = $quizFunctions->saveResult($_SESSION['user_id'], $quiz_id, $score, $user_answers);
    
    if ($result_id) {
        // Clear any output buffers before redirect
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        if (headers_sent()) {
            echo '<script>window.location.href = "result.php?id=' . $result_id . '";</script>';
            exit();
        } else {
            header("Location: result.php?id=" . $result_id);
            exit();
        }
    } else {
        $error = "Failed to submit quiz. Please try again.";
    }
}

// Now include the header AFTER all processing
require_once 'includes/header.php';

// Show error if exists
if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-pencil-alt me-2"></i><?php echo sanitize($quiz['title']); ?></h4>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <div class="row">
                        <div class="col-md-4">
                            <i class="fas fa-clock me-1"></i>
                            <strong>Time Limit:</strong> <?php echo $quiz['time_limit']; ?> minutes
                        </div>
                        <div class="col-md-4">
                            <i class="fas fa-question-circle me-1"></i>
                            <strong>Questions:</strong> <?php echo count($questions); ?>
                        </div>
                        <div class="col-md-4">
                            <i class="fas fa-trophy me-1"></i>
                            <strong>Total Points:</strong> <?php echo $quiz['total_marks']; ?>
                        </div>
                    </div>
                </div>
                
                <form method="POST" action="" id="quizForm">
                    <?php foreach ($questions as $index => $question): 
                        $options = $quizFunctions->getQuestionOptions($question['id']);
                    ?>
                    <div class="question-card mb-4 p-3 border rounded">
                        <h5 class="mb-3">
                            <span class="badge bg-primary me-2"><?php echo $index + 1; ?></span>
                            <?php echo sanitize($question['question_text']); ?>
                            <small class="text-muted float-end"><?php echo $question['marks']; ?> points</small>
                        </h5>
                        
                        <div class="options">
                            <?php foreach ($options as $option): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" 
                                       name="answers[<?php echo $question['id']; ?>]" 
                                       id="option_<?php echo $option['id']; ?>"
                                       value="<?php echo $option['id']; ?>" required>
                                <label class="form-check-label" for="option_<?php echo $option['id']; ?>">
                                    <?php echo sanitize($option['option_text']); ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-success btn-lg px-5">
                            <i class="fas fa-paper-plane me-2"></i>Submit Quiz
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-lg ms-2" onclick="confirmReset()">
                            <i class="fas fa-redo me-2"></i>Reset Answers
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Sidebar with quiz info and timer -->
    <div class="col-lg-4">
        <div class="card sticky-top" style="top: 20px;">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Quiz Information</h5>
            </div>
            <div class="card-body">
                <h6>Instructions:</h6>
                <ul class="small">
                    <li>Answer all questions</li>
                    <li>Each question has only one correct answer</li>
                    <li>You cannot change answers after submission</li>
                    <li>Time limit: <?php echo $quiz['time_limit']; ?> minutes</li>
                </ul>
                
                <hr>
                
                <div class="text-center">
                    <div class="display-4" id="timer">
                        <?php echo str_pad($quiz['time_limit'], 2, '0', STR_PAD_LEFT); ?>:00
                    </div>
                    <small class="text-muted">Time Remaining</small>
                </div>
                
                <hr>
                
                <h6>Question Navigation:</h6>
                <div class="question-navigation">
                    <div class="row row-cols-4 g-2">
                        <?php foreach ($questions as $index => $question): ?>
                        <div class="col">
                            <button type="button" style="margin: 40px;" class="btn btn-outline-primary btn-sm w-100" 
                                    onclick="scrollToQuestion(<?php echo $index; ?>)">
                                <?php echo $index + 1; ?>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="mt-3">
                    <div class="progress">
                        <div class="progress-bar" id="progressBar" role="progressbar" style="width: 0%"></div>
                    </div>
                    <small class="text-muted d-block text-center mt-1">
                        <span id="answeredCount">0</span> of <?php echo count($questions); ?> answered
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Timer functionality
let totalMinutes = <?php echo $quiz['time_limit']; ?>;
let totalSeconds = totalMinutes * 60;
let timerInterval;

function startTimer() {
    timerInterval = setInterval(function() {
        totalSeconds--;
        let minutes = Math.floor(totalSeconds / 60);
        let seconds = totalSeconds % 60;
        
        document.getElementById('timer').textContent = 
            `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        // Update progress bar
        let timeElapsed = (<?php echo $quiz['time_limit']; ?> * 60 - totalSeconds);
        let totalTime = <?php echo $quiz['time_limit']; ?> * 60;
        let progressPercent = (timeElapsed / totalTime) * 100;
        document.getElementById('progressBar').style.width = `${progressPercent}%`;
        
        if (totalSeconds <= 0) {
            clearInterval(timerInterval);
            alert('Time is up! Submitting your quiz...');
            document.getElementById('quizForm').submit();
        }
    }, 1000);
}

// Update answered count
function updateAnsweredCount() {
    let answered = document.querySelectorAll('input[type="radio"]:checked').length;
    document.getElementById('answeredCount').textContent = answered;
    
    // Update question navigation buttons
    document.querySelectorAll('.question-navigation button').forEach((btn, index) => {
        let questionNumber = index + 1;
        let questionId = <?php echo json_encode(array_column($questions, 'id')); ?>[index];
        let isAnswered = document.querySelector(`input[name="answers[${questionId}]"]:checked`);
        
        if (isAnswered) {
            btn.classList.remove('btn-outline-primary');
            btn.classList.add('btn-primary');
        } else {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-primary');
        }
    });
}

// Scroll to question
function scrollToQuestion(index) {
    let questions = document.querySelectorAll('.question-card');
    if (questions[index]) {
        questions[index].scrollIntoView({ behavior: 'smooth', block: 'start' });
        questions[index].classList.add('border-primary');
        setTimeout(() => {
            questions[index].classList.remove('border-primary');
        }, 2000);
    }
}

// Confirm reset
function confirmReset() {
    if (confirm('Are you sure you want to reset all answers?')) {
        document.querySelectorAll('input[type="radio"]').forEach(input => {
            input.checked = false;
        });
        updateAnsweredCount();
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    startTimer();
    updateAnsweredCount();
    
    // Update count when answers change
    document.querySelectorAll('input[type="radio"]').forEach(input => {
        input.addEventListener('change', updateAnsweredCount);
    });
    
    // Auto-save answers every 30 seconds (optional)
    setInterval(function() {
        let formData = new FormData(document.getElementById('quizForm'));
        localStorage.setItem('quiz_answers', JSON.stringify(Object.fromEntries(formData)));
    }, 30000);
    
    // Load saved answers (if any)
    let savedAnswers = localStorage.getItem('quiz_answers');
    if (savedAnswers) {
        savedAnswers = JSON.parse(savedAnswers);
        for (let question in savedAnswers.answers) {
            let input = document.querySelector(`input[name="answers[${question}]"][value="${savedAnswers.answers[question]}"]`);
            if (input) input.checked = true;
        }
        updateAnsweredCount();
    }
    
    // Clear saved answers on submit
    document.getElementById('quizForm').addEventListener('submit', function() {
        localStorage.removeItem('quiz_answers');
    });
});

// Warn before leaving page
window.addEventListener('beforeunload', function(e) {
    let answered = document.querySelectorAll('input[type="radio"]:checked').length;
    if (answered > 0) {
        e.preventDefault();
        e.returnValue = 'You have unsaved answers. Are you sure you want to leave?';
    }
});
</script>

<?php 
// End with footer
require_once 'includes/footer.php'; 
?>