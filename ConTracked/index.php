<?php
// === SETTINGS ===
$conversionRate = 35; // 1 point = £35

// === DATABASE SETUP ===
$db = new PDO('sqlite:workflow.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS notes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    job_id INTEGER,
    content TEXT,
    created_at TEXT,
    FOREIGN KEY (job_id) REFERENCES jobs(id)
)");

// Add category to notes
// Add 'category' column to notes table only if it doesn't exist
$columns = $db->query("PRAGMA table_info(notes)")->fetchAll(PDO::FETCH_ASSOC);
$columnNames = array_column($columns, 'name');

if (!in_array('category', $columnNames)) {
    $db->exec("ALTER TABLE notes ADD COLUMN category TEXT");
}


// Create comments table
$db->exec("CREATE TABLE IF NOT EXISTS note_comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    note_id INTEGER,
    content TEXT,
    created_at TEXT,
    FOREIGN KEY (note_id) REFERENCES notes(id)
)");



// === HANDLE FORM SUBMISSION ===
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $stmt = $db->prepare("INSERT INTO jobs (title, code, points, location, job_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST["title"],
        $_POST["code"],
        $_POST["points"],
        $_POST["location"],
        $_POST["job_date"]
    ]);
}

// === FETCH JOBS ===
$today = date("Y-m-d");
$pastJobs = $db->query("SELECT * FROM jobs WHERE job_date < '$today' ORDER BY job_date DESC")->fetchAll();
$upcomingJobs = $db->query("SELECT * FROM jobs WHERE job_date >= '$today' ORDER BY job_date ASC")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Con-Tracked</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 30px; background: #f4f4f4; }
        h1 { color: #333; }
        form { background: white; padding: 20px; margin-bottom: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input, button { margin-top: 10px; padding: 10px; width: 100%; }
        .job-list { background: white; padding: 20px; margin-bottom: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .job-item { padding: 10px; border-bottom: 1px solid #eee; }
        .job-item:last-child { border-bottom: none; }
        .title { font-weight: bold; }
    </style>
</head>
<body>

<h1>Workflow Tracker</h1>

<!-- === JOB FORM === -->
<form method="POST">
    <h2>Add New Job</h2>
    <select name="title" required>
    <option value="">Select Job Title</option>
    <option value="BT">BT</option>
    <option value="CityFibre">CityFibre</option>
</select>
    <input type="text" name="code" placeholder="Job Code" required>
    <input type="number" name="points" placeholder="Job Points" required>
    <input type="text" name="location" placeholder="Job Location" required>
    <input type="date" name="job_date" required>
    <button type="submit">Add Job</button>
</form>

<!-- === UPCOMING JOBS === -->
<div class="job-list">
    <h2>Upcoming Jobs</h2>
    <?php foreach ($upcomingJobs as $job): ?>
        <?php $value = $job['points'] * $conversionRate; ?>
        <?php
$hasNotes = $db->query("SELECT COUNT(*) FROM notes WHERE job_id = " . $job['id'])->fetchColumn();
?><div class="title">
<?= htmlspecialchars($job['title']) ?> (<?= $job['code'] ?>)
<?php if ($hasNotes): ?>
    <span title="This job has notes">🟢</span>
<?php endif; ?>
<a href="notes.php?job_id=<?= $job['id'] ?>">View/Add Notes</a>
</div>

        <div class="job-item">
            <div class="title"><?= htmlspecialchars($job['title']) ?> (<?= $job['code'] ?>)</div>
            <div><?= $job['points'] ?> points — £<?= number_format($value, 2) ?> @ <?= htmlspecialchars($job['location']) ?></div>
            <div>Date: <?= $job['job_date'] ?></div>
        </div>
    <?php endforeach; ?>
</div>

<!-- === PAST JOBS === -->
<div class="job-list">
    <h2>Past Jobs</h2>
    <?php foreach ($pastJobs as $job): ?>
        <?php $value = $job['points'] * $conversionRate; ?>
        <div class="job-item">
            <div class="title"><?= htmlspecialchars($job['title']) ?> (<?= $job['code'] ?>)</div>
            <div><?= $job['points'] ?> points — £<?= number_format($value, 2) ?> @ <?= htmlspecialchars($job['location']) ?></div>
            <div>Date: <?= $job['job_date'] ?></div>
<a href="notes.php?job_id=<?= $job['id'] ?>">View/Add Notes</a>
        </div>
    <?php endforeach; ?>
</div>

</body>
</html>