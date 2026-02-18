<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$teacherId = (int) $_SESSION['teacher_id'];
$examId = (int) ($_GET['exam_id'] ?? $_POST['exam_id'] ?? 0);

if ($examId < 1) {
    teacherFlash('error', 'Invalid exam selection.');
    header('Location: exams.php');
    exit;
}

$examStmt = $conn->prepare('SELECT id, title, subject, semester FROM exams WHERE id = ? AND (teacher_id = ? OR teacher_id IS NULL) LIMIT 1');
$examStmt->bind_param('ii', $examId, $teacherId);
$examStmt->execute();
$exam = $examStmt->get_result()->fetch_assoc();
$examStmt->close();

if (!$exam) {
    teacherFlash('error', 'Exam not found or access denied.');
    header('Location: exams.php');
    exit;
}

function teacherRedirectQuestions(int $examId): void
{
    header('Location: questions.php?exam_id=' . $examId);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!teacherVerifyCsrf($csrf)) {
        teacherFlash('error', 'Invalid request token.');
        teacherRedirectQuestions($examId);
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add_question') {
        $questionText = trim($_POST['question_text'] ?? '');
        $optionA = trim($_POST['option_a'] ?? '');
        $optionB = trim($_POST['option_b'] ?? '');
        $optionC = trim($_POST['option_c'] ?? '');
        $optionD = trim($_POST['option_d'] ?? '');
        $correctOption = strtoupper(trim($_POST['correct_option'] ?? ''));

        if ($questionText === '' || $optionA === '' || $optionB === '' || $optionC === '' || $optionD === '' || !in_array($correctOption, ['A', 'B', 'C', 'D'], true)) {
            teacherFlash('error', 'Please provide a valid MCQ question and correct option.');
            teacherRedirectQuestions($examId);
        }

        $stmt = $conn->prepare('INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('issssss', $examId, $questionText, $optionA, $optionB, $optionC, $optionD, $correctOption);
        $stmt->execute();
        $stmt->close();

        teacherFlash('success', 'Question added successfully.');
        teacherRedirectQuestions($examId);
    }

    if ($action === 'update_question') {
        $questionId = (int) ($_POST['question_id'] ?? 0);
        $questionText = trim($_POST['question_text'] ?? '');
        $optionA = trim($_POST['option_a'] ?? '');
        $optionB = trim($_POST['option_b'] ?? '');
        $optionC = trim($_POST['option_c'] ?? '');
        $optionD = trim($_POST['option_d'] ?? '');
        $correctOption = strtoupper(trim($_POST['correct_option'] ?? ''));

        if ($questionId < 1 || $questionText === '' || $optionA === '' || $optionB === '' || $optionC === '' || $optionD === '' || !in_array($correctOption, ['A', 'B', 'C', 'D'], true)) {
            teacherFlash('error', 'Invalid question update payload.');
            teacherRedirectQuestions($examId);
        }

        $stmt = $conn->prepare('UPDATE questions SET question_text = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_option = ? WHERE id = ? AND exam_id = ?');
        $stmt->bind_param('ssssssii', $questionText, $optionA, $optionB, $optionC, $optionD, $correctOption, $questionId, $examId);
        $stmt->execute();
        $stmt->close();

        teacherFlash('success', 'Question updated successfully.');
        teacherRedirectQuestions($examId);
    }

    if ($action === 'delete_question') {
        $questionId = (int) ($_POST['question_id'] ?? 0);
        if ($questionId > 0) {
            $stmt = $conn->prepare('DELETE FROM questions WHERE id = ? AND exam_id = ?');
            $stmt->bind_param('ii', $questionId, $examId);
            $stmt->execute();
            $stmt->close();
            teacherFlash('success', 'Question deleted successfully.');
        }
        teacherRedirectQuestions($examId);
    }
}

$listStmt = $conn->prepare('SELECT id, question_text, option_a, option_b, option_c, option_d, correct_option FROM questions WHERE exam_id = ? ORDER BY id ASC');
$listStmt->bind_param('i', $examId);
$listStmt->execute();
$questions = $listStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$listStmt->close();

teacherRenderHeader('Question Bank', 'exams');
?>

<?php if ($error = teacherFlash('error')): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success = teacherFlash('success')): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h2 class="h4 mb-1"><?= htmlspecialchars($exam['title']) ?></h2>
        <p class="text-secondary mb-0"><?= htmlspecialchars($exam['subject']) ?> | <?= htmlspecialchars($exam['semester']) ?></p>
    </div>
    <a href="exams.php" class="btn btn-outline-secondary">Back to Exams</a>
</div>

<div class="card content-card mb-4">
    <div class="card-body">
        <h3 class="h5 mb-3">Add MCQ Question</h3>
        <form method="post" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(teacherCsrfToken()) ?>">
            <input type="hidden" name="action" value="add_question">
            <input type="hidden" name="exam_id" value="<?= (int) $examId ?>">

            <div class="col-12">
                <label class="form-label">Question</label>
                <textarea name="question_text" class="form-control" rows="3" required></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Option A</label>
                <input type="text" name="option_a" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Option B</label>
                <input type="text" name="option_b" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Option C</label>
                <input type="text" name="option_c" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Option D</label>
                <input type="text" name="option_d" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Correct Option</label>
                <select class="form-select" name="correct_option" required>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                </select>
            </div>
            <div class="col-12">
                <button class="btn btn-indigo" type="submit">Add Question</button>
            </div>
        </form>
    </div>
</div>

<div class="card content-card">
    <div class="card-body">
        <h3 class="h5 mb-3">MCQ List (<?= count($questions) ?>)</h3>

        <?php if (empty($questions)): ?>
            <p class="text-secondary mb-0">No questions found for this exam.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Question</th>
                        <th>Correct</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($questions as $index => $question): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($question['question_text']) ?></td>
                            <td><span class="badge text-bg-success"><?= htmlspecialchars($question['correct_option']) ?></span></td>
                            <td class="d-flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    class="btn btn-primary btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editQuestionModal"
                                    data-id="<?= (int) $question['id'] ?>"
                                    data-question="<?= htmlspecialchars($question['question_text']) ?>"
                                    data-a="<?= htmlspecialchars($question['option_a']) ?>"
                                    data-b="<?= htmlspecialchars($question['option_b']) ?>"
                                    data-c="<?= htmlspecialchars($question['option_c']) ?>"
                                    data-d="<?= htmlspecialchars($question['option_d']) ?>"
                                    data-correct="<?= htmlspecialchars($question['correct_option']) ?>"
                                >Edit</button>
                                <form method="post" onsubmit="return confirm('Delete this question?');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(teacherCsrfToken()) ?>">
                                    <input type="hidden" name="action" value="delete_question">
                                    <input type="hidden" name="exam_id" value="<?= (int) $examId ?>">
                                    <input type="hidden" name="question_id" value="<?= (int) $question['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="editQuestionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Edit MCQ Question</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(teacherCsrfToken()) ?>">
                    <input type="hidden" name="action" value="update_question">
                    <input type="hidden" name="exam_id" value="<?= (int) $examId ?>">
                    <input type="hidden" name="question_id" id="edit_question_id">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Question</label>
                            <textarea name="question_text" id="edit_question_text" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Option A</label>
                            <input type="text" name="option_a" id="edit_option_a" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Option B</label>
                            <input type="text" name="option_b" id="edit_option_b" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Option C</label>
                            <input type="text" name="option_c" id="edit_option_c" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Option D</label>
                            <input type="text" name="option_d" id="edit_option_d" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Correct Option</label>
                            <select class="form-select" name="correct_option" id="edit_correct_option" required>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-indigo">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const editQuestionModal = document.getElementById('editQuestionModal');
if (editQuestionModal) {
    editQuestionModal.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        if (!button) return;

        document.getElementById('edit_question_id').value = button.getAttribute('data-id') || '';
        document.getElementById('edit_question_text').value = button.getAttribute('data-question') || '';
        document.getElementById('edit_option_a').value = button.getAttribute('data-a') || '';
        document.getElementById('edit_option_b').value = button.getAttribute('data-b') || '';
        document.getElementById('edit_option_c').value = button.getAttribute('data-c') || '';
        document.getElementById('edit_option_d').value = button.getAttribute('data-d') || '';
        document.getElementById('edit_correct_option').value = button.getAttribute('data-correct') || 'A';
    });
}
</script>

<?php teacherRenderFooter(); ?>
