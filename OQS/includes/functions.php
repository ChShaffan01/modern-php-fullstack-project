<?php
require_once 'db.php';

class QuizFunctions {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getQuiz($id) {
        $stmt = $this->pdo->prepare("
            SELECT q.*, u.name as creator_name 
            FROM quizzes q 
            LEFT JOIN users u ON q.created_by = u.id 
            WHERE q.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getAllQuizzes() {
        $stmt = $this->pdo->query("
            SELECT q.*, u.name as creator_name, 
                   COUNT(qu.id) as question_count,
                   (SELECT COUNT(*) FROM results r WHERE r.quiz_id = q.id) as attempts
            FROM quizzes q 
            LEFT JOIN users u ON q.created_by = u.id 
            LEFT JOIN questions qu ON q.id = qu.quiz_id
            GROUP BY q.id
            ORDER BY q.created_at DESC
        ");
        return $stmt->fetchAll();
    }
    
    public function getQuizQuestions($quiz_id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM questions 
            WHERE quiz_id = ? 
            ORDER BY question_order ASC
        ");
        $stmt->execute([$quiz_id]);
        return $stmt->fetchAll();
    }
    
    public function getQuestionOptions($question_id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM options 
            WHERE question_id = ? 
            ORDER BY option_order ASC
        ");
        $stmt->execute([$question_id]);
        return $stmt->fetchAll();
    }
    
    public function calculateScore($quiz_id, $user_answers) {
        $score = 0;
        $questions = $this->getQuizQuestions($quiz_id);
        
        foreach ($questions as $question) {
            if (isset($user_answers[$question['id']])) {
                $selected_option_id = $user_answers[$question['id']];
                $stmt = $this->pdo->prepare("SELECT is_correct FROM options WHERE id = ?");
                $stmt->execute([$selected_option_id]);
                $option = $stmt->fetch();
                
                if ($option && $option['is_correct']) {
                    $score += $question['marks'];
                }
            }
        }
        
        return $score;
    }
    
    public function saveResult($user_id, $quiz_id, $score, $user_answers) {
        $this->pdo->beginTransaction();
        
        try {
            // Get total questions
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM questions WHERE quiz_id = ?");
            $stmt->execute([$quiz_id]);
            $total_questions = $stmt->fetch()['count'];
            
            // Calculate correct answers
            $correct_answers = 0;
            foreach ($user_answers as $question_id => $option_id) {
                $stmt = $this->pdo->prepare("SELECT is_correct FROM options WHERE id = ?");
                $stmt->execute([$option_id]);
                if ($stmt->fetch()['is_correct']) {
                    $correct_answers++;
                }
            }
            
            // Insert result
            $stmt = $this->pdo->prepare("
                INSERT INTO results (user_id, quiz_id, score, total_questions, correct_answers) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $quiz_id, $score, $total_questions, $correct_answers]);
            $result_id = $this->pdo->lastInsertId();
            
            // Save user answers
            foreach ($user_answers as $question_id => $option_id) {
                $is_correct = false;
                $stmt = $this->pdo->prepare("SELECT is_correct FROM options WHERE id = ?");
                $stmt->execute([$option_id]);
                $option = $stmt->fetch();
                if ($option) {
                    $is_correct = $option['is_correct'];
                }
                
                $stmt = $this->pdo->prepare("
                    INSERT INTO user_answers (result_id, question_id, option_id, is_correct) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$result_id, $question_id, $option_id, $is_correct]);
            }
            
            $this->pdo->commit();
            return $result_id;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
    
    public function getUserResults($user_id) {
        $stmt = $this->pdo->prepare("
            SELECT r.*, q.title as quiz_title, q.total_marks,
                   (r.score / q.total_marks * 100) as percentage
            FROM results r 
            JOIN quizzes q ON r.quiz_id = q.id 
            WHERE r.user_id = ? 
            ORDER BY r.submitted_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
}

$quizFunctions = new QuizFunctions($pdo);
// Helper function for safe redirects
function safe_redirect($url) {
    if (headers_sent()) {
        echo '<script>window.location.href = "' . $url . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . $url . '"></noscript>';
    } else {
        header("Location: " . $url);
    }
    exit();
}

// Helper function to check if user is admin
function require_admin_access() {
    global $auth;
    if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
        safe_redirect('../dashboard.php');
    }
}

// Helper function to require login
function require_login() {
    global $auth;
    if (!$auth->isLoggedIn()) {
        safe_redirect('login.php');
    }
}
?>