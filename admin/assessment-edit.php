<?php
session_start();
require_once '../config.php';

if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    redirectTo('../login.php');
}

$page_title = "Edit Assessment - Admin";
$success_message = '';
$error_message = '';

$assessment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$is_edit = $assessment_id > 0;

// Get assessment data if editing
$assessment = null;
$questions = [];
if ($is_edit) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM assessments WHERE id = ?");
        $stmt->execute([$assessment_id]);
        $assessment = $stmt->fetch();
        
        if (!$assessment) {
            $error_message = "Assessment not found.";
            $is_edit = false;
        } else {
            // Get questions for this assessment
            $stmt = $pdo->prepare("SELECT * FROM assessment_questions WHERE assessment_id = ? ORDER BY question_order");
            $stmt->execute([$assessment_id]);
            $questions = $stmt->fetchAll();
        }
    } catch (Exception $e) {
        $error_message = "Error loading assessment: " . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Security token mismatch.';
    } else {
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $category = sanitizeInput($_POST['category'] ?? '');
        $difficulty_level = sanitizeInput($_POST['difficulty_level'] ?? 'beginner');
        $time_limit = intval($_POST['time_limit'] ?? 30);
        $passing_score = floatval($_POST['passing_score'] ?? 70);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $question_count = intval($_POST['question_count'] ?? 0);
        
        $errors = [];
        if (empty($title)) $errors[] = "Title is required.";
        if (empty($description)) $errors[] = "Description is required.";
        if ($time_limit < 1) $errors[] = "Time limit must be at least 1 minute.";
        if ($passing_score < 0 || $passing_score > 100) $errors[] = "Passing score must be between 0 and 100.";
        
        if (empty($errors)) {
            try {
                $pdo = getDBConnection();
                $pdo->beginTransaction();
                
                if ($is_edit) {
                    // Update assessment
                    $stmt = $pdo->prepare("
                        UPDATE assessments SET
                            title = ?, description = ?, category = ?, 
                            difficulty_level = ?, time_limit = ?, passing_score = ?,
                            question_count = ?, is_active = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $title, $description, $category, $difficulty_level,
                        $time_limit, $passing_score, $question_count, $is_active,
                        $assessment_id
                    ]);
                } else {
                    // Create assessment
                    $stmt = $pdo->prepare("
                        INSERT INTO assessments (
                            title, description, category, difficulty_level,
                            time_limit, passing_score, question_count, is_active, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $title, $description, $category, $difficulty_level,
                        $time_limit, $passing_score, $question_count, $is_active
                    ]);
                    $assessment_id = $pdo->lastInsertId();
                    $is_edit = true;
                }
                
                // Handle questions
                if (isset($_POST['questions']) && is_array($_POST['questions'])) {
                    // Delete existing questions
                    $stmt = $pdo->prepare("DELETE FROM assessment_questions WHERE assessment_id = ?");
                    $stmt->execute([$assessment_id]);
                    
                    // Insert new questions
                    $stmt = $pdo->prepare("
                        INSERT INTO assessment_questions (
                            assessment_id, question_text, question_type, options,
                            correct_answer, explanation, points, question_order
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $order = 1;
                    foreach ($_POST['questions'] as $q) {
                        if (empty($q['question_text'])) continue;
                        
                        $question_text = sanitizeInput($q['question_text']);
                        $question_type = sanitizeInput($q['question_type'] ?? 'multiple_choice');
                        $correct_answer = sanitizeInput($q['correct_answer'] ?? '');
                        $explanation = sanitizeInput($q['explanation'] ?? '');
                        $points = intval($q['points'] ?? 1);
                        
                        // Handle options
                        $options = [];
                        if ($question_type === 'multiple_choice' && isset($q['options'])) {
                            foreach ($q['options'] as $opt) {
                                if (!empty($opt)) {
                                    $options[] = sanitizeInput($opt);
                                }
                            }
                        }
                        $options_json = json_encode($options);
                        
                        $stmt->execute([
                            $assessment_id, $question_text, $question_type,
                            $options_json, $correct_answer, $explanation,
                            $points, $order
                        ]);
                        $order++;
                    }
                    
                    // Update question count
                    $actual_count = $order - 1;
                    $pdo->prepare("UPDATE assessments SET question_count = ? WHERE id = ?")
                        ->execute([$actual_count, $assessment_id]);
                }
                
                $pdo->commit();
                $success_message = "Assessment saved successfully!";
                
                // Reload data
                $stmt = $pdo->prepare("SELECT * FROM assessments WHERE id = ?");
                $stmt->execute([$assessment_id]);
                $assessment = $stmt->fetch();
                
                $stmt = $pdo->prepare("SELECT * FROM assessment_questions WHERE assessment_id = ? ORDER BY question_order");
                $stmt->execute([$assessment_id]);
                $questions = $stmt->fetchAll();
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_message = "Error saving assessment: " . $e->getMessage();
            }
        } else {
            $error_message = implode('<br>', $errors);
        }
    }
}

$additional_css = "
    .editor-container { max-width: 1200px; margin: 0 auto; padding: 2rem 1rem; }
    .editor-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
    .editor-form { background: white; border-radius: var(--border-radius-lg); box-shadow: var(--shadow-md); padding: 2rem; }
    .form-section { margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px solid var(--gray-200); }
    .form-section:last-of-type { border-bottom: none; }
    .section-title { font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem; color: var(--gray-900); }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .form-group-full { grid-column: 1 / -1; }
    .questions-container { margin-top: 2rem; }
    .question-card { background: var(--gray-50); border: 2px solid var(--gray-200); border-radius: var(--border-radius-lg); padding: 1.5rem; margin-bottom: 1rem; position: relative; }
    .question-card.dragging { opacity: 0.5; }
    .question-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
    .question-number { font-weight: 600; color: var(--primary-blue); font-size: 1.1rem; }
    .question-actions { display: flex; gap: 0.5rem; }
    .drag-handle { cursor: move; color: var(--gray-400); padding: 0.5rem; }
    .drag-handle:hover { color: var(--gray-600); }
    .options-list { margin-top: 1rem; }
    .option-item { display: flex; gap: 0.5rem; margin-bottom: 0.5rem; align-items: center; }
    .option-item input[type='text'] { flex: 1; }
    .option-item .btn-danger { padding: 0.5rem; }
    .add-option-btn { margin-top: 0.5rem; }
    .action-buttons { display: flex; gap: 1rem; justify-content: space-between; margin-top: 2rem; padding-top: 2rem; border-top: 2px solid var(--gray-200); }
    @media (max-width: 768px) {
        .form-grid { grid-template-columns: 1fr; }
        .action-buttons { flex-direction: column; }
    }
";

include 'includes/header.php';
?>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/header.css">
<link rel="stylesheet" href="../assets/css/admin.css">

<div class="editor-container">
    <div class="editor-header">
        <h1><?php echo $is_edit ? 'Edit Assessment' : 'Create New Assessment'; ?></h1>
        <a href="assessments.php" class="btn btn-secondary">Back to Assessments</a>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?></div>
    <?php endif; ?>

    <form method="POST" class="editor-form" id="assessment-form">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="question_count" id="question-count" value="<?php echo count($questions); ?>">
        
        <!-- Basic Information -->
        <div class="form-section">
            <h2 class="section-title">Assessment Information</h2>
            
            <div class="form-group-full">
                <label class="form-label">Title *</label>
                <input type="text" name="title" class="form-control" 
                       value="<?php echo htmlspecialchars($assessment['title'] ?? ''); ?>" 
                       placeholder="e.g., Introduction to Butterfly Anatomy" required>
            </div>
            
            <div class="form-group-full">
                <label class="form-label">Description *</label>
                <textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($assessment['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-control">
                        <option value="">General</option>
                        <option value="anatomy" <?php echo ($assessment['category'] ?? '') === 'anatomy' ? 'selected' : ''; ?>>Anatomy</option>
                        <option value="behavior" <?php echo ($assessment['category'] ?? '') === 'behavior' ? 'selected' : ''; ?>>Behavior</option>
                        <option value="ecology" <?php echo ($assessment['category'] ?? '') === 'ecology' ? 'selected' : ''; ?>>Ecology</option>
                        <option value="identification" <?php echo ($assessment['category'] ?? '') === 'identification' ? 'selected' : ''; ?>>Identification</option>
                        <option value="conservation" <?php echo ($assessment['category'] ?? '') === 'conservation' ? 'selected' : ''; ?>>Conservation</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Difficulty Level</label>
                    <select name="difficulty_level" class="form-control">
                        <option value="beginner" <?php echo ($assessment['difficulty_level'] ?? 'beginner') === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                        <option value="intermediate" <?php echo ($assessment['difficulty_level'] ?? '') === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                        <option value="advanced" <?php echo ($assessment['difficulty_level'] ?? '') === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Time Limit (minutes)</label>
                    <input type="number" name="time_limit" class="form-control" 
                           value="<?php echo htmlspecialchars($assessment['time_limit'] ?? '30'); ?>" min="1" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Passing Score (%)</label>
                    <input type="number" name="passing_score" class="form-control" 
                           value="<?php echo htmlspecialchars($assessment['passing_score'] ?? '70'); ?>" 
                           min="0" max="100" step="0.1" required>
                </div>
            </div>
            
            <div class="form-group-full">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <input type="checkbox" name="is_active" id="is_active" 
                           style="width: 20px; height: 20px;"
                           <?php echo ($assessment['is_active'] ?? 1) ? 'checked' : ''; ?>>
                    <label for="is_active" style="margin: 0; cursor: pointer;">
                        <strong>Active Assessment</strong> - Users can take this assessment
                    </label>
                </div>
            </div>
        </div>

        <!-- Questions Section -->
        <div class="form-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2 class="section-title" style="margin: 0;">Questions</h2>
                <button type="button" class="btn btn-primary" onclick="addQuestion()">
                    <i class="fas fa-plus"></i> Add Question
                </button>
            </div>
            
            <div class="questions-container" id="questions-container">
                <?php if (!empty($questions)): ?>
                    <?php foreach ($questions as $index => $question): ?>
                        <?php 
                        $options = json_decode($question['options'], true) ?? [];
                        ?>
                        <div class="question-card" data-index="<?php echo $index; ?>">
                            <div class="question-header">
                                <span class="question-number">
                                    <i class="fas fa-grip-vertical drag-handle"></i>
                                    Question <?php echo $index + 1; ?>
                                </span>
                                <div class="question-actions">
                                    <button type="button" class="btn btn-sm btn-danger" onclick="removeQuestion(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Question Text *</label>
                                <textarea name="questions[<?php echo $index; ?>][question_text]" class="form-control" rows="2" required><?php echo htmlspecialchars($question['question_text']); ?></textarea>
                            </div>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Question Type</label>
                                    <select name="questions[<?php echo $index; ?>][question_type]" class="form-control question-type-select" onchange="toggleOptions(this)">
                                        <option value="multiple_choice" <?php echo $question['question_type'] === 'multiple_choice' ? 'selected' : ''; ?>>Multiple Choice</option>
                                        <option value="true_false" <?php echo $question['question_type'] === 'true_false' ? 'selected' : ''; ?>>True/False</option>
                                        <option value="short_answer" <?php echo $question['question_type'] === 'short_answer' ? 'selected' : ''; ?>>Short Answer</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Points</label>
                                    <input type="number" name="questions[<?php echo $index; ?>][points]" class="form-control" 
                                           value="<?php echo $question['points']; ?>" min="1" required>
                                </div>
                            </div>
                            
                            <div class="options-section" style="<?php echo $question['question_type'] !== 'multiple_choice' ? 'display: none;' : ''; ?>">
                                <label class="form-label">Answer Options</label>
                                <div class="options-list">
                                    <?php foreach ($options as $opt_index => $option): ?>
                                        <div class="option-item">
                                            <input type="text" name="questions[<?php echo $index; ?>][options][]" 
                                                   class="form-control" value="<?php echo htmlspecialchars($option); ?>" 
                                                   placeholder="Option <?php echo $opt_index + 1; ?>">
                                            <button type="button" class="btn btn-sm btn-danger" onclick="removeOption(this)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-sm btn-secondary add-option-btn" onclick="addOption(this)">
                                    <i class="fas fa-plus"></i> Add Option
                                </button>
                            </div>
                            
                            <div class="form-group" style="margin-top: 1rem;">
                                <label class="form-label">Correct Answer *</label>
                                <input type="text" name="questions[<?php echo $index; ?>][correct_answer]" 
                                       class="form-control" value="<?php echo htmlspecialchars($question['correct_answer']); ?>" 
                                       placeholder="e.g., Option 1, True, or the correct answer" required>
                                <small style="color: var(--gray-500);">For multiple choice, enter the option text or number</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Explanation (optional)</label>
                                <textarea name="questions[<?php echo $index; ?>][explanation]" class="form-control" rows="2" 
                                          placeholder="Explain why this is the correct answer"><?php echo htmlspecialchars($question['explanation']); ?></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: var(--gray-500); padding: 2rem;">
                        No questions yet. Click "Add Question" to create your first question.
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="assessments.php" class="btn btn-secondary">Cancel</a>
            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-outline">Save as Draft</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $is_edit ? 'Update Assessment' : 'Create Assessment'; ?>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
let questionIndex = <?php echo count($questions); ?>;

function addQuestion() {
    const container = document.getElementById('questions-container');
    const emptyMsg = container.querySelector('p');
    if (emptyMsg) emptyMsg.remove();
    
    const questionCard = document.createElement('div');
    questionCard.className = 'question-card';
    questionCard.setAttribute('data-index', questionIndex);
    questionCard.innerHTML = `
        <div class="question-header">
            <span class="question-number">
                <i class="fas fa-grip-vertical drag-handle"></i>
                Question ${questionIndex + 1}
            </span>
            <div class="question-actions">
                <button type="button" class="btn btn-sm btn-danger" onclick="removeQuestion(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Question Text *</label>
            <textarea name="questions[${questionIndex}][question_text]" class="form-control" rows="2" required></textarea>
        </div>
        
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Question Type</label>
                <select name="questions[${questionIndex}][question_type]" class="form-control question-type-select" onchange="toggleOptions(this)">
                    <option value="multiple_choice">Multiple Choice</option>
                    <option value="true_false">True/False</option>
                    <option value="short_answer">Short Answer</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Points</label>
                <input type="number" name="questions[${questionIndex}][points]" class="form-control" value="1" min="1" required>
            </div>
        </div>
        
        <div class="options-section">
            <label class="form-label">Answer Options</label>
            <div class="options-list">
                <div class="option-item">
                    <input type="text" name="questions[${questionIndex}][options][]" class="form-control" placeholder="Option 1">
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeOption(this)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="option-item">
                    <input type="text" name="questions[${questionIndex}][options][]" class="form-control" placeholder="Option 2">
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeOption(this)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-secondary add-option-btn" onclick="addOption(this)">
                <i class="fas fa-plus"></i> Add Option
            </button>
        </div>
        
        <div class="form-group" style="margin-top: 1rem;">
            <label class="form-label">Correct Answer *</label>
            <input type="text" name="questions[${questionIndex}][correct_answer]" class="form-control" 
                   placeholder="e.g., Option 1, True, or the correct answer" required>
            <small style="color: var(--gray-500);">For multiple choice, enter the option text or number</small>
        </div>
        
        <div class="form-group">
            <label class="form-label">Explanation (optional)</label>
            <textarea name="questions[${questionIndex}][explanation]" class="form-control" rows="2" 
                      placeholder="Explain why this is the correct answer"></textarea>
        </div>
    `;
    
    container.appendChild(questionCard);
    questionIndex++;
    updateQuestionNumbers();
    updateQuestionCount();
}

function removeQuestion(btn) {
    if (confirm('Remove this question?')) {
        btn.closest('.question-card').remove();
        updateQuestionNumbers();
        updateQuestionCount();
        
        const container = document.getElementById('questions-container');
        if (container.children.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: var(--gray-500); padding: 2rem;">No questions yet. Click "Add Question" to create your first question.</p>';
        }
    }
}

function addOption(btn) {
    const optionsList = btn.previousElementSibling;
    const questionCard = btn.closest('.question-card');
    const index = questionCard.getAttribute('data-index');
    const optionCount = optionsList.children.length + 1;
    
    const optionItem = document.createElement('div');
    optionItem.className = 'option-item';
    optionItem.innerHTML = `
        <input type="text" name="questions[${index}][options][]" class="form-control" placeholder="Option ${optionCount}">
        <button type="button" class="btn btn-sm btn-danger" onclick="removeOption(this)">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    optionsList.appendChild(optionItem);
}

function removeOption(btn) {
    const optionsList = btn.closest('.options-list');
    if (optionsList.children.length > 2) {
        btn.closest('.option-item').remove();
    } else {
        alert('You must have at least 2 options for multiple choice questions.');
    }
}

function toggleOptions(select) {
    const questionCard = select.closest('.question-card');
    const optionsSection = questionCard.querySelector('.options-section');
    
    if (select.value === 'multiple_choice') {
        optionsSection.style.display = 'block';
    } else {
        optionsSection.style.display = 'none';
    }
}

function updateQuestionNumbers() {
    const questions = document.querySelectorAll('.question-card');
    questions.forEach((q, index) => {
        q.querySelector('.question-number').innerHTML = `
            <i class="fas fa-grip-vertical drag-handle"></i>
            Question ${index + 1}
        `;
    });
}

function updateQuestionCount() {
    const count = document.querySelectorAll('.question-card').length;
    document.getElementById('question-count').value = count;
}

// Form validation
document.getElementById('assessment-form').addEventListener('submit', function(e) {
    const questionCards = document.querySelectorAll('.question-card');
    
    if (questionCards.length === 0) {
        e.preventDefault();
        alert('Please add at least one question to the assessment.');
        return false;
    }
    
    // Validate each question
    let valid = true;
    questionCards.forEach((card, index) => {
        const questionText = card.querySelector('textarea[name*="[question_text]"]');
        const correctAnswer = card.querySelector('input[name*="[correct_answer]"]');
        
        if (!questionText.value.trim()) {
            valid = false;
            alert(`Question ${index + 1}: Question text is required.`);
            questionText.focus();
            return;
        }
        
        if (!correctAnswer.value.trim()) {
            valid = false;
            alert(`Question ${index + 1}: Correct answer is required.`);
            correctAnswer.focus();
            return;
        }
    });
    
    if (!valid) {
        e.preventDefault();
        return false;
    }
});

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    updateQuestionCount();
});
</script>