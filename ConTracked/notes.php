<?php
$db = new PDO('sqlite:workflow.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$jobId = $_GET['job_id'] ?? null;
if (!$jobId) {
    echo "Invalid job ID.";
    exit;
}

// Fetch job details
$job = $db->query("SELECT * FROM jobs WHERE id = $jobId")->fetch(PDO::FETCH_ASSOC);

// Handle note submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['content'])) {
    $stmt = $db->prepare("INSERT INTO notes (job_id, content, created_at) VALUES (?, ?, ?)");
    $stmt->execute([$jobId, $_POST['content'], date('Y-m-d H:i:s')]);
}

// Fetch existing notes
$notes = $db->query("SELECT * FROM notes WHERE job_id = $jobId ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Notes for Job <?= htmlspecialchars($job['code']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 30px; background: #f9f9f9; }
        .note-box { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 0 5px rgba(0,0,0,0.1); }
        textarea, button { width: 100%; padding: 10px; margin-top: 10px; }
        .note { margin-top: 10px; padding: 10px; background: #eee; border-radius: 5px; }
    </style>
</head>
<body>

<h2>Notes for <?= htmlspecialchars($job['title']) ?> - <?= htmlspecialchars($job['code']) ?></h2>
<p><a href="index.php">← Back to Tracker</a></p>

<form method="POST" class="note-box">
    <h3>Add a Note</h3>
    <textarea name="content" rows="4" placeholder="Write your note here..." required></textarea>
    <button type="submit">Add Note</button>
</form>

<h3>Previous Notes</h3>
<?php if ($notes): ?>
    <?php foreach ($notes as $note): ?>
        <div class="note">
    <strong><?= $note['created_at'] ?> (<?= htmlspecialchars($note['category'] ?? 'No Tag') ?>)</strong><br>
    <?= nl2br(htmlspecialchars($note['content'])) ?><br>

    <form method="POST" style="display:inline;">
        <input type="hidden" name="delete_note_id" value="<?= $note['id'] ?>">
        <button type="submit">❌ Delete</button>
    </form>
    <button onclick="editNote(<?= $note['id'] ?>)">✏️ Edit</button>

    <form method="POST" style="margin-top:10px;">
        <input type="hidden" name="comment_note_id" value="<?= $note['id'] ?>">
        <textarea name="comment_content" rows="2" placeholder="Add a comment..." required></textarea>
        <button type="submit">Comment</button>
    </form>

    <?php
    $comments = $db->query("SELECT * FROM note_comments WHERE note_id = " . $note['id'])->fetchAll(PDO::FETCH_ASSOC);
    foreach ($comments as $comment) {
        echo '<div style="margin-left:20px; background:#fff; padding:5px; margin-top:5px;">🗨️ ' . nl2br(htmlspecialchars($comment['content'])) . ' <small>(' . $comment['created_at'] . ')</small></div>';
    }
    ?>
</div>

    <?php endforeach; ?>
<?php else: ?>
    <p>No notes yet.</p>
<?php endif; ?>

</body>
</html>
