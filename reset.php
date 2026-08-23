<?php
require __DIR__ . '/event.php';
$event = getEventOrFail();
$dbFile = eventDbFile($event);
$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (file_exists($dbFile)) {
        unlink($dbFile);
        $message = 'Database reset successfully.';
    } else {
        $message = 'Database file does not exist.';
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-[100dvh] flex items-center justify-center bg-slate-50">
    <div class="p-8 text-center">
        <?php if ($message): ?>
            <p class="text-slate-700 mb-4"><?= htmlspecialchars($message) ?></p>
            <a href="index.php?event=<?= urlencode($event) ?>" class="text-slate-600 hover:text-slate-800 underline">Back to home</a>
        <?php elseif (file_exists($dbFile)): ?>
            <p class="text-slate-700 mb-4">This will delete all saved data for event "<?= htmlspecialchars($event) ?>".</p>
            <form method="POST">
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-md text-sm font-medium">
                    Reset database
                </button>
            </form>
        <?php else: ?>
            <p class="text-slate-700 mb-4">No data to reset for event "<?= htmlspecialchars($event) ?>".</p>
            <a href="index.php?event=<?= urlencode($event) ?>" class="text-slate-600 hover:text-slate-800 underline">Back to home</a>
        <?php endif; ?>
    </div>
</body>
</html>
