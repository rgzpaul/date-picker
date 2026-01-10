<?php
$dbFile = __DIR__ . '/db.json';

if (file_exists($dbFile)) {
    unlink($dbFile);
    $message = 'Database reset successfully.';
} else {
    $message = 'Database file does not exist.';
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
        <p class="text-slate-700 mb-4"><?= htmlspecialchars($message) ?></p>
        <a href="index.php" class="text-slate-600 hover:text-slate-800 underline">Back to home</a>
    </div>
</body>
</html>
