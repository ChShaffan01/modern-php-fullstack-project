/**
 * QUIZMASTER - MAIN JAVASCRIPT FILE
 * Contains core functionality and utilities for the quiz system
 */

// ===== GLOBAL VARIABLES =====
let quizData = {
    currentQuiz: null,
    userAnswers: {},
    startTime: null,
    timerInterval: null,
    savedState: null
};

// ===== DOM READY =====
document.addEventListener('DOMContentLoaded', function() {
    initializeComponents();
    setupEventListeners();
    loadSavedState();
});

// ===== INITIALIZATION =====
function initializeComponents() {
    // Initialize tooltips
    initTooltips();
    
    // Initialize popovers
    initPopovers();
    
    // Initialize form validation
    initFormValidation();
    
    // Initialize password strength indicator
    initPasswordStrength();
    
    // Initialize auto-dismiss alerts
    initAutoDismissAlerts();
    
    // Initialize quiz if on quiz page
    if (document.querySelector('.quiz-container')) {
        initQuiz();
    }
    
    // Initialize result charts if on result page
    if (document.querySelector('.performance-chart')) {
        initResultCharts();
    }
}

// ===== TOOLTIPS =====
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            trigger: 'hover focus'
        });
    });
}

// ===== POPOVERS =====
function initPopovers() {
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

// ===== FORM VALIDATION =====
function initFormValidation() {
    // Fetch all forms that need validation
    const forms = document.querySelectorAll('.needs-validation');
    
    // Loop over them and prevent submission
    Array.from(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                highlightInvalidFields(form);
            } else {
                // Show loading state on valid form submission
                showFormLoading(form);
            }
            
            form.classList.add('was-validated');
        }, false);
        
        // Real-time validation for input fields
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(function(input) {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                clearFieldError(this);
            });
        });
    });
}

function validateField(field) {
    const isValid = field.checkValidity();
    const formGroup = field.closest('.form-group') || field.closest('.mb-3');
    
    if (formGroup) {
        if (!isValid) {
            formGroup.classList.add('has-error');
            showFieldError(field, getValidationMessage(field));
        } else {
            formGroup.classList.remove('has-error');
            formGroup.classList.add('has-success');
            clearFieldError(field);
        }
    }
}

function showFieldError(field, message) {
    // Remove existing error message
    clearFieldError(field);
    
    // Create error message element
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback d-block';
    errorDiv.textContent = message;
    
    // Insert after the field
    const formGroup = field.closest('.form-group') || field.closest('.mb-3');
    if (formGroup) {
        formGroup.appendChild(errorDiv);
    }
    
    // Add error styling to field
    field.classList.add('is-invalid');
    field.classList.remove('is-valid');
}

function clearFieldError(field) {
    const formGroup = field.closest('.form-group') || field.closest('.mb-3');
    if (formGroup) {
        const errorMessages = formGroup.querySelectorAll('.invalid-feedback');
        errorMessages.forEach(function(error) {
            error.remove();
        });
    }
    
    field.classList.remove('is-invalid');
}

function getValidationMessage(field) {
    if (field.validity.valueMissing) {
        return 'This field is required';
    }
    
    if (field.validity.typeMismatch) {
        if (field.type === 'email') {
            return 'Please enter a valid email address';
        }
        if (field.type === 'url') {
            return 'Please enter a valid URL';
        }
    }
    
    if (field.validity.patternMismatch) {
        return 'Please match the requested format';
    }
    
    if (field.validity.tooShort) {
        return `Please enter at least ${field.minLength} characters`;
    }
    
    if (field.validity.tooLong) {
        return `Please enter no more than ${field.maxLength} characters`;
    }
    
    if (field.validity.rangeUnderflow) {
        return `Value must be at least ${field.min}`;
    }
    
    if (field.validity.rangeOverflow) {
        return `Value must be at most ${field.max}`;
    }
    
    return 'Please enter a valid value';
}

function highlightInvalidFields(form) {
    const invalidFields = form.querySelectorAll(':invalid');
    invalidFields.forEach(function(field) {
        validateField(field);
    });
}

function showFormLoading(form) {
    const submitButton = form.querySelector('button[type="submit"]');
    if (submitButton) {
        const originalText = submitButton.innerHTML;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
        submitButton.disabled = true;
        
        // Restore button after 5 seconds (in case of error)
        setTimeout(function() {
            submitButton.innerHTML = originalText;
            submitButton.disabled = false;
        }, 5000);
    }
}

// ===== PASSWORD STRENGTH =====
function initPasswordStrength() {
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    
    passwordInputs.forEach(function(input) {
        // Create strength indicator if it doesn't exist
        if (!input.nextElementSibling || !input.nextElementSibling.classList.contains('password-strength')) {
            const strengthDiv = document.createElement('div');
            strengthDiv.className = 'password-strength mt-2';
            input.parentNode.insertBefore(strengthDiv, input.nextSibling);
        }
        
        input.addEventListener('input', function() {
            updatePasswordStrength(this);
        });
        
        // Initial check if password has value
        if (input.value) {
            updatePasswordStrength(input);
        }
    });
}

function updatePasswordStrength(input) {
    const strengthDiv = input.nextElementSibling;
    if (!strengthDiv || !strengthDiv.classList.contains('password-strength')) return;
    
    const password = input.value;
    const strength = calculatePasswordStrength(password);
    
    strengthDiv.innerHTML = `
        <div class="progress" style="height: 5px;">
            <div class="progress-bar ${strength.class}" role="progressbar" 
                 style="width: ${strength.score}%"></div>
        </div>
        <small class="d-block mt-1">${strength.text}</small>
    `;
}

function calculatePasswordStrength(password) {
    let score = 0;
    let text = '';
    let className = '';
    
    // Length check
    if (password.length >= 8) score += 25;
    if (password.length >= 12) score += 10;
    
    // Character variety checks
    if (/[a-z]/.test(password)) score += 10;
    if (/[A-Z]/.test(password)) score += 10;
    if (/[0-9]/.test(password)) score += 10;
    if (/[^A-Za-z0-9]/.test(password)) score += 15;
    
    // Pattern checks (deduct for weak patterns)
    if (/(.)\1{2,}/.test(password)) score -= 10; // Repeated characters
    if (/^(123|abc|password|qwerty)/i.test(password)) score -= 20; // Common patterns
    if (/^\d+$/.test(password)) score -= 15; // All numbers
    
    // Cap score between 0 and 100
    score = Math.max(0, Math.min(100, score));
    
    // Determine strength level
    if (score >= 80) {
        text = 'Very Strong';
        className = 'bg-success';
    } else if (score >= 60) {
        text = 'Strong';
        className = 'bg-info';
    } else if (score >= 40) {
        text = 'Medium';
        className = 'bg-warning';
    } else if (score >= 20) {
        text = 'Weak';
        className = 'bg-danger';
    } else {
        text = 'Very Weak';
        className = 'bg-secondary';
    }
    
    return { score, text, className };
}

// ===== AUTO-DISMISS ALERTS =====
function initAutoDismissAlerts() {
    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Add close buttons to alerts that don't have them
    const alerts = document.querySelectorAll('.alert:not(.alert-dismissible)');
    alerts.forEach(function(alert) {
        if (!alert.querySelector('.btn-close')) {
            alert.classList.add('alert-dismissible', 'fade', 'show');
            const closeButton = document.createElement('button');
            closeButton.type = 'button';
            closeButton.className = 'btn-close';
            closeButton.setAttribute('data-bs-dismiss', 'alert');
            closeButton.setAttribute('aria-label', 'Close');
            alert.appendChild(closeButton);
        }
    });
}

// ===== QUIZ FUNCTIONS =====
function initQuiz() {
    // Load quiz data from data attributes
    const quizContainer = document.querySelector('.quiz-container');
    if (quizContainer) {
        quizData.currentQuiz = {
            id: quizContainer.dataset.quizId,
            timeLimit: parseInt(quizContainer.dataset.timeLimit) || 30,
            totalQuestions: parseInt(quizContainer.dataset.totalQuestions) || 0
        };
        
        quizData.startTime = new Date();
        
        // Initialize timer
        initQuizTimer();
        
        // Initialize question navigation
        initQuestionNavigation();
        
        // Initialize answer tracking
        initAnswerTracking();
        
        // Initialize auto-save
        initAutoSave();
        
        // Initialize keyboard shortcuts
        initKeyboardShortcuts();
        
        // Initialize question marking
        initQuestionMarking();
    }
}

function initQuizTimer() {
    const timerElement = document.querySelector('.timer-display');
    const progressBar = document.querySelector('.timer-progress-bar');
    const statusElement = document.querySelector('.timer-status');
    
    if (!timerElement || !quizData.currentQuiz) return;
    
    const timeLimit = quizData.currentQuiz.timeLimit * 60; // Convert to seconds
    let timeRemaining = timeLimit;
    
    // Update timer every second
    quizData.timerInterval = setInterval(function() {
        timeRemaining--;
        
        // Calculate minutes and seconds
        const minutes = Math.floor(timeRemaining / 60);
        const seconds = timeRemaining % 60;
        
        // Update display
        timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        // Update progress bar
        if (progressBar) {
            const progressPercent = ((timeLimit - timeRemaining) / timeLimit) * 100;
            progressBar.style.width = `${progressPercent}%`;
        }
        
        // Update status and styling
        if (statusElement) {
            if (timeRemaining <= 300) { // 5 minutes
                timerElement.classList.add('text-danger');
                statusElement.textContent = 'Hurry up! Time is running out.';
                statusElement.classList.add('danger');
            } else if (timeRemaining <= 600) { // 10 minutes
                timerElement.classList.add('text-warning');
                statusElement.textContent = 'Time is getting low.';
                statusElement.classList.add('warning');
            }
        }
        
        // Auto-submit when time is up
        if (timeRemaining <= 0) {
            clearInterval(quizData.timerInterval);
            submitQuiz();
        }
    }, 1000);
    
    // Save timer state
    quizData.timerIntervalId = quizData.timerInterval;
}

function initQuestionNavigation() {
    const navButtons = document.querySelectorAll('.question-nav-btn');
    const prevButton = document.querySelector('.btn-prev');
    const nextButton = document.querySelector('.btn-next');
    
    // Handle navigation button clicks
    navButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const questionId = this.dataset.questionId;
            navigateToQuestion(questionId);
        });
    });
    
    // Handle previous/next buttons
    if (prevButton) {
        prevButton.addEventListener('click', function() {
            navigateToRelativeQuestion(-1);
        });
    }
    
    if (nextButton) {
        nextButton.addEventListener('click', function() {
            navigateToRelativeQuestion(1);
        });
    }
    
    // Initialize current question
    const currentQuestion = document.querySelector('.question-card.current');
    if (currentQuestion) {
        updateNavigationState(currentQuestion.dataset.questionId);
    }
}

function navigateToQuestion(questionId) {
    // Hide all questions
    document.querySelectorAll('.question-card').forEach(function(card) {
        card.classList.remove('current');
    });
    
    // Show selected question
    const targetQuestion = document.querySelector(`.question-card[data-question-id="${questionId}"]`);
    if (targetQuestion) {
        targetQuestion.classList.add('current');
        targetQuestion.scrollIntoView({ behavior: 'smooth', block: 'start' });
        updateNavigationState(questionId);
    }
}

function navigateToRelativeQuestion(offset) {
    const questions = Array.from(document.querySelectorAll('.question-card'));
    const currentIndex = questions.findIndex(q => q.classList.contains('current'));
    
    if (currentIndex !== -1) {
        const newIndex = currentIndex + offset;
        if (newIndex >= 0 && newIndex < questions.length) {
            const questionId = questions[newIndex].dataset.questionId;
            navigateToQuestion(questionId);
        }
    }
}

function updateNavigationState(questionId) {
    // Update nav buttons
    document.querySelectorAll('.question-nav-btn').forEach(function(button) {
        button.classList.remove('current');
        if (button.dataset.questionId === questionId) {
            button.classList.add('current');
        }
    });
    
    // Update prev/next button states
    const questions = Array.from(document.querySelectorAll('.question-card'));
    const currentIndex = questions.findIndex(q => q.dataset.questionId === questionId);
    
    const prevButton = document.querySelector('.btn-prev');
    const nextButton = document.querySelector('.btn-next');
    
    if (prevButton) {
        prevButton.disabled = currentIndex === 0;
    }
    
    if (nextButton) {
        nextButton.disabled = currentIndex === questions.length - 1;
    }
}

function initAnswerTracking() {
    // Track radio button changes
    document.querySelectorAll('input[type="radio"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const questionId = this.name.match(/\[(\d+)\]/)[1];
            const optionId = this.value;
            
            // Save answer
            quizData.userAnswers[questionId] = optionId;
            
            // Update UI
            updateQuestionStatus(questionId, true);
            updateNavigationButton(questionId, true);
            
            // Auto-save
            saveQuizState();
        });
    });
}

function updateQuestionStatus(questionId, answered) {
    const questionCard = document.querySelector(`.question-card[data-question-id="${questionId}"]`);
    if (questionCard) {
        if (answered) {
            questionCard.classList.add('answered');
        } else {
            questionCard.classList.remove('answered');
        }
    }
}

function updateNavigationButton(questionId, answered) {
    const navButton = document.querySelector(`.question-nav-btn[data-question-id="${questionId}"]`);
    if (navButton) {
        if (answered) {
            navButton.classList.add('answered');
        } else {
            navButton.classList.remove('answered');
        }
    }
}

function initAutoSave() {
    // Save quiz state every 30 seconds
    setInterval(function() {
        if (Object.keys(quizData.userAnswers).length > 0) {
            saveQuizState();
            showToast('Progress auto-saved', 'info');
        }
    }, 30000);
    
    // Save on page unload
    window.addEventListener('beforeunload', function(e) {
        if (Object.keys(quizData.userAnswers).length > 0) {
            saveQuizState();
            // Show confirmation for unsaved changes
            e.preventDefault();
            e.returnValue = 'You have unsaved answers. Are you sure you want to leave?';
        }
    });
}

function saveQuizState() {
    const state = {
        answers: quizData.userAnswers,
        startTime: quizData.startTime,
        lastSaved: new Date()
    };
    
    // Save to localStorage
    localStorage.setItem(`quiz_${quizData.currentQuiz.id}`, JSON.stringify(state));
    
    // Update save indicator
    const saveIndicator = document.querySelector('.save-indicator');
    if (saveIndicator) {
        saveIndicator.textContent = `Last saved: ${new Date().toLocaleTimeString()}`;
        saveIndicator.classList.add('text-success');
        
        // Remove success class after 2 seconds
        setTimeout(function() {
            saveIndicator.classList.remove('text-success');
        }, 2000);
    }
}

function loadSavedState() {
    if (!quizData.currentQuiz) return;
    
    const savedState = localStorage.getItem(`quiz_${quizData.currentQuiz.id}`);
    if (savedState) {
        try {
            quizData.savedState = JSON.parse(savedState);
            
            // Restore answers
            if (quizData.savedState.answers) {
                quizData.userAnswers = quizData.savedState.answers;
                
                // Check radio buttons
                Object.entries(quizData.userAnswers).forEach(([questionId, optionId]) => {
                    const radio = document.querySelector(`input[name="answers[${questionId}]"][value="${optionId}"]`);
                    if (radio) {
                        radio.checked = true;
                        updateQuestionStatus(questionId, true);
                        updateNavigationButton(questionId, true);
                    }
                });
            }
            
            // Show restore notification
            const restoreBtn = document.createElement('button');
            restoreBtn.className = 'btn btn-info btn-sm';
            restoreBtn.innerHTML = '<i class="fas fa-history me-1"></i>Restore Previous Session';
            restoreBtn.addEventListener('click', function() {
                if (confirm('Restore your previous quiz session? This will replace any current answers.')) {
                    location.reload();
                }
            });
            
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-info alert-dismissible fade show';
            alertDiv.innerHTML = `
                <i class="fas fa-info-circle me-2"></i>
                Found a previous session from ${new Date(quizData.savedState.lastSaved).toLocaleString()}.
                <div class="mt-2">${restoreBtn.outerHTML}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const container = document.querySelector('.container');
            if (container) {
                container.insertBefore(alertDiv, container.firstChild);
            }
            
        } catch (e) {
            console.error('Failed to load saved state:', e);
        }
    }
}

function initKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Don't trigger shortcuts in input fields
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
        
        // Previous question: Arrow Left or P
        if (e.key === 'ArrowLeft' || e.key === 'p') {
            e.preventDefault();
            navigateToRelativeQuestion(-1);
        }
        
        // Next question: Arrow Right or N
        else if (e.key === 'ArrowRight' || e.key === 'n') {
            e.preventDefault();
            navigateToRelativeQuestion(1);
        }
        
        // Mark question: M
        else if (e.key === 'm') {
            e.preventDefault();
            toggleQuestionMark();
        }
        
        // Submit quiz: Ctrl + Enter
        else if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault();
            if (confirm('Are you sure you want to submit the quiz?')) {
                submitQuiz();
            }
        }
        
        // Show shortcuts: ?
        else if (e.key === '?') {
            e.preventDefault();
            showKeyboardShortcuts();
        }
    });
}

function toggleQuestionMark() {
    const currentQuestion = document.querySelector('.question-card.current');
    if (currentQuestion) {
        const questionId = currentQuestion.dataset.questionId;
        const navButton = document.querySelector(`.question-nav-btn[data-question-id="${questionId}"]`);
        
        if (navButton) {
            navButton.classList.toggle('marked');
            showToast('Question marked for review', 'info');
        }
    }
}

function showKeyboardShortcuts() {
    const shortcuts = [
        { key: '← or P', action: 'Previous question' },
        { key: '→ or N', action: 'Next question' },
        { key: 'M', action: 'Mark question for review' },
        { key: 'Ctrl + Enter', action: 'Submit quiz' },
        { key: '?', action: 'Show this help' }
    ];
    
    let html = '<div class="shortcut-list">';
    shortcuts.forEach(function(shortcut) {
        html += `
            <div class="d-flex justify-content-between mb-2">
                <span class="shortcut-key">${shortcut.key}</span>
                <span>${shortcut.action}</span>
            </div>
        `;
    });
    html += '</div>';
    
    showToast(html, 'info', 5000);
}

function submitQuiz() {
    // Clear timer
    if (quizData.timerInterval) {
        clearInterval(quizData.timerInterval);
    }
    
    // Remove beforeunload listener
    window.removeEventListener('beforeunload', arguments.callee);
    
    // Clear saved state
    localStorage.removeItem(`quiz_${quizData.currentQuiz.id}`);
    
    // Submit form
    const quizForm = document.getElementById('quizForm');
    if (quizForm) {
        quizForm.submit();
    }
}

function initQuestionMarking() {
    document.querySelectorAll('.question-nav-btn').forEach(function(button) {
        button.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            this.classList.toggle('marked');
            showToast('Question marked for review', 'info');
        });
    });
}

// ===== RESULT CHARTS =====
function initResultCharts() {
    const chartElements = document.querySelectorAll('.performance-chart');
    
    chartElements.forEach(function(chartElement) {
        const correctPercent = parseFloat(chartElement.dataset.correctPercent) || 0;
        const incorrectPercent = parseFloat(chartElement.dataset.incorrectPercent) || 0;
        const unansweredPercent = 100 - correctPercent - incorrectPercent;
        
        // Set CSS variables for conic gradient
        chartElement.style.setProperty('--correct-percent', correctPercent);
        chartElement.style.setProperty('--incorrect-percent', correctPercent + incorrectPercent);
        
        // Update center text
        const centerElement = chartElement.querySelector('.chart-center');
        if (centerElement) {
            centerElement.innerHTML = `
                <div class="text-center">
                    <div class="h4 mb-0">${Math.round(correctPercent)}%</div>
                    <small class="text-muted">Correct</small>
                </div>
            `;
        }
        
        // Update legend
        const legendElement = chartElement.querySelector('.chart-legend');
        if (legendElement) {
            legendElement.innerHTML = `
                <div class="legend-item">
                    <span class="legend-color correct"></span>
                    <small>Correct: ${Math.round(correctPercent)}%</small>
                </div>
                <div class="legend-item">
                    <span class="legend-color incorrect"></span>
                    <small>Incorrect: ${Math.round(incorrectPercent)}%</small>
                </div>
                <div class="legend-item">
                    <span class="legend-color unanswered"></span>
                    <small>Unanswered: ${Math.round(unansweredPercent)}%</small>
                </div>
            `;
        }
    });
}

// ===== TOAST NOTIFICATIONS =====
function showToast(message, type = 'info', duration = 3000) {
    // Create toast container if it doesn't exist
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    // Create toast element
    const toastId = 'toast-' + Date.now();
    const toast = document.createElement('div');
    toast.id = toastId;
    toast.className = `toast align-items-center text-bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    // Set toast content
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    // Add toast to container
    toastContainer.appendChild(toast);
    
    // Initialize and show toast
    const bsToast = new bootstrap.Toast(toast, {
        delay: duration,
        animation: true
    });
    
    bsToast.show();
    
    // Remove toast from DOM after it's hidden
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
    
    return bsToast;
}

// ===== COPY TO CLIPBOARD =====
function copyToClipboard(text, successMessage = 'Copied to clipboard!') {
    navigator.clipboard.writeText(text).then(function() {
        showToast(successMessage, 'success');
    }).catch(function(err) {
        console.error('Could not copy text: ', err);
        showToast('Failed to copy', 'danger');
    });
}

// ===== AJAX HELPER =====
function ajaxRequest(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: null
    };
    
    const mergedOptions = { ...defaultOptions, ...options };
    
    // Convert body to JSON if it's an object
    if (mergedOptions.body && typeof mergedOptions.body === 'object') {
        mergedOptions.body = JSON.stringify(mergedOptions.body);
    }
    
    return fetch(url, mergedOptions)
        .then(async function(response) {
            const contentType = response.headers.get('content-type');
            
            if (contentType && contentType.includes('application/json')) {
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || 'Request failed');
                }
                
                return data;
            } else {
                const text = await response.text();
                
                if (!response.ok) {
                    throw new Error(text || 'Request failed');
                }
                
                return text;
            }
        })
        .catch(function(error) {
            console.error('AJAX request failed:', error);
            showToast(error.message, 'danger');
            throw error;
        });
}

// ===== FORM DATA SERIALIZATION =====
function serializeForm(form) {
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        // Handle array inputs (e.g., name="array[]")
        if (key.endsWith('[]')) {
            key = key.slice(0, -2);
            if (!data[key]) {
                data[key] = [];
            }
            data[key].push(value);
        } else {
            data[key] = value;
        }
    }
    
    return data;
}

// ===== DEBOUNCE FUNCTION =====
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ===== THROTTLE FUNCTION =====
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// ===== SCROLL TO TOP =====
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// ===== DARK MODE TOGGLE =====
function toggleDarkMode() {
    const htmlElement = document.documentElement;
    const currentTheme = htmlElement.getAttribute('data-bs-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    htmlElement.setAttribute('data-bs-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    
    showToast(`Switched to ${newTheme} mode`, 'info');
}

// ===== INITIALIZE DARK MODE =====
function initDarkMode() {
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
        document.documentElement.setAttribute('data-bs-theme', 'dark');
    }
}

// ===== EVENT LISTENERS SETUP =====
function setupEventListeners() {
    // Initialize dark mode
    initDarkMode();
    
    // Scroll to top button
    const scrollTopBtn = document.getElementById('scrollTopBtn');
    if (scrollTopBtn) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                scrollTopBtn.classList.add('show');
            } else {
                scrollTopBtn.classList.remove('show');
            }
        });
        
        scrollTopBtn.addEventListener('click', scrollToTop);
    }
    
    // Dark mode toggle
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', toggleDarkMode);
    }
    
    // Print buttons
    document.querySelectorAll('[data-action="print"]').forEach(function(button) {
        button.addEventListener('click', function() {
            window.print();
        });
    });
    
    // Copy buttons
    document.querySelectorAll('[data-action="copy"]').forEach(function(button) {
        button.addEventListener('click', function() {
            const text = this.dataset.copyText || '';
            copyToClipboard(text);
        });
    });
    
    // Confirm buttons
    document.querySelectorAll('[data-confirm]').forEach(function(button) {
        button.addEventListener('click', function(e) {
            const message = this.dataset.confirm || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

// ===== EXPORT FUNCTIONS FOR GLOBAL USE =====
window.QuizMaster = {
    showToast,
    copyToClipboard,
    ajaxRequest,
    serializeForm,
    debounce,
    throttle,
    scrollToTop,
    toggleDarkMode
};