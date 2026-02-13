<?php
ob_start();

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include initialization FIRST
require_once 'includes/init.php';

// Now set the page title
$page_title = "Home";

// Now include header AFTER $auth is initialized
require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section rounded-3">
    <div class="container text-center">
        <h1 class="display-4 fw-bold mb-4">Test Your Knowledge with QuizMaster</h1>
        <p class="lead mb-4">Challenge yourself with our interactive quizzes. Learn, compete, and track your progress.</p>
        <?php if (!$isLoggedIn): ?>
            <div class="mt-4">
                <a href="register.php" class="btn btn-light btn-lg me-3">Get Started</a>
                <a href="login.php" class="btn btn-outline-light btn-lg">Login</a>
            </div>
        <?php else: ?>
            <a href="dashboard.php" class="btn btn-light btn-lg">Go to Dashboard</a>
        <?php endif; ?>
    </div>
</section>

<!-- Features Section -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Why Choose QuizMaster?</h2>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <div class="feature-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h4>Wide Range of Topics</h4>
                    <p>From general knowledge to specialized subjects, we have quizzes for everyone.</p>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h4>Track Your Progress</h4>
                    <p>Monitor your improvement with detailed results and performance analytics.</p>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <div class="feature-icon">
                        <i class="fas fa-award"></i>
                    </div>
                    <h4>Compete & Earn</h4>
                    <p>Challenge other users and earn badges based on your performance.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Recent Quizzes -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Popular Quizzes</h2>
        
        <div class="row g-4">
            <?php
            // Use $quizFunctions from init.php
            $quizzes = $quizFunctions->getAllQuizzes();
            $recentQuizzes = array_slice($quizzes, 0, 3);
            
            foreach ($recentQuizzes as $quiz): 
            ?>
            <div class="col-md-4">
                <div class="card quiz-card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo sanitize($quiz['title']); ?></h5>
                        <p class="card-text text-muted"><?php echo substr(sanitize($quiz['description'] ?? 'No description'), 0, 100); ?>...</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-question-circle me-1"></i>
                                <?php echo $quiz['question_count']; ?> Questions
                            </small>
                            <small class="text-muted">
                                <i class="fas fa-users me-1"></i>
                                <?php echo $quiz['attempts']; ?> Attempts
                            </small>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <?php if ($isLoggedIn): ?>
                            <a href="quiz.php?action=attempt&id=<?php echo $quiz['id']; ?>" class="btn btn-primary btn-sm">Take Quiz</a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-outline-primary btn-sm">Login to Attempt</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($isLoggedIn): ?>
            <div class="text-center mt-4">
                <a href="quizzes.php" class="btn btn-primary">View All Quizzes</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Stats -->
<section class="py-5">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3">
                <h3 class="display-6 fw-bold text-primary"><?php echo count($quizzes); ?></h3>
                <p class="text-muted">Active Quizzes</p>
            </div>
            <div class="col-md-3">
                <h3 class="display-6 fw-bold text-primary">
                    <?php 
                    $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) as users FROM results");
                    echo $stmt->fetch()['users'] ?? 0;
                    ?>
                </h3>
                <p class="text-muted">Active Users</p>
            </div>
            <div class="col-md-3">
                <h3 class="display-6 fw-bold text-primary">
                    <?php 
                    $stmt = $pdo->query("SELECT COUNT(*) as attempts FROM results");
                    echo $stmt->fetch()['attempts'] ?? 0;
                    ?>
                </h3>
                <p class="text-muted">Quiz Attempts</p>
            </div>
            <div class="col-md-3">
                <h3 class="display-6 fw-bold text-primary">
                    <?php 
                    $stmt = $pdo->query("SELECT COUNT(*) as questions FROM questions");
                    echo $stmt->fetch()['questions'] ?? 0;
                    ?>
                </h3>
                <p class="text-muted">Total Questions</p>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>