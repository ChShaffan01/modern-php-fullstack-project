/**
 * QUIZMASTER - QUIZ SPECIFIC JAVASCRIPT
 * Contains quiz-specific functionality
 */

// ===== QUIZ STATE MANAGEMENT =====
class QuizManager {
    constructor(options = {}) {
        this.quizId = options.quizId || null;
        this.timeLimit = options.timeLimit || 30; // in minutes
        this.totalQuestions = options.totalQuestions || 0;
        this.currentQuestion = 1;
        
        this.userAnswers = {};
        this.markedQuestions = new Set();
        this.visitedQuestions = new Set();
        
        this.startTime = null;
        this.timerInterval = null;
        this.timeRemaining = this.timeLimit * 60; // in seconds
        
        this.isSubmitting = false;
        this.autoSaveInterval = null;
        
        this.init();
    }
    
    init() {
        this.loadState();
        this.setupTimer();
        this.setupNavigation();
        this.setupEventListeners();
        this.setupAutoSave();
        this.setupKeyboardShortcuts();
        this.updateUI();
    }
    
    // ===== TIMER MANAGEMENT =====
    setupTimer() {
        this.startTime = new Date();
        this.timeRemaining = this.timeLimit * 60;
        
        const timerDisplay = document.querySelector('.timer-display');
        const progressBar = document.querySelector('.timer-progress-bar');
        const statusElement = document.querySelector('.timer-status');
        
        if (!timerDisplay) return;
        
        this.timerInterval = setInterval(() => {
            this.timeRemaining--;
            
            // Update display
            const minutes = Math.floor(this.timeRemaining / 60);
            const seconds = this.timeRemaining % 60;
            timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            // Update progress bar
            if (progressBar) {
                const elapsed = (this.timeLimit * 60) - this.timeRemaining;
                const progress = (elapsed / (this.timeLimit * 60)) * 100;
                progressBar.style.width = `${progress}%`;
                
                // Update color based on time
                if (this.timeRemaining <= 300) { // 5 minutes
                    progressBar.style.background = 'linear-gradient(90deg, var(--danger-color), var(--accent-color))';
                    timerDisplay.classList.add('text-danger');
                    
                    if (statusElement) {
                        statusElement.textContent = 'Hurry up! Time is running out.';
                        statusElement.classList.add('danger');
                    }
                } else if (this.timeRemaining <= 600) { // 10 minutes
                    progressBar.style.background = 'linear-gradient(90deg, var(--warning-color), var(--danger-color))';
                    timerDisplay.classList.add('text-warning');
                    
                    if (statusElement) {
                        statusElement.textContent = 'Time is getting low.';
                        statusElement.classList.add('warning');
                    }
                }
            }
            
            // Auto-submit when time is up
            if (this.timeRemaining <= 0) {
                this.submitQuiz();
            }
        }, 1000);
    }
    
    // ===== NAVIGATION =====
    setupNavigation() {
        // Question number buttons
        document.querySelectorAll('.question-nav-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const questionNum = parseInt(btn.dataset.question);
                this.goToQuestion(questionNum);
            });
        });
        
        // Previous button
        const prevBtn = document.querySelector('.btn-prev');
        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                this.goToQuestion(this.currentQuestion - 1);
            });
        }
        
        // Next button
        const nextBtn = document.querySelector('.btn-next');
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                this.goToQuestion(this.currentQuestion + 1);
            });
        }
        
        // First button
        const firstBtn = document.querySelector('.btn-first');
        if (firstBtn) {
            firstBtn.addEventListener('click', () => {
                this.goToQuestion(1);
            });
        }
        
        // Last button
        const lastBtn = document.querySelector('.btn-last');
        if (lastBtn) {
            lastBtn.addEventListener('click', () => {
                this.goToQuestion(this.totalQuestions);
            });
        }
    }
    
    goToQuestion(questionNum) {
        if (questionNum < 1 || questionNum > this.totalQuestions) return;
        
        // Hide current question
        const currentCard = document.querySelector(`.question-card[data-question="${this.currentQuestion}"]`);
        if (currentCard) {
            currentCard.classList.remove('current');
        }
        
        // Show new question
        const newCard = document.querySelector(`.question-card[data-question="${questionNum}"]`);
        if (newCard) {
            newCard.classList.add('current');
            newCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            // Mark as visited
            this.visitedQuestions.add(questionNum);
            
            // Update current question
            this.currentQuestion = questionNum;
            
            // Update navigation UI
            this.updateNavigationUI();
        }
    }
    
    updateNavigationUI() {
        // Update nav buttons
        document.querySelectorAll('.question-nav-btn').forEach(btn => {
            const questionNum = parseInt(btn.dataset.question);
            btn.classList.remove('current');
            
            if (questionNum === this.currentQuestion) {
                btn.classList.add('current');
            }
            
            // Update answered status
            if (this.userAnswers[questionNum]) {
                btn.classList.add('answered');
            } else {
                btn.classList.remove('answered');
            }
            
            // Update marked status
            if (this.markedQuestions.has(questionNum)) {
                btn.classList.add('marked');
            } else {
                btn.classList.remove('marked');
            }
        });
        
        // Update prev/next button states
        const prevBtn = document.querySelector('.btn-prev');
        const nextBtn = document.querySelector('.btn-next');
        const firstBtn = document.querySelector('.btn-first');
        const lastBtn = document.querySelector('.btn-last');
        
        if (prevBtn) prevBtn.disabled = this.currentQuestion === 1;
        if (nextBtn) nextBtn.disabled = this.currentQuestion === this.totalQuestions;
        if (firstBtn) firstBtn.disabled = this.currentQuestion === 1;
        if (lastBtn) lastBtn.disabled = this.currentQuestion === this.totalQuestions;
        
        // Update question counter
        const counter = document.querySelector('.question-counter');
        if (counter) {
            counter.textContent = `Question ${this.currentQuestion} of ${this.totalQuestions}`;
        }
    }
    
    // ===== ANSWER MANAGEMENT =====
    setupEventListeners() {
        // Radio button changes
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                const questionNum = this.getQuestionNumberFromInput(radio);
                const answer = radio.value;
                
                this.saveAnswer(questionNum, answer);
                this.updateQuestionStatus(questionNum);
            });
        });
        
        // Mark for review
        document.querySelectorAll('.btn-mark').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleMarkQuestion(this.currentQuestion);
            });
        });
        
        // Clear answer
        document.querySelectorAll('.btn-clear').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.clearAnswer(this.currentQuestion);
            });
        });
        
        // Submit button
        const submitBtn = document.querySelector('.btn-submit');
        if (submitBtn) {
            submitBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.confirmSubmit();
            });
        }
        
        // Save button
        const saveBtn = document.querySelector('.btn-save');
        if (saveBtn) {
            saveBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.saveState();
                QuizMaster.showToast('Progress saved', 'success');
            });
        }
    }
    
    getQuestionNumberFromInput(input) {
        const name = input.name;
        const match = name.match(/\[(\d+)\]/);
        return match ? parseInt(match[1]) : null;
    }
    
    saveAnswer(questionNum, answer) {
        this.userAnswers[questionNum] = answer;
        this.updateNavigationUI();
    }
    
    clearAnswer(questionNum) {
        delete this.userAnswers[questionNum];
        
        // Uncheck radio buttons
        document.querySelectorAll(`input[name^="answers[${questionNum}]"]`).forEach(radio => {
            radio.checked = false;
        });
        
        this.updateQuestionStatus(questionNum);
        this.updateNavigationUI();
    }
    
    toggleMarkQuestion(questionNum) {
        if (this.markedQuestions.has(questionNum)) {
            this.markedQuestions.delete(questionNum);
            QuizMaster.showToast('Question unmarked', 'info');
        } else {
            this.markedQuestions.add(questionNum);
            QuizMaster.showToast('Question marked for review', 'warning');
        }
        
        this.updateNavigationUI();
    }
    
    updateQuestionStatus(questionNum) {
        const questionCard = document.querySelector(`.question-card[data-question="${questionNum}"]`);
        if (questionCard) {
            if (this.userAnswers[questionNum]) {
                questionCard.classList.add('answered');
            } else {
                questionCard.classList.remove('answered');
            }
        }
    }
    
    // ===== STATE MANAGEMENT =====
    setupAutoSave() {
        // Auto-save every 30 seconds
        this.autoSaveInterval = setInterval(() => {
            if (Object.keys(this.userAnswers).length > 0) {
                this.saveState();
            }
        }, 30000);
        
        // Save on page unload
        window.addEventListener('beforeunload', (e) => {
            if (Object.keys(this.userAnswers).length > 0 && !this.isSubmitting) {
                this.saveState();
                e.preventDefault();
                e.returnValue = 'You have unsaved quiz progress. Are you sure you want to leave?';
            }
        });
    }
    
    saveState() {
        const state = {
            userAnswers: this.userAnswers,
            markedQuestions: Array.from(this.markedQuestions),
            visitedQuestions: Array.from(this.visitedQuestions),
            currentQuestion: this.currentQuestion,
            startTime: this.startTime,
            timeRemaining: this.timeRemaining,
            lastSaved: new Date()
        };
        
        localStorage.setItem(`quiz_state_${this.quizId}`, JSON.stringify(state));
        
        // Update save indicator
        const saveIndicator = document.querySelector('.save-indicator');
        if (saveIndicator) {
            saveIndicator.textContent = `Last saved: ${new Date().toLocaleTimeString()}`;
            saveIndicator.classList.add('text-success');
            
            setTimeout(() => {
                saveIndicator.classList.remove('text-success');
            }, 2000);
        }
    }
    
    loadState() {
        const savedState = localStorage.getItem(`quiz_state_${this.quizId}`);
        
        if (savedState) {
            try {
                const state = JSON.parse(savedState);
                
                // Restore answers
                if (state.userAnswers) {
                    this.userAnswers = state.userAnswers;
                    
                    // Check radio buttons
                    Object.entries(this.userAnswers).forEach(([questionNum, answer]) => {
                        const radio = document.querySelector(`input[name="answers[${questionNum}]"][value="${answer}"]`);
                        if (radio) {
                            radio.checked = true;
                            this.updateQuestionStatus(parseInt(questionNum));
                        }
                    });
                }
                
                // Restore marked questions
                if (state.markedQuestions) {
                    this.markedQuestions = new Set(state.markedQuestions);
                }
                
                // Restore visited questions
                if (state.visitedQuestions) {
                    this.visitedQuestions = new Set(state.visitedQuestions);
                }
                
                // Restore current question
                if (state.currentQuestion) {
                    this.currentQuestion = state.currentQuestion;
                }
                
                // Restore time
                if (state.timeRemaining) {
                    this.timeRemaining = state.timeRemaining;
                }
                
                // Show restore notification
                this.showRestoreNotification(state.lastSaved);
                
            } catch (error) {
                console.error('Failed to load saved state:', error);
            }
        }
    }
    
    showRestoreNotification(lastSaved) {
        const notification = document.createElement('div');
        notification.className = 'alert alert-info alert-dismissible fade show';
        notification.innerHTML = `
            <i class="fas fa-history me-2"></i>
            Found saved progress from ${new Date(lastSaved).toLocaleString()}.
            <button type="button" class="btn btn-sm btn-outline-light ms-2" id="restoreQuiz">
                Restore
            </button>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.querySelector('.container');
        if (container) {
            container.insertBefore(notification, container.firstChild);
            
            // Add restore button event listener
            document.getElementById('restoreQuiz').addEventListener('click', () => {
                location.reload();
            });
        }
    }
    
    // ===== KEYBOARD SHORTCUTS =====
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Don't trigger in input fields
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            
            switch(e.key) {
                case 'ArrowLeft':
                case 'p':
                    e.preventDefault();
                    this.goToQuestion(this.currentQuestion - 1);
                    break;
                    
                case 'ArrowRight':
                case 'n':
                    e.preventDefault();
                    this.goToQuestion(this.currentQuestion + 1);
                    break;
                    
                case 'm':
                    e.preventDefault();
                    this.toggleMarkQuestion(this.currentQuestion);
                    break;
                    
                case 'c':
                    e.preventDefault();
                    this.clearAnswer(this.currentQuestion);
                    break;
                    
                case '1':
                case '2':
                case '3':
                case '4':
                    if (e.altKey) {
                        e.preventDefault();
                        this.selectOption(parseInt(e.key));
                    }
                    break;
                    
                case 'Enter':
                    if (e.ctrlKey) {
                        e.preventDefault();
                        this.confirmSubmit();
                    }
                    break;
                    
                case '?':
                    e.preventDefault();
                    this.showShortcutsHelp();
                    break;
            }
        });
    }
    
    selectOption(optionNumber) {
        const radio = document.querySelector(
            `.question-card.current input[type="radio"]:nth-child(${optionNumber})`
        );
        if (radio) {
            radio.checked = true;
            radio.dispatchEvent(new Event('change'));
        }
    }
    
    showShortcutsHelp() {
        const shortcuts = [
            { key: '← or P', action: 'Previous question' },
            { key: '→ or N', action: 'Next question' },
            { key: 'M', action: 'Mark/unmark question' },
            { key: 'C', action: 'Clear answer' },
            { key: 'Alt + 1-4', action: 'Select option 1-4' },
            { key: 'Ctrl + Enter', action: 'Submit quiz' },
            { key: '?', action: 'Show keyboard shortcuts' }
        ];
        
        let html = '<h6 class="mb-3">Keyboard Shortcuts</h6>';
        shortcuts.forEach(shortcut => {
            html += `
                <div class="d-flex justify-content-between mb-2">
                    <kbd class="bg-dark">${shortcut.key}</kbd>
                    <span class="text-muted">${shortcut.action}</span>
                </div>
            `;
        });
        
        QuizMaster.showToast(html, 'info', 5000);
    }
    
    // ===== SUBMISSION =====
    confirmSubmit() {
        const answeredCount = Object.keys(this.userAnswers).length;
        const unansweredCount = this.totalQuestions - answeredCount;
        const markedCount = this.markedQuestions.size;
        
        let message = `You have answered ${answeredCount} out of ${this.totalQuestions} questions.`;
        
        if (unansweredCount > 0) {
            message += `\n${unansweredCount} questions remain unanswered.`;
        }
        
        if (markedCount > 0) {
            message += `\n${markedCount} questions are marked for review.`;
        }
        
        message += '\n\nAre you sure you want to submit?';
        
        if (confirm(message)) {
            this.submitQuiz();
        }
    }
    
    submitQuiz() {
        this.isSubmitting = true;
        
        // Clear intervals
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
        }
        
        if (this.autoSaveInterval) {
            clearInterval(this.autoSaveInterval);
        }
        
        // Clear saved state
        localStorage.removeItem(`quiz_state_${this.quizId}`);
        
        // Remove beforeunload listener
        window.removeEventListener('beforeunload', arguments.callee);
        
        // Show loading state
        const submitBtn = document.querySelector('.btn-submit');
        if (submitBtn) {
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';
            submitBtn.disabled = true;
        }
        
        // Submit the form
        const quizForm = document.getElementById('quizForm');
        if (quizForm) {
            quizForm.submit();
        } else {
            // If no form, show completion message
            this.showCompletion();
        }
    }
    
    showCompletion() {
        const answeredCount = Object.keys(this.userAnswers).length;
        const score = Math.round((answeredCount / this.totalQuestions) * 100);
        
        const completionHTML = `
            <div class="completion-screen">
                <div class="completion-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 class="completion-title">Quiz Submitted!</h2>
                <p class="completion-message">
                    You answered ${answeredCount} out of ${this.totalQuestions} questions.
                </p>
                <div class="completion-stats">
                    <div class="stat-row">
                        <span class="stat-label">Estimated Score</span>
                        <span class="stat-value high">${score}%</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Time Taken</span>
                        <span class="stat-value">${this.formatTime((this.timeLimit * 60) - this.timeRemaining)}</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Questions Answered</span>
                        <span class="stat-value">${answeredCount}</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Questions Marked</span>
                        <span class="stat-value">${this.markedQuestions.size}</span>
                    </div>
                </div>
                <div class="completion-actions mt-4">
                    <a href="results.php" class="btn btn-primary me-2">
                        <i class="fas fa-chart-bar me-2"></i>View Results
                    </a>
                    <a href="quiz.php" class="btn btn-outline-primary">
                        <i class="fas fa-redo me-2"></i>Take Another Quiz
                    </a>
                </div>
            </div>
        `;
        
        const quizContainer = document.querySelector('.quiz-container');
        if (quizContainer) {
            quizContainer.innerHTML = completionHTML;
        }
    }
    
    formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
    }
    
    // ===== UTILITY METHODS =====
    updateUI() {
        this.updateNavigationUI();
        this.updateProgress();
        this.updateSummary();
    }
    
    updateProgress() {
        const answeredCount = Object.keys(this.userAnswers).length;
        const progressPercent = (answeredCount / this.totalQuestions) * 100;
        
        const progressBar = document.querySelector('.quiz-progress-bar');
        if (progressBar) {
            progressBar.style.width = `${progressPercent}%`;
        }
        
        const progressText = document.querySelector('.progress-text');
        if (progressText) {
            progressText.textContent = `${answeredCount}/${this.totalQuestions}`;
        }
    }
    
    updateSummary() {
        const answeredCount = Object.keys(this.userAnswers).length;
        const unansweredCount = this.totalQuestions - answeredCount;
        const markedCount = this.markedQuestions.size;
        
        // Update summary elements
        const answeredElement = document.querySelector('.summary-answered .count');
        const unansweredElement = document.querySelector('.summary-unanswered .count');
        const markedElement = document.querySelector('.summary-marked .count');
        
        if (answeredElement) answeredElement.textContent = answeredCount;
        if (unansweredElement) unansweredElement.textContent = unansweredCount;
        if (markedElement) markedElement.textContent = markedCount;
    }
}

// ===== QUIZ REVIEW FUNCTIONS =====
function initQuizReview() {
    // Expand/collapse all buttons
    const expandAllBtn = document.querySelector('.btn-expand-all');
    const collapseAllBtn = document.querySelector('.btn-collapse-all');
    
    if (expandAllBtn) {
        expandAllBtn.addEventListener('click', () => {
            document.querySelectorAll('.accordion-button').forEach(btn => {
                if (btn.classList.contains('collapsed')) {
                    btn.click();
                }
            });
        });
    }
    
    if (collapseAllBtn) {
        collapseAllBtn.addEventListener('click', () => {
            document.querySelectorAll('.accordion-button').forEach(btn => {
                if (!btn.classList.contains('collapsed')) {
                    btn.click();
                }
            });
        });
    }
    
    // Show correct answers only
    const showCorrectBtn = document.querySelector('.btn-show-correct');
    if (showCorrectBtn) {
        showCorrectBtn.addEventListener('click', () => {
            document.querySelectorAll('.accordion-item').forEach(item => {
                const isCorrect = item.querySelector('.fa-check');
                if (isCorrect) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
    
    // Show incorrect answers only
    const showIncorrectBtn = document.querySelector('.btn-show-incorrect');
    if (showIncorrectBtn) {
        showIncorrectBtn.addEventListener('click', () => {
            document.querySelectorAll('.accordion-item').forEach(item => {
                const isIncorrect = item.querySelector('.fa-times');
                if (isIncorrect) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
    
    // Show all answers
    const showAllBtn = document.querySelector('.btn-show-all');
    if (showAllBtn) {
        showAllBtn.addEventListener('click', () => {
            document.querySelectorAll('.accordion-item').forEach(item => {
                item.style.display = 'block';
            });
        });
    }
    
    // Print results
    const printBtn = document.querySelector('.btn-print');
    if (printBtn) {
        printBtn.addEventListener('click', () => {
            window.print();
        });
    }
}

// ===== QUIZ STATISTICS =====
function initQuizStatistics() {
    const chartElements = document.querySelectorAll('.result-chart');
    
    chartElements.forEach(chartElement => {
        const ctx = chartElement.getContext('2d');
        const correct = parseFloat(chartElement.dataset.correct) || 0;
        const incorrect = parseFloat(chartElement.dataset.incorrect) || 0;
        const unanswered = parseFloat(chartElement.dataset.unanswered) || 0;
        
        // Create pie chart
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Correct', 'Incorrect', 'Unanswered'],
                datasets: [{
                    data: [correct, incorrect, unanswered],
                    backgroundColor: [
                        'var(--success-color)',
                        'var(--danger-color)',
                        'var(--light-gray)'
                    ],
                    borderWidth: 2,
                    borderColor: 'var(--card-bg)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: {
                                family: 'var(--font-primary)',
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '70%'
            }
        });
    });
}

// ===== INITIALIZE QUIZ =====
document.addEventListener('DOMContentLoaded', function() {
    // Initialize quiz manager if on quiz page
    const quizContainer = document.querySelector('.quiz-container');
    if (quizContainer) {
        const quizId = quizContainer.dataset.quizId;
        const timeLimit = parseInt(quizContainer.dataset.timeLimit) || 30;
        const totalQuestions = parseInt(quizContainer.dataset.totalQuestions) || 0;
        
        window.quizManager = new QuizManager({
            quizId: quizId,
            timeLimit: timeLimit,
            totalQuestions: totalQuestions
        });
    }
    
    // Initialize quiz review if on result page
    if (document.querySelector('.review-panel')) {
        initQuizReview();
    }
    
    // Initialize statistics if on result page
    if (document.querySelector('.result-chart')) {
        initQuizStatistics();
    }
    
    // Initialize quiz filters
    const quizFilter = document.getElementById('quizFilter');
    if (quizFilter) {
        quizFilter.addEventListener('input', QuizMaster.debounce(function() {
            filterQuizzes(this.value);
        }, 300));
    }
    
    // Initialize quiz sorting
    const quizSort = document.getElementById('quizSort');
    if (quizSort) {
        quizSort.addEventListener('change', function() {
            sortQuizzes(this.value);
        });
    }
});

// ===== QUIZ FILTERING =====
function filterQuizzes(searchTerm) {
    const quizCards = document.querySelectorAll('.quiz-card');
    const searchLower = searchTerm.toLowerCase();
    
    quizCards.forEach(card => {
        const title = card.querySelector('.card-title').textContent.toLowerCase();
        const description = card.querySelector('.card-text').textContent.toLowerCase();
        const tags = card.dataset.tags ? card.dataset.tags.toLowerCase() : '';
        
        if (title.includes(searchLower) || 
            description.includes(searchLower) || 
            tags.includes(searchLower)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// ===== QUIZ SORTING =====
function sortQuizzes(sortBy) {
    const quizContainer = document.querySelector('.row');
    const quizCards = Array.from(document.querySelectorAll('.quiz-card'));
    
    quizCards.sort((a, b) => {
        switch(sortBy) {
            case 'title-asc':
                return a.querySelector('.card-title').textContent.localeCompare(
                    b.querySelector('.card-title').textContent
                );
                
            case 'title-desc':
                return b.querySelector('.card-title').textContent.localeCompare(
                    a.querySelector('.card-title').textContent
                );
                
            case 'date-new':
                return new Date(b.dataset.date) - new Date(a.dataset.date);
                
            case 'date-old':
                return new Date(a.dataset.date) - new Date(b.dataset.date);
                
            case 'questions-asc':
                return parseInt(a.dataset.questions) - parseInt(b.dataset.questions);
                
            case 'questions-desc':
                return parseInt(b.dataset.questions) - parseInt(a.dataset.questions);
                
            case 'difficulty-easy':
                return getDifficultyValue(a.dataset.difficulty) - getDifficultyValue(b.dataset.difficulty);
                
            case 'difficulty-hard':
                return getDifficultyValue(b.dataset.difficulty) - getDifficultyValue(a.dataset.difficulty);
                
            default:
                return 0;
        }
    });
    
    // Reorder cards in container
    quizCards.forEach(card => {
        quizContainer.appendChild(card);
    });
}

function getDifficultyValue(difficulty) {
    const levels = {
        'easy': 1,
        'medium': 2,
        'hard': 3,
        'expert': 4
    };
    
    return levels[difficulty] || 0;
}

// ===== QUIZ ANALYTICS =====
function trackQuizEvent(eventName, eventData = {}) {
    // Send analytics event
    if (typeof gtag !== 'undefined') {
        gtag('event', eventName, eventData);
    }
    
    // Log to console in development
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        console.log('Quiz Event:', eventName, eventData);
    }
}

// ===== QUIZ SHARING =====
function shareQuizResult() {
    const score = document.querySelector('.result-score').textContent;
    const quizTitle = document.querySelector('.quiz-title').textContent;
    const url = window.location.href;
    
    const shareText = `I scored ${score} on "${quizTitle}"! Take the quiz here: ${url}`;
    
    if (navigator.share) {
        navigator.share({
            title: 'My Quiz Result',
            text: shareText,
            url: url
        });
    } else {
        QuizMaster.copyToClipboard(shareText, 'Result copied to clipboard!');
    }
}

// ===== QUIZ EXPORT =====
function exportQuizResults() {
    const resultData = {
        quiz: document.querySelector('.quiz-title').textContent,
        score: document.querySelector('.result-score').textContent,
        percentage: document.querySelector('.result-percentage').textContent,
        date: document.querySelector('.result-date').textContent,
        details: []
    };
    
    // Collect question details
    document.querySelectorAll('.feedback-item').forEach(item => {
        const question = item.querySelector('.feedback-question').textContent;
        const yourAnswer = item.querySelector('.your-answer .answer-text').textContent;
        const correctAnswer = item.querySelector('.correct-answer .answer-text').textContent;
        const isCorrect = item.classList.contains('correct');
        
        resultData.details.push({
            question: question,
            yourAnswer: yourAnswer,
            correctAnswer: correctAnswer,
            isCorrect: isCorrect
        });
    });
    
    // Convert to JSON
    const jsonData = JSON.stringify(resultData, null, 2);
    
    // Create download link
    const blob = new Blob([jsonData], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `quiz-result-${Date.now()}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    QuizMaster.showToast('Results exported successfully', 'success');
}