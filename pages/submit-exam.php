<?php
require_once '../config/db.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: take-exam.php');
    exit;
}

$student_id = $_SESSION['student_id'];
$exam_id = intval($_POST['exam_id'] ?? 0);
$answers = $_POST['answer'] ?? [];

if ($exam_id <= 0) {
    header('Location: take-exam.php');
    exit;
}

// Check if already taken
$check = $conn->prepare("SELECT id FROM student_exams WHERE student_id = ? AND exam_id = ?");
$check->bind_param("ii", $student_id, $exam_id);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    header('Location: my-results.php');
    exit;
}

// Get correct answers
$q_stmt = $conn->prepare("SELECT id, correct_option FROM questions WHERE exam_id = ?");
$q_stmt->bind_param("i", $exam_id);
$q_stmt->execute();
$correct_answers = $q_stmt->get_result();

$total = 0;
$score = 0;
$corrections = [];

while ($q = $correct_answers->fetch_assoc()) {
    $total++;
    $selected = $answers[$q['id']] ?? '';
    $is_correct = (strtoupper($selected) === strtoupper($q['correct_option']));
    if ($is_correct) $score++;
    $corrections[$q['id']] = ['selected' => $selected, 'correct' => $q['correct_option']];
}

$percentage = $total > 0 ? round(($score / $total) * 100, 2) : 0;

// Save result
$stmt = $conn->prepare("INSERT INTO student_exams (student_id, exam_id, score, total, percentage) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iiiid", $student_id, $exam_id, $score, $total, $percentage);
$stmt->execute();
$student_exam_id = $conn->insert_id;

// Save individual answers
$ans_stmt = $conn->prepare("INSERT INTO student_answers (student_exam_id, question_id, selected_option) VALUES (?, ?, ?)");
foreach ($answers as $question_id => $selected_option) {
    $qid = intval($question_id);
    $sel = $selected_option;
    $ans_stmt->bind_param("iis", $student_exam_id, $qid, $sel);
    $ans_stmt->execute();
}

// Redirect to results
header('Location: my-results.php?submitted=1&exam_id=' . $exam_id);
exit;
?>
