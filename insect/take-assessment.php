<?php
session_start();
require_once 'config.php';

if (!isLoggedIn()) {
    redirectTo('login.php?redirect=assessments.php');
}

$assessment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

if (!$assessment_id) {
    redirectTo('assessments.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_assessment'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Security token mismatch.';
        redirectTo('assessments.php');
    }
    
    try {
        $pdo = getDBConnection();
        
        // Get questions for this assessment
        $stmt = $pdo->prepare("SELECT * FROM assessment_questions WHERE assessment_id = ?");
        $stmt->execute([$assessment_id]);
        $questions = $stmt->fetchAll();
        
        if (empty($questions)) {
            throw new Exception("No questions found for this assessment.");
        }
        
        // Calculate score
        $total_questions = count($questions);
        $correct_answers = 0;
        $total_points = 0;
        $earned_points = 0;
        
        foreach ($questions as $question) {
            $question_id = $question['id'];
            $user_answer = trim($_POST['answer_' . $question_id] ?? '');
            $correct_answer = trim($question['correct_answer']);
            $points = intval($question['points']);
            
            $total_points += $points;
            
            // Check answer based on question type
            if ($question['question_type'] === 'multiple_choice' || $question['question_type'] === 'true_false') {
                if (strcasecmp($user_answer, $correct_answer) === 0) {
                    $correct_answers++;
                    $earned_points += $points;
                }
            } elseif ($question['question_type'] === 'short_answer') {
                // Case-insensitive comparison for short answers
                if (strcasecmp($user_answer, $correct_answer) === 0) {
                    $correct_answers++;
                    $earned_points += $points;
                }
            }
        }
        
        // Calculate percentage score
        $score = ($total_points > 0) ? ($earned_points / $total_points) * 100 : 0;
        
        // Check if user_assessment_results table exists, if not create it
        $stmt = $pdo->query("SHOW TABLES LIKE 'user_assessment_results'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("
                CREATE TABLE user_assessment_results (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT NOT NULL,
                    assessment_id INT NOT NULL,
                    score DECIMAL(5,2) NOT NULL,
                    total_questions INT NOT NULL,
                    correct_answers INT NOT NULL,
                    total_points INT NOT NULL,
                    earned_points INT NOT NULL,
                    time_taken INT DEFAULT 0,
                    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE,
                    INDEX idx_user_assessment (user_id, assessment_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }
        
        // Calculate time taken (if start time was stored in session)
        $time_taken = 0;
        if (isset($_SESSION['assessment_start_time_' . $assessment_id])) {
            $time_taken = time() - $_SESSION['assessment_start_time_' . $assessment_id];
            unset($_SESSION['assessment_start_time_' . $assessment_id]);
        }
        
        // Save result
        $stmt = $pdo->prepare("
            INSERT INTO user_assessment_results 
            (user_id, assessment_id, score, total_questions, correct_answers, total_points, earned_points, time_taken, completed_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $assessment_id, $score, $total_questions, $correct_answers, $total_points, $earned_points, $time_taken]);
        
        $result_id = $pdo->lastInsertId();
        
        // Store answers for review
        $_SESSION['assessment_answers_' . $result_id] = $_POST;
        
        // Redirect to results
        redirectTo('assessment-result.php?id=' . $result_id);
        
    } catch (Exception $e) {
        error_log("Submit assessment error: " . $e->getMessage());
        $_SESSION['error_message'] = 'Error submitting assessment: ' . $e->getMessage();
    }
}

try {
    $pdo = getDBConnection();
    
    // Get assessment details
    $stmt = $pdo->prepare("SELECT * FROM assessments WHERE id = ? AND is_active = 1");
    $stmt->execute([$assessment_id]);
    $assessment = $stmt->fetch();
    
    if (!$assessment) {
        $_SESSION['error_message'] = 'Assessment not found or inactive.';
        redirectTo('assessments.php');
    }
    
    // Get questions for this assessment
    $stmt = $pdo->prepare("SELECT * FROM assessment_questions WHERE assessment_id = ? ORDER BY question_order, id");
    $stmt->execute([$assessment_id]);
    $questions = $stmt->fetchAll();
    
    if (empty($questions)) {
        $_SESSION['error_message'] = 'No questions available for this assessment.';
        redirectTo('assessments.php');
    }
    
    // Store start time in session
    if (!isset($_SESSION['assessment_start_time_' . $assessment_id])) {
        $_SESSION['assessment_start_time_' . $assessment_id] = time();
    }
    
    $page_title = htmlspecialchars($assessment['title']) . " - Insect Life";
    
} catch (Exception $e) {
    error_log("Take assessment error: " . $e->getMessage());
    $_SESSION['error_message'] = 'Error loading assessment.';
    redirectTo('assessments.php');
}

$additional_css = "
    .quiz-container {
        max-width: 900px;
        margin: 2rem auto;
        padding: 0 1rem 3rem;
    }
    
    .quiz-header {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-green) 100%);
        color: white;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-lg);
        padding: 2rem;
        margin-bottom: 2rem;
        text-align: center;
    }
    
    .quiz-header h1 {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        color: white;
    }
    
    .quiz-header p {
        opacity: 0.95;
        font-size: 1.1rem;
        color: white;
        margin-bottom: 1rem;
    }
    
    .quiz-meta {
        display: flex;
        justify-content: center;
        gap: 2rem;
        margin-top: 1rem;
        font-size: 0.95rem;
    }
    
    .quiz-meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .quiz-progress {
        background: white;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        padding: 1.5rem;
        margin-bottom: 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 80px;
        z-index: 50;
    }
    
    .progress-info {
        font-size: 0.95rem;
        color: var(--gray-600);
        font-weight: 600;
    }
    
    .progress-bar-container {
        flex: 1;
        margin: 0 2rem;
        height: 10px;
        background: var(--gray-200);
        border-radius: 10px;
        overflow: hidden;
    }
    
    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--primary-blue) 0%, var(--secondary-green) 100%);
        transition: width 0.3s ease;
        border-radius: 10px;
    }
    
    .timer {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--primary-blue);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .question-card {
        background: white;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        padding: 2rem;
        margin-bottom: 2rem;
        transition: all var(--transition-normal);
        scroll-margin-top: 150px;
    }
    
    .question-card:hover {
        box-shadow: var(--shadow-lg);
    }
    
    .question-card.answered {
        border-left: 4px solid var(--secondary-green);
    }
    
    .question-number {
        display: inline-block;
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-green) 100%);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.9rem;
        margin-bottom: 1rem;
    }
    
    .question-type-badge {
        display: inline-block;
        background: var(--gray-200);
        color: var(--gray-700);
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-left: 0.5rem;
        text-transform: uppercase;
    }
    
    .question-text {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--gray-900);
        margin-bottom: 1.5rem;
        line-height: 1.6;
    }
    
    .question-points {
        font-size: 0.85rem;
        color: var(--gray-600);
        margin-bottom: 1rem;
    }
    
    .options-container {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .option-label {
        display: flex;
        align-items: center;
        padding: 1.25rem;
        border: 2px solid var(--gray-300);
        border-radius: var(--border-radius-md);
        cursor: pointer;
        transition: all var(--transition-fast);
        background: white;
        position: relative;
    }
    
    .option-label:hover {
        border-color: var(--primary-blue);
        background: rgba(59, 130, 246, 0.05);
        transform: translateX(5px);
    }
    
    .option-label input[type='radio'] {
        width: 20px;
        height: 20px;
        margin-right: 1rem;
        accent-color: var(--primary-blue);
        cursor: pointer;
        flex-shrink: 0;
    }
    
    .option-label:has(input:checked) {
        border-color: var(--primary-blue);
        background: rgba(59, 130, 246, 0.1);
        border-width: 3px;
    }
    
    .option-letter {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        background: var(--gray-200);
        border-radius: 50%;
        font-weight: 700;
        margin-right: 1rem;
        color: var(--gray-700);
        flex-shrink: 0;
    }
    
    .option-label:has(input:checked) .option-letter {
        background: var(--primary-blue);
        color: white;
    }
    
    .option-text {
        flex: 1;
        color: var(--gray-800);
        font-size: 1rem;
    }
    
    .option-label:has(input:checked) .option-text {
        font-weight: 600;
        color: var(--gray-900);
    }
    
    .short-answer-input {
        width: 100%;
        padding: 1rem;
        border: 2px solid var(--gray-300);
        border-radius: var(--border-radius-md);
        font-size: 1rem;
        transition: all var(--transition-fast);
    }
    
    .short-answer-input:focus {
        outline: none;
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .quiz-actions {
        position: sticky;
        bottom: 0;
        background: white;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-xl);
        padding: 1.5rem;
        margin-top: 2rem;
        display: flex;
        gap: 1rem;
        justify-content: space-between;
        z-index: 100;
    }
    
    .btn-submit {
        flex: 1;
        padding: 1rem 2rem;
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-green) 100%);
        color: white;
        border: none;
        border-radius: var(--border-radius-md);
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all var(--transition-fast);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    
    .btn-submit:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }
    
    .btn-submit:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .btn-cancel {
        padding: 1rem 2rem;
        background: white;
        color: var(--gray-700);
        border: 2px solid var(--gray-300);
        border-radius: var(--border-radius-md);
        font-weight: 600;
        cursor: pointer;
        transition: all var(--transition-fast);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn-cancel:hover {
        border-color: var(--gray-400);
        background: var(--gray-50);
    }
    
    .alert {
        padding: 1rem 1.5rem;
        border-radius: var(--border-radius-md);
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .alert-warning {
        background: rgba(251, 191, 36, 0.1);
        color: #d97706;
        border: 1px solid rgba(251, 191, 36, 0.3);
    }
    
    .alert-info {
        background: rgba(59, 130, 246, 0.1);
        color: var(--primary-blue);
        border: 1px solid rgba(59, 130, 246, 0.3);
    }
    
    @media (max-width: 768px) {
        .quiz-progress {
            flex-direction: column;
            gap: 1rem;
            top: 60px;
        }
        
        .progress-bar-container {
            margin: 0;
            width: 100%;
        }
        
        .quiz-actions {
            flex-direction: column;
        }
        
        .quiz-meta {
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .option-label {
            padding: 1rem;
        }
        
        .question-text {
            font-size: 1.1rem;
        }
    }
";

include 'includes/header.php';
?>

<div class="quiz-container">
    <div class="quiz-header">
        <h1><?php echo htmlspecialchars($assessment['title']); ?></h1>
        <p><?php echo htmlspecialchars($assessment['description']); ?></p>
        <div class="quiz-meta">
            <div class="quiz-meta-item">
                <i class="fas fa-question-circle"></i>
                <span><?php echo count($questions); ?> Questions</span>
            </div>
            <div class="quiz-meta-item">
                <i class="fas fa-clock"></i>
                <span><?php echo $assessment['time_limit']; ?> minutes</span>
            </div>
            <div class="quiz-meta-item">
                <i class="fas fa-chart-line"></i>
                <span><?php echo number_format($assessment['passing_score']); ?>% to pass</span>
            </div>
        </div>
    </div>
    
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <div>
            <strong>Instructions:</strong> Answer all questions to the best of your ability. 
            You can review your answers before submitting. Good luck!
        </div>
    </div>
    
    <form method="POST" id="assessment-form">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="submit_assessment" value="1">
        
        <!-- Progress Bar -->
        <div class="quiz-progress">
            <div class="progress-info">
                <span id="answered-count">0</span> of <?php echo count($questions); ?> answered
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar-fill" id="progress-bar"></div>
            </div>
            <div class="timer" id="timer">
                <i class="fas fa-clock"></i>
                <span id="time-display">00:00</span>
            </div>
        </div>
        
        <!-- Questions -->
        <?php foreach ($questions as $index => $question): ?>
            <?php 
            $options = json_decode($question['options'], true) ?? [];
            $question_num = $index + 1;
            ?>
            <div class="question-card" id="question-<?php echo $question['id']; ?>" data-question-id="<?php echo $question['id']; ?>">
                <div>
                    <span class="question-number">Question <?php echo $question_num; ?></span>
                    <span class="question-type-badge"><?php echo str_replace('_', ' ', $question['question_type']); ?></span>
                </div>
                
                <div class="question-text"><?php echo htmlspecialchars($question['question_text']); ?></div>
                
                <?php if ($question['points'] > 1): ?>
                <div class="question-points">
                    <i class="fas fa-star"></i> Worth <?php echo $question['points']; ?> points
                </div>
                <?php endif; ?>
                
                <?php if ($question['question_type'] === 'multiple_choice'): ?>
                    <div class="options-container">
                        <?php foreach ($options as $opt_index => $option): ?>
                            <label class="option-label">
                                <input type="radio" 
                                       name="answer_<?php echo $question['id']; ?>" 
                                       value="<?php echo htmlspecialchars($option); ?>"
                                       onchange="updateProgress()">
                                <span class="option-letter"><?php echo chr(65 + $opt_index); ?></span>
                                <span class="option-text"><?php echo htmlspecialchars($option); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    
                <?php elseif ($question['question_type'] === 'true_false'): ?>
                    <div class="options-container">
                        <label class="option-label">
                            <input type="radio" 
                                   name="answer_<?php echo $question['id']; ?>" 
                                   value="True"
                                   onchange="updateProgress()">
                            <span class="option-letter">T</span>
                            <span class="option-text">True</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" 
                                   name="answer_<?php echo $question['id']; ?>" 
                                   value="False"
                                   onchange="updateProgress()">
                            <span class="option-letter">F</span>
                            <span class="option-text">False</span>
                        </label>
                    </div>
                    
                <?php elseif ($question['question_type'] === 'short_answer'): ?>
                    <input type="text" 
                           class="short-answer-input" 
                           name="answer_<?php echo $question['id']; ?>"
                           placeholder="Type your answer here..."
                           oninput="updateProgress()">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        <!-- Submit Section -->
        <div class="quiz-actions">
            <a href="assessments.php" class="btn-cancel" onclick="return confirm('Are you sure you want to cancel? Your progress will be lost.')">
                <i class="fas fa-times"></i> Cancel
            </a>
            <button type="submit" class="btn-submit" id="submit-btn">
                <i class="fas fa-check"></i> Submit Assessment
            </button>
        </div>
    </form>
</div>

<script>
// Timer
let startTime = <?php echo $_SESSION['assessment_start_time_' . $assessment_id]; ?>;
let timeLimit = <?php echo $assessment['time_limit']; ?> * 60; // Convert to seconds

function updateTimer() {
    const elapsed = Math.floor(Date.now() / 1000) - startTime;
    const remaining = Math.max(0, timeLimit - elapsed);
    
    const minutes = Math.floor(remaining / 60);
    const seconds = remaining % 60;
    
    document.getElementById('time-display').textContent = 
        String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
    
    if (remaining <= 300 && remaining > 0) { // 5 minutes warning
        document.getElementById('timer').style.color = '#ef4444';
    }
    
    if (remaining === 0) {
        alert('Time is up! The assessment will be submitted automatically.');
        document.getElementById('assessment-form').submit();
    }
}

setInterval(updateTimer, 1000);
updateTimer();

// Progress tracking
function updateProgress() {
    const questionCards = document.querySelectorAll('.question-card');
    let answeredCount = 0;
    
    questionCards.forEach(card => {
        const questionId = card.getAttribute('data-question-id');
        const radios = card.querySelectorAll('input[type="radio"]');
        const textInput = card.querySelector('input[type="text"]');
        
        let isAnswered = false;
        
        if (radios.length > 0) {
            isAnswered = Array.from(radios).some(radio => radio.checked);
        } else if (textInput) {
            isAnswered = textInput.value.trim() !== '';
        }
        
        if (isAnswered) {
            answeredCount++;
            card.classList.add('answered');
        } else {
            card.classList.remove('answered');
        }
    });
    
    const totalQuestions = questionCards.length;
    const percentage = (answeredCount / totalQuestions) * 100;
    
    document.getElementById('answered-count').textContent = answeredCount;
    document.getElementById('progress-bar').style.width = percentage + '%';
    
    // Enable submit button if all answered
    const submitBtn = document.getElementById('submit-btn');
    if (answeredCount === totalQuestions) {
        submitBtn.disabled = false;
    }
}

// Form submission validation
document.getElementById('assessment-form').addEventListener('submit', function(e) {
    const questionCards = document.querySelectorAll('.question-card');
    let unansweredQuestions = [];
    
    questionCards.forEach((card, index) => {
        const questionId = card.getAttribute('data-question-id');
        const radios = card.querySelectorAll('input[type="radio"]');
        const textInput = card.querySelector('input[type="text"]');
        
        let isAnswered = false;
        
        if (radios.length > 0) {
            isAnswered = Array.from(radios).some(radio => radio.checked);
        } else if (textInput) {
            isAnswered = textInput.value.trim() !== '';
        }
        
        if (!isAnswered) {
            unansweredQuestions.push(index + 1);
        }
    });
    
    if (unansweredQuestions.length > 0) {
        e.preventDefault();
        const message = `Please answer all questions before submitting.\n\nUnanswered questions: ${unansweredQuestions.join(', ')}`;
        alert(message);
        
        // Scroll to first unanswered question
        const firstUnanswered = document.querySelector('.question-card:not(.answered)');
        if (firstUnanswered) {
            firstUnanswered.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return false;
    }
    
    if (!confirm('Are you sure you want to submit your assessment? You cannot change your answers after submission.')) {
        e.preventDefault();
        return false;
    }
    
    // Show loading state
    const submitBtn = document.getElementById('submit-btn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
});

// Initialize
updateProgress();

// Auto-save to localStorage for recovery (optional)
setInterval(function() {
    const formData = new FormData(document.getElementById('assessment-form'));
    const data = {};
    for (let [key, value] of formData.entries()) {
        if (key.startsWith('answer_')) {
            data[key] = value;
        }
    }
    localStorage.setItem('assessment_<?php echo $assessment_id; ?>', JSON.stringify(data));
}, 30000); // Save every 30 seconds

// Prevent accidental navigation away
window.addEventListener('beforeunload', function(e) {
    e.preventDefault();
    e.returnValue = '';
    return '';
});
</script>

<?php include 'includes/footer.php'; ?>