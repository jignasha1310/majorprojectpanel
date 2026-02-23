<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$stmt = $conn->prepare(
    "SELECT c.id, c.name, t.name AS teacher_name,
            COUNT(DISTINCT s.id) AS total_students,
            COALESCE(AVG(se.percentage), 0) AS avg_percentage
     FROM classes c
     LEFT JOIN teachers t ON t.id = c.teacher_id
     LEFT JOIN students s ON s.class_id = c.id
     LEFT JOIN student_exams se ON se.student_id = s.id
     GROUP BY c.id
     ORDER BY c.name"
);
$stmt->execute();
$classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

adminRenderHeader('Classes', 'classes');
?>

<div class="card content-card">
    <div class="card-body">
        <h2 class="h4 mb-3">Classes Overview</h2>
        <?php if (empty($classes)): ?>
            <div class="text-secondary">No classes found. Add classes in the database to see analytics.</div>
        <?php else: ?>
            <div class="table-responsive table-shell">
                <table class="table align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Class Name</th>
                        <th>Teacher Name</th>
                        <th>Total Students</th>
                        <th>Average Performance</th>
                        <th>Progress</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($classes as $class): ?>
                        <?php $avg = round((float) $class['avg_percentage'], 2); ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $class['name']) ?></td>
                            <td><?= htmlspecialchars((string) ($class['teacher_name'] ?? 'Unassigned')) ?></td>
                            <td><?= (int) $class['total_students'] ?></td>
                            <td><?= $avg ?>%</td>
                            <td style="min-width: 160px;">
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $avg ?>%;" aria-valuenow="<?= $avg ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <a href="students.php?class_id=<?= (int) $class['id'] ?>" class="btn btn-sm btn-outline-secondary">View Students</a>
                                <a href="results.php?class_id=<?= (int) $class['id'] ?>" class="btn btn-sm btn-outline-primary">View Results</a>
                                <a href="results-export.php?type=class&class_id=<?= (int) $class['id'] ?>" class="btn btn-sm btn-outline-success">Generate Report</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php adminRenderFooter(); ?>
