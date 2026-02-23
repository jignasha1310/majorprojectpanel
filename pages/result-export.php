<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}

$studentId = (int) $_SESSION['student_id'];
$resultId = (int) ($_GET['se_id'] ?? 0);
if ($resultId <= 0) {
    header('Location: my-results.php');
    exit;
}

$fpdfPath = __DIR__ . '/../lib/fpdf/fpdf.php';

function renderStudentPrintHtml(array $row): void
{
    $status = ((float) $row['percentage'] >= 40) ? 'Pass' : 'Fail';
    $generatedAt = date('Y-m-d H:i:s');
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>ExamPro Result Report</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            @media print {
                .no-print { display: none !important; }
            }
        </style>
    </head>
    <body class="p-4">
        <div class="d-flex justify-content-between align-items-center mb-3 no-print">
            <h1 class="h4 mb-0">ExamPro Result Report</h1>
            <button class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
        </div>
        <div class="mb-2 text-secondary">Generated: <?= htmlspecialchars($generatedAt) ?></div>
        <div class="table-responsive">
            <table class="table table-bordered">
                <tbody>
                <tr><th>Student</th><td><?= htmlspecialchars((string) $row['student_name']) ?></td></tr>
                <tr><th>Email</th><td><?= htmlspecialchars((string) $row['email']) ?></td></tr>
                <tr><th>Class</th><td><?= htmlspecialchars((string) $row['department']) ?></td></tr>
                <tr><th>Roll No</th><td><?= htmlspecialchars((string) $row['roll_number']) ?></td></tr>
                <tr><th>Exam</th><td><?= htmlspecialchars((string) $row['exam_title']) ?></td></tr>
                <tr><th>Subject</th><td><?= htmlspecialchars((string) $row['subject']) ?></td></tr>
                <tr><th>Exam Date</th><td><?= htmlspecialchars((string) $row['exam_date']) ?></td></tr>
                <tr><th>Submitted</th><td><?= htmlspecialchars((string) $row['submitted_at']) ?></td></tr>
                <tr><th>Score</th><td><?= (int) $row['score'] ?> / <?= (int) $row['total'] ?></td></tr>
                <tr><th>Percentage</th><td><?= htmlspecialchars((string) $row['percentage']) ?>%</td></tr>
                <tr><th>Status</th><td><?= htmlspecialchars($status) ?></td></tr>
                </tbody>
            </table>
        </div>
    </body>
    </html>
    <?php
}

if (!file_exists($fpdfPath)) {
    $stmt = $conn->prepare(
        "SELECT se.id, se.score, se.total, se.percentage, se.submitted_at,
                s.name AS student_name, s.email, s.department, s.roll_number,
                e.title AS exam_title, e.subject, e.exam_date
         FROM student_exams se
         JOIN students s ON s.id = se.student_id
         JOIN exams e ON e.id = se.exam_id
         WHERE se.id = ? AND se.student_id = ?
         LIMIT 1"
    );
    $stmt->bind_param('ii', $resultId, $studentId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        header('Location: my-results.php');
        exit;
    }

    renderStudentPrintHtml($row);
    exit;
}

require_once $fpdfPath;

$stmt = $conn->prepare(
    "SELECT se.id, se.score, se.total, se.percentage, se.submitted_at,
            s.name AS student_name, s.email, s.department, s.roll_number,
            e.title AS exam_title, e.subject, e.exam_date
     FROM student_exams se
     JOIN students s ON s.id = se.student_id
     JOIN exams e ON e.id = se.exam_id
     WHERE se.id = ? AND se.student_id = ?
     LIMIT 1"
);
$stmt->bind_param('ii', $resultId, $studentId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    header('Location: my-results.php');
    exit;
}

$status = ((float) $row['percentage'] >= 40) ? 'Pass' : 'Fail';

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'ExamPro Result Report', 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
$pdf->Ln(4);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'Student Information', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Name: ' . $row['student_name'], 0, 1);
$pdf->Cell(0, 6, 'Email: ' . $row['email'], 0, 1);
$pdf->Cell(0, 6, 'Class: ' . $row['department'], 0, 1);
$pdf->Cell(0, 6, 'Roll No: ' . $row['roll_number'], 0, 1);
$pdf->Ln(3);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'Exam Information', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Exam: ' . $row['exam_title'], 0, 1);
$pdf->Cell(0, 6, 'Subject: ' . $row['subject'], 0, 1);
$pdf->Cell(0, 6, 'Exam Date: ' . $row['exam_date'], 0, 1);
$pdf->Cell(0, 6, 'Submitted: ' . $row['submitted_at'], 0, 1);
$pdf->Ln(3);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'Result Summary', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Score: ' . $row['score'] . ' / ' . $row['total'], 0, 1);
$pdf->Cell(0, 6, 'Percentage: ' . $row['percentage'] . '%', 0, 1);
$pdf->Cell(0, 6, 'Status: ' . $status, 0, 1);

$filename = 'result-' . $row['student_name'] . '-' . $row['exam_title'] . '.pdf';
$filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);
$pdf->Output('D', $filename);
exit;
