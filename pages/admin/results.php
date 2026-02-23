<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

function bindParams(mysqli_stmt $stmt, string $types, array $params): void
{
    if ($types === '') {
        return;
    }
    $bind = [$types];
    foreach ($params as $i => $value) {
        $bind[] = &$params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind);
}

$search = trim((string) ($_GET['q'] ?? ''));
$classId = (int) ($_GET['class_id'] ?? 0);
$examId = (int) ($_GET['exam_id'] ?? 0);
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where = ' WHERE 1=1 ';
$types = '';
$params = [];

if ($search !== '') {
    $where .= ' AND s.name LIKE ? ';
    $types .= 's';
    $params[] = '%' . $search . '%';
}
if ($classId > 0) {
    $where .= ' AND s.class_id = ? ';
    $types .= 'i';
    $params[] = $classId;
}
if ($examId > 0) {
    $where .= ' AND se.exam_id = ? ';
    $types .= 'i';
    $params[] = $examId;
}
if ($dateFrom !== '') {
    $where .= ' AND DATE(se.submitted_at) >= ? ';
    $types .= 's';
    $params[] = $dateFrom;
}
if ($dateTo !== '') {
    $where .= ' AND DATE(se.submitted_at) <= ? ';
    $types .= 's';
    $params[] = $dateTo;
}

// Filters data
$classes = [];
$classStmt = $conn->prepare('SELECT id, name FROM classes ORDER BY name');
if ($classStmt && $classStmt->execute()) {
    $classes = $classStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
$classStmt?->close();

$exams = [];
$examStmt = $conn->prepare('SELECT id, title FROM exams ORDER BY created_at DESC');
if ($examStmt && $examStmt->execute()) {
    $exams = $examStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
$examStmt?->close();

// Summary
$summarySql = "
    SELECT
        COUNT(DISTINCT se.student_id) AS total_students,
        COALESCE(AVG(se.percentage), 0) AS avg_percentage,
        COALESCE(MAX(se.percentage), 0) AS max_percentage,
        COALESCE(MIN(se.percentage), 0) AS min_percentage
    FROM student_exams se
    JOIN students s ON s.id = se.student_id
    JOIN exams e ON e.id = se.exam_id
    LEFT JOIN classes c ON c.id = s.class_id
    $where
";
$summaryStmt = $conn->prepare($summarySql);
bindParams($summaryStmt, $types, $params);
$summaryStmt->execute();
$summary = $summaryStmt->get_result()->fetch_assoc() ?: [
    'total_students' => 0,
    'avg_percentage' => 0,
    'max_percentage' => 0,
    'min_percentage' => 0,
];
$summaryStmt->close();

// Total rows for pagination
$countSql = "
    SELECT COUNT(*) AS total_rows
    FROM student_exams se
    JOIN students s ON s.id = se.student_id
    JOIN exams e ON e.id = se.exam_id
    LEFT JOIN classes c ON c.id = s.class_id
    $where
";
$countStmt = $conn->prepare($countSql);
bindParams($countStmt, $types, $params);
$countStmt->execute();
$totalRows = (int) (($countStmt->get_result()->fetch_assoc()['total_rows']) ?? 0);
$countStmt->close();
$totalPages = max(1, (int) ceil($totalRows / $limit));

// List
$listSql = "
    SELECT
        se.id,
        s.name AS student_name,
        COALESCE(c.name, s.department) AS class_name,
        e.title AS exam_title,
        se.score,
        se.total,
        se.percentage,
        se.submitted_at
    FROM student_exams se
    JOIN students s ON s.id = se.student_id
    JOIN exams e ON e.id = se.exam_id
    LEFT JOIN classes c ON c.id = s.class_id
    $where
    ORDER BY se.id DESC
    LIMIT ? OFFSET ?
";
$listStmt = $conn->prepare($listSql);
$listTypes = $types . 'ii';
$listParams = array_merge($params, [$limit, $offset]);
bindParams($listStmt, $listTypes, $listParams);
$listStmt->execute();
$results = $listStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$listStmt->close();

$passThreshold = 40;

adminRenderHeader('Results', 'results');
?>

<?php if ($error = adminFlash('error')): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success = adminFlash('success')): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card content-card h-100">
            <div class="card-body">
                <div class="text-secondary small">Total Students Appeared</div>
                <div class="h3 fw-bold mb-0"><?= (int) $summary['total_students'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card content-card h-100">
            <div class="card-body">
                <div class="text-secondary small">Average Percentage</div>
                <div class="h3 fw-bold mb-0"><?= round((float) $summary['avg_percentage'], 2) ?>%</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card content-card h-100">
            <div class="card-body">
                <div class="text-secondary small">Highest Score</div>
                <div class="h3 fw-bold mb-0"><?= round((float) $summary['max_percentage'], 2) ?>%</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card content-card h-100">
            <div class="card-body">
                <div class="text-secondary small">Lowest Score</div>
                <div class="h3 fw-bold mb-0"><?= round((float) $summary['min_percentage'], 2) ?>%</div>
            </div>
        </div>
    </div>
</div>

<div class="card content-card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Search Student</label>
                <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Student name">
            </div>
            <div class="col-md-2">
                <label class="form-label">Class</label>
                <select name="class_id" class="form-select">
                    <option value="0">All</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?= (int) $class['id'] ?>" <?= $classId === (int) $class['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) $class['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Exam</label>
                <select name="exam_id" class="form-select">
                    <option value="0">All</option>
                    <?php foreach ($exams as $exam): ?>
                        <option value="<?= (int) $exam['id'] ?>" <?= $examId === (int) $exam['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) $exam['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo) ?>">
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-indigo">Apply Filters</button>
                <a href="results.php" class="btn btn-outline-secondary">Reset</a>
                <a href="results-export.php?type=all" class="btn btn-outline-primary ms-auto">Download All PDF</a>
                <a href="results-export.php?type=xls" class="btn btn-outline-success">Export Excel</a>
            </div>
        </form>
    </div>
</div>

<div class="card content-card">
    <div class="card-body">
        <h2 class="h4 mb-3">Results List</h2>
        <div class="table-responsive table-shell">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Exam</th>
                    <th>Score</th>
                    <th>Total</th>
                    <th>Percentage</th>
                    <th>Status</th>
                    <th>Submitted Date</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($results)): ?>
                    <tr><td colspan="9" class="text-center text-secondary">No results found.</td></tr>
                <?php else: ?>
                    <?php foreach ($results as $result): ?>
                        <?php $status = ((float) $result['percentage'] >= $passThreshold) ? 'Pass' : 'Fail'; ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $result['student_name']) ?></td>
                            <td><?= htmlspecialchars((string) $result['class_name']) ?></td>
                            <td><?= htmlspecialchars((string) $result['exam_title']) ?></td>
                            <td><?= (int) $result['score'] ?></td>
                            <td><?= (int) $result['total'] ?></td>
                            <td><?= htmlspecialchars((string) $result['percentage']) ?>%</td>
                            <td>
                                <span class="badge <?= $status === 'Pass' ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $status ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars((string) $result['submitted_at']) ?></td>
                            <td class="text-nowrap">
                                <a href="result-view.php?result_id=<?= (int) $result['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a>
                                <a href="results-export.php?type=single&id=<?= (int) $result['id'] ?>" class="btn btn-sm btn-outline-primary">PDF</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination mb-0">
                    <?php
                    $query = $_GET;
                    for ($i = 1; $i <= $totalPages; $i++):
                        $query['page'] = $i;
                        $url = 'results.php?' . http_build_query($query);
                        ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= htmlspecialchars($url) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php adminRenderFooter(); ?>
