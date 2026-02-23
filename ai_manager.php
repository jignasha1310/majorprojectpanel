<?php
declare(strict_types=1);

// =========================
// Configuration
// =========================
// 1) Database credentials
$dbHost = 'localhost';
$dbName = 'exampro_db';
$dbUser = 'root';
$dbPass = '';

// 2) Gemini API key
// Put your Gemini API key here. Example: $geminiApiKey = 'YOUR_API_KEY';
$geminiApiKey = '';

// =========================
// Database connection (PDO)
// =========================
$pdoDsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
$pdo = new PDO($pdoDsn, $dbUser, $dbPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

// Auto-create table (note: correct_answer column)
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS exam_questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        topic VARCHAR(120) NOT NULL,
        question TEXT NOT NULL,
        a VARCHAR(255) NOT NULL,
        b VARCHAR(255) NOT NULL,
        c VARCHAR(255) NOT NULL,
        d VARCHAR(255) NOT NULL,
        correct_answer CHAR(1) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

// =========================
// Helpers
// =========================
function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function fetchGeminiMcqs(string $topic, int $count, string $apiKey): array
{
    if ($apiKey === '') {
        return [false, 'Missing Gemini API key.'];
    }

    $count = max(1, min(20, $count));
    $prompt = "Generate exactly {$count} multiple-choice questions about: {$topic}. "
        . "Return ONLY valid JSON in this exact format: "
        . "[{\"question\":\"...\",\"a\":\"...\",\"b\":\"...\",\"c\":\"...\",\"d\":\"...\",\"correct_answer\":\"A\"}, ...]. "
        . "correct_answer must be one of A,B,C,D.";

    $payload = [
        'contents' => [[
            'parts' => [[ 'text' => $prompt ]]
        ]]
    ];

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . urlencode($apiKey);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30,
    ]);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($raw === false || $err) {
        return [false, 'cURL error: ' . $err];
    }

    $response = json_decode($raw, true);
    $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if ($text === '') {
        return [false, 'Empty response from Gemini.'];
    }

    $json = json_decode($text, true);
    if (!is_array($json)) {
        return [false, 'Gemini response was not valid JSON.'];
    }

    return [true, $json];
}

function saveQuestions(PDO $pdo, string $topic, array $items): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO exam_questions (topic, question, a, b, c, d, correct_answer) VALUES (:topic, :question, :a, :b, :c, :d, :correct_answer)'
    );
    $count = 0;
    foreach ($items as $item) {
        if (
            !isset($item['question'], $item['a'], $item['b'], $item['c'], $item['d'], $item['correct_answer'])
        ) {
            continue;
        }
        $answer = strtoupper(trim((string) $item['correct_answer']));
        if (!in_array($answer, ['A', 'B', 'C', 'D'], true)) {
            continue;
        }
        $stmt->execute([
            ':topic' => $topic,
            ':question' => trim((string) $item['question']),
            ':a' => trim((string) $item['a']),
            ':b' => trim((string) $item['b']),
            ':c' => trim((string) $item['c']),
            ':d' => trim((string) $item['d']),
            ':correct_answer' => $answer,
        ]);
        $count++;
    }
    return $count;
}

// =========================
// Actions
// =========================
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['topic'])) {
    $topic = trim((string) $_POST['topic']);
    $count = (int) ($_POST['count'] ?? 5);
    if ($topic === '') {
        $error = 'Please enter a topic.';
    } else {
        [$ok, $data] = fetchGeminiMcqs($topic, $count, $geminiApiKey);
        if (!$ok) {
            $error = (string) $data;
        } else {
            $saved = saveQuestions($pdo, $topic, $data);
            if ($saved === 0) {
                $error = 'No valid questions were saved. Please try again.';
            } else {
                $message = "Saved {$saved} questions for topic: {$topic}.";
            }
        }
    }
}

$questions = $pdo->query('SELECT * FROM exam_questions ORDER BY id DESC LIMIT 100')->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AI Question Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6fb; }
        .card { border-radius: 16px; }
        .loader-overlay { display: none; position: fixed; inset: 0; background: rgba(255,255,255,0.8); z-index: 1050; }
        .loader-box { height: 100%; display: flex; align-items: center; justify-content: center; flex-direction: column; }

        @media print {
            .no-print, .no-print * { display: none !important; }
            body { background: #fff; }
            .print-area { margin: 0; }
            .card { border: none; box-shadow: none; }
        }
    </style>
</head>
<body>
<div class="loader-overlay" id="loader">
    <div class="loader-box">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="mt-2 text-secondary">Generating questions...</div>
    </div>
</div>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h1 class="h3 fw-bold">AI Question Manager</h1>
        <button class="btn btn-success" onclick="window.print()">Print / Export PDF</button>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger no-print"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if ($message !== ''): ?>
        <div class="alert alert-success no-print"><?= h($message) ?></div>
    <?php endif; ?>

    <div class="card mb-4 no-print">
        <div class="card-body">
            <h2 class="h5 mb-3">Generate MCQs (Gemini 1.5 Flash)</h2>
            <form method="post" id="ai-form" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Topic</label>
                    <input type="text" name="topic" class="form-control" placeholder="e.g., DBMS Normalization" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">No. of Questions</label>
                    <input type="number" name="count" class="form-control" min="1" max="20" value="5" required>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Generate Questions</button>
                </div>
            </form>
            <div class="form-text mt-2">
                Add your Gemini API key in <code>$geminiApiKey</code> at the top of this file.
            </div>
        </div>
    </div>

    <div class="card print-area">
        <div class="card-body">
            <h2 class="h5 mb-3">Latest Questions</h2>
            <?php if (empty($questions)): ?>
                <div class="text-secondary">No questions found yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>Question</th>
                            <th>A</th>
                            <th>B</th>
                            <th>C</th>
                            <th>D</th>
                            <th>Answer</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($questions as $q): ?>
                            <tr>
                                <td><?= h((string) $q['question']) ?></td>
                                <td><?= h((string) $q['a']) ?></td>
                                <td><?= h((string) $q['b']) ?></td>
                                <td><?= h((string) $q['c']) ?></td>
                                <td><?= h((string) $q['d']) ?></td>
                                <td><?= h((string) $q['correct_answer']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const form = document.getElementById('ai-form');
const loader = document.getElementById('loader');
if (form && loader) {
    form.addEventListener('submit', () => {
        loader.style.display = 'block';
    });
}
</script>
</body>
</html>
