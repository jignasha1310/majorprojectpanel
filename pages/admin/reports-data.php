<?php
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

$examAvg = ['labels' => [], 'values' => []];
$stmt = $conn->prepare(
    "SELECT e.title, COALESCE(AVG(se.percentage), 0) AS avg_pct
     FROM exams e
     LEFT JOIN student_exams se ON se.exam_id = e.id
     GROUP BY e.id
     ORDER BY e.exam_date"
);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
foreach ($rows as $row) {
    $examAvg['labels'][] = $row['title'];
    $examAvg['values'][] = round((float) $row['avg_pct'], 2);
}

$passFail = ['pass' => 0, 'fail' => 0];
$stmt = $conn->prepare(
    "SELECT
        SUM(CASE WHEN percentage >= 40 THEN 1 ELSE 0 END) AS pass_count,
        SUM(CASE WHEN percentage < 40 THEN 1 ELSE 0 END) AS fail_count
     FROM student_exams"
);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$passFail['pass'] = (int) ($row['pass_count'] ?? 0);
$passFail['fail'] = (int) ($row['fail_count'] ?? 0);

$trend = ['labels' => [], 'values' => []];
$stmt = $conn->prepare(
    "SELECT e.exam_date, COALESCE(AVG(se.percentage), 0) AS avg_pct
     FROM exams e
     LEFT JOIN student_exams se ON se.exam_id = e.id
     GROUP BY e.exam_date
     ORDER BY e.exam_date"
);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
foreach ($rows as $row) {
    $trend['labels'][] = $row['exam_date'];
    $trend['values'][] = round((float) $row['avg_pct'], 2);
}

$classAvg = ['labels' => [], 'values' => []];
$stmt = $conn->prepare(
    "SELECT c.name, COALESCE(AVG(se.percentage), 0) AS avg_pct
     FROM classes c
     LEFT JOIN students s ON s.class_id = c.id
     LEFT JOIN student_exams se ON se.student_id = s.id
     GROUP BY c.id
     ORDER BY c.name"
);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
foreach ($rows as $row) {
    $classAvg['labels'][] = $row['name'];
    $classAvg['values'][] = round((float) $row['avg_pct'], 2);
}

echo json_encode([
    'examAvg' => $examAvg,
    'passFail' => $passFail,
    'trend' => $trend,
    'classAvg' => $classAvg,
]);
