<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

adminRenderHeader('Reports', 'reports');
?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card content-card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Exam-wise Average Percentage</h2>
                <canvas id="examAvgChart" height="160"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card content-card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Pass vs Fail Ratio</h2>
                <canvas id="passFailChart" height="160"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card content-card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Performance Trend by Exam Date</h2>
                <canvas id="trendChart" height="160"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card content-card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Class-wise Performance</h2>
                <canvas id="classChart" height="160"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
async function loadReports() {
    const res = await fetch('reports-data.php', { credentials: 'same-origin' });
    const data = await res.json();

    new Chart(document.getElementById('examAvgChart'), {
        type: 'bar',
        data: {
            labels: data.examAvg.labels,
            datasets: [{
                label: 'Average %',
                data: data.examAvg.values,
                backgroundColor: '#4f46e5'
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });

    new Chart(document.getElementById('passFailChart'), {
        type: 'pie',
        data: {
            labels: ['Pass', 'Fail'],
            datasets: [{
                data: [data.passFail.pass, data.passFail.fail],
                backgroundColor: ['#16a34a', '#dc2626']
            }]
        },
        options: { responsive: true }
    });

    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: data.trend.labels,
            datasets: [{
                label: 'Average %',
                data: data.trend.values,
                borderColor: '#0ea5e9',
                backgroundColor: 'rgba(14,165,233,0.1)',
                tension: 0.3,
                fill: true
            }]
        },
        options: { responsive: true }
    });

    new Chart(document.getElementById('classChart'), {
        type: 'doughnut',
        data: {
            labels: data.classAvg.labels,
            datasets: [{
                data: data.classAvg.values,
                backgroundColor: ['#f59e0b', '#10b981', '#6366f1', '#ef4444', '#14b8a6', '#8b5cf6']
            }]
        },
        options: { responsive: true }
    });
}

loadReports().catch(console.error);
</script>

<?php adminRenderFooter(); ?>
