<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$listStmt = $conn->prepare('SELECT se.id, s.name AS student_name, e.title AS exam_title, se.score, se.total, se.percentage, se.submitted_at FROM student_exams se JOIN students s ON s.id = se.student_id JOIN exams e ON e.id = se.exam_id ORDER BY se.id DESC LIMIT ?');
$limit = 25;
$listStmt->bind_param('i', $limit);
$listStmt->execute();
$results = $listStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$listStmt->close();

adminRenderHeader('Results', 'results');
?>

<div class="card content-card">
    <div class="card-body">
        <h2 class="h4 mb-3">Results List</h2>
        <div class="table-responsive table-shell">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Student</th>
                    <th>Exam</th>
                    <th>Score</th>
                    <th>Total</th>
                    <th>Percentage</th>
                    <th>Submitted</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($results)): ?>
                    <tr><td colspan="6" class="text-center text-secondary">No results found.</td></tr>
                <?php else: ?>
                    <?php foreach ($results as $result): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $result['student_name']) ?></td>
                            <td><?= htmlspecialchars((string) $result['exam_title']) ?></td>
                            <td><?= (int) $result['score'] ?></td>
                            <td><?= (int) $result['total'] ?></td>
                            <td><?= htmlspecialchars((string) $result['percentage']) ?>%</td>
                            <td><?= htmlspecialchars((string) $result['submitted_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php adminRenderFooter(); ?>
