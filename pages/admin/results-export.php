<?php
require_once __DIR__ . '/includes/auth.php';

$type = (string) ($_GET['type'] ?? '');
$resultId = (int) ($_GET['id'] ?? 0);
$classId = (int) ($_GET['class_id'] ?? 0);

if ($type === 'xls') {
    $stmt = $conn->prepare(
        "SELECT s.name AS student_name,
                COALESCE(c.name, s.department) AS class_name,
                e.title AS exam_title,
                se.score, se.total, se.percentage, se.submitted_at
         FROM student_exams se
         JOIN students s ON s.id = se.student_id
         JOIN exams e ON e.id = se.exam_id
         LEFT JOIN classes c ON c.id = s.class_id
         ORDER BY se.submitted_at DESC"
    );
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $filename = 'exampro-results-' . date('Ymd-His') . '.xls';
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "Student\tClass\tExam\tScore\tTotal\tPercentage\tStatus\tSubmitted\n";
    foreach ($rows as $row) {
        $status = ((float) $row['percentage'] >= 40) ? 'Pass' : 'Fail';
        echo implode("\t", [
            $row['student_name'],
            $row['class_name'],
            $row['exam_title'],
            $row['score'],
            $row['total'],
            $row['percentage'],
            $status,
            $row['submitted_at'],
        ]) . "\n";
    }
    exit;
}

if (!in_array($type, ['single', 'all', 'class'], true)) {
    header('Location: results.php');
    exit;
}

$fpdfPath = __DIR__ . '/../../lib/fpdf/fpdf.php';

function renderPrintHtml(string $title, string $generatedAt, array $headers, array $rows): void
{
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= htmlspecialchars($title) ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            @media print {
                .no-print { display: none !important; }
            }
        </style>
    </head>
    <body class="p-4">
        <div class="d-flex justify-content-between align-items-center mb-3 no-print">
            <h1 class="h4 mb-0"><?= htmlspecialchars($title) ?></h1>
            <button class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
        </div>
        <div class="mb-2 text-secondary">Generated: <?= htmlspecialchars($generatedAt) ?></div>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light">
                <tr>
                    <?php foreach ($headers as $header): ?>
                        <th><?= htmlspecialchars($header) ?></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="<?= count($headers) ?>" class="text-center text-secondary">No results found.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php foreach ($row as $cell): ?>
                                <td><?= htmlspecialchars((string) $cell) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </body>
    </html>
    <?php
}

if (!file_exists($fpdfPath)) {
    $generatedAt = date('Y-m-d H:i:s');
    if ($type === 'single') {
        if ($resultId <= 0) {
            header('Location: results.php');
            exit;
        }
        $stmt = $conn->prepare(
            "SELECT se.id, se.score, se.total, se.percentage, se.submitted_at,
                    s.name AS student_name, s.email, COALESCE(c.name, s.department) AS class_name, s.roll_number,
                    e.title AS exam_title, e.subject, e.exam_date
             FROM student_exams se
             JOIN students s ON s.id = se.student_id
             JOIN exams e ON e.id = se.exam_id
             LEFT JOIN classes c ON c.id = s.class_id
             WHERE se.id = ?
             LIMIT 1"
        );
        $stmt->bind_param('i', $resultId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            header('Location: results.php');
            exit;
        }
        $status = ((float) $row['percentage'] >= 40) ? 'Pass' : 'Fail';
        $headers = ['Student', 'Class', 'Exam', 'Score', 'Total', 'Percentage', 'Status', 'Submitted'];
        $rows = [[
            $row['student_name'],
            $row['class_name'],
            $row['exam_title'],
            $row['score'],
            $row['total'],
            $row['percentage'] . '%',
            $status,
            $row['submitted_at'],
        ]];
        renderPrintHtml('ExamPro Result Report', $generatedAt, $headers, $rows);
        exit;
    }

    $sql = "SELECT s.name AS student_name,
                   COALESCE(c.name, s.department) AS class_name,
                   e.title AS exam_title,
                   se.score, se.total, se.percentage, se.submitted_at
            FROM student_exams se
            JOIN students s ON s.id = se.student_id
            JOIN exams e ON e.id = se.exam_id
            LEFT JOIN classes c ON c.id = s.class_id";
    if ($type === 'class' && $classId > 0) {
        $sql .= " WHERE s.class_id = ?";
    }
    $sql .= " ORDER BY se.submitted_at DESC";

    $stmt = $conn->prepare($sql);
    if ($type === 'class' && $classId > 0) {
        $stmt->bind_param('i', $classId);
    }
    $stmt->execute();
    $rowsDb = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $headers = ['Student', 'Class', 'Exam', 'Score', 'Total', 'Percentage', 'Status', 'Submitted'];
    $rows = [];
    foreach ($rowsDb as $row) {
        $status = ((float) $row['percentage'] >= 40) ? 'Pass' : 'Fail';
        $rows[] = [
            $row['student_name'],
            $row['class_name'],
            $row['exam_title'],
            $row['score'],
            $row['total'],
            $row['percentage'] . '%',
            $status,
            $row['submitted_at'],
        ];
    }
    renderPrintHtml('ExamPro Result Report', $generatedAt, $headers, $rows);
    exit;
}

require_once $fpdfPath;

if ($type === 'single') {
    if ($resultId <= 0) {
        header('Location: results.php');
        exit;
    }
    $stmt = $conn->prepare(
        "SELECT se.id, se.score, se.total, se.percentage, se.submitted_at,
                s.name AS student_name, s.email, COALESCE(c.name, s.department) AS class_name, s.roll_number,
                e.title AS exam_title, e.subject, e.exam_date
         FROM student_exams se
         JOIN students s ON s.id = se.student_id
         JOIN exams e ON e.id = se.exam_id
         LEFT JOIN classes c ON c.id = s.class_id
         WHERE se.id = ?
         LIMIT 1"
    );
    $stmt->bind_param('i', $resultId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        header('Location: results.php');
        exit;
    }

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
    $pdf->Cell(0, 6, 'Class: ' . $row['class_name'], 0, 1);
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

    $status = ((float) $row['percentage'] >= 40) ? 'Pass' : 'Fail';
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
}

// All results PDF (optionally filtered by class)
$sql = "SELECT s.name AS student_name,
               COALESCE(c.name, s.department) AS class_name,
               e.title AS exam_title,
               se.score, se.total, se.percentage, se.submitted_at
        FROM student_exams se
        JOIN students s ON s.id = se.student_id
        JOIN exams e ON e.id = se.exam_id
        LEFT JOIN classes c ON c.id = s.class_id";
if ($type === 'class' && $classId > 0) {
    $sql .= " WHERE s.class_id = ?";
}
    $sql .= " ORDER BY se.submitted_at DESC";

$stmt = $conn->prepare($sql);
if ($type === 'class' && $classId > 0) {
    $stmt->bind_param('i', $classId);
}
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'ExamPro Result Report', 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
$pdf->Ln(4);

$pdf->SetFont('Arial', 'B', 9);
$widths = [40, 25, 60, 15, 15, 20, 18, 35];
$headers = ['Student', 'Class', 'Exam', 'Score', 'Total', 'Percent', 'Status', 'Submitted'];
foreach ($headers as $i => $header) {
    $pdf->Cell($widths[$i], 7, $header, 1, 0, 'C');
}
$pdf->Ln();

$pdf->SetFont('Arial', '', 9);
foreach ($rows as $row) {
    $status = ((float) $row['percentage'] >= 40) ? 'Pass' : 'Fail';
    $cells = [
        $row['student_name'],
        $row['class_name'],
        $row['exam_title'],
        $row['score'],
        $row['total'],
        $row['percentage'] . '%',
        $status,
        $row['submitted_at'],
    ];
    foreach ($cells as $i => $cell) {
        $pdf->Cell($widths[$i], 7, (string) $cell, 1, 0, 'C');
    }
    $pdf->Ln();
}

$suffix = $type === 'class' ? '-class-' . $classId : '';
$filename = 'exampro-results' . $suffix . '-' . date('Ymd-His') . '.pdf';
$pdf->Output('D', $filename);
exit;
