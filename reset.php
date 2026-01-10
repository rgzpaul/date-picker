<?php
$dbFile = __DIR__ . '/db.json';
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
<body class="min-h-screen flex items-center justify-center bg-slate-100">
    <div class="bg-white p-8 rounded-lg shadow text-center">
        <?php if ($message): ?>
            <p class="text-slate-700 mb-4"><?= htmlspecialchars($message) ?></p>
            <a href="index.php" class="text-slate-600 hover:text-slate-800 underline">Back to home</a>
        <?php else: ?>
            <p class="text-slate-700 mb-4">This will delete all saved data.</p>
            <form method="POST" class="space-y-4">
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-md text-sm font-medium">
                    Reset Database
                </button>
            </form>
            <a href="index.php" class="inline-block mt-4 text-slate-600 hover:text-slate-800 underline text-sm">Cancel</a>
        <?php endif; ?>
    </div>
</body>
</html>
