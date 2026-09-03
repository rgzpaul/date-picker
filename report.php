<?php
require __DIR__ . '/event.php';
$event = getEventOrFail();
$dbFile = eventDbFile($event);

// Legge le date salvate per l'evento
$data = file_exists($dbFile) ? (json_decode(file_get_contents($dbFile), true) ?? []) : [];

// Raggruppa per data e raccoglie i nomi; i voti "mi adatto" vanno a parte
$dateGroups = [];
$adaptiveNames = [];
foreach ($data as $entry) {
  if (is_array($entry) && !empty($entry['adaptive'])) {
    $adaptiveNames[] = $entry['name'] ?? 'Anonimo';
    continue;
  }
  // Supporta sia il nuovo formato (array con date e name) che il vecchio (solo stringa)
  if (is_array($entry) && isset($entry['date'])) {
    $date = $entry['date'];
    $name = $entry['name'] ?? 'Anonimo';
  } else {
    $date = $entry;
    $name = 'Anonimo';
  }

  if (!isset($dateGroups[$date])) {
    $dateGroups[$date] = ['count' => 0, 'names' => []];
  }
  $dateGroups[$date]['count']++;
  if (!in_array($name, $dateGroups[$date]['names'])) {
    $dateGroups[$date]['names'][] = $name;
  }
}

// Ordina per conteggio decrescente; a parità di voti viene prima la data
// di calendario più vicina (le chiavi Y-m-d si confrontano come stringhe)
$groupsForSort = $dateGroups;
uksort($dateGroups, function ($a, $b) use ($groupsForSort) {
  return $groupsForSort[$b]['count'] - $groupsForSort[$a]['count'] ?: strcmp($a, $b);
});

// I voti "mi adatto" contano automaticamente sulla data in testa
if (!empty($adaptiveNames) && !empty($dateGroups)) {
  $topDate = array_key_first($dateGroups);
  foreach ($adaptiveNames as $adaptiveName) {
    $dateGroups[$topDate]['count']++;
    if (!in_array($adaptiveName, $dateGroups[$topDate]['names'])) {
      $dateGroups[$topDate]['names'][] = $adaptiveName;
    }
  }
}

// Crea il counter per compatibilita
$counter = [];
foreach ($dateGroups as $date => $info) {
  $counter[$date] = $info['count'];
}

// Funzione per formattare la data come "LUN 21/08"
function formatDateItalian($dateStr)
{
  $giorniSettimana = [
    'Mon' => 'LUN',
    'Tue' => 'MAR',
    'Wed' => 'MER',
    'Thu' => 'GIO',
    'Fri' => 'VEN',
    'Sat' => 'SAB',
    'Sun' => 'DOM',
  ];

  // Assicurati che il formato della data sia correttamente interpretato
  // Il formato YYYY-MM-DD che viene salvato da index.php
  $timestamp = strtotime($dateStr);
  if ($timestamp === false) {
    // In caso di problemi con il formato, ritorna la stringa originale
    return $dateStr;
  }

  $giorno = $giorniSettimana[date('D', $timestamp)] ?? date('D', $timestamp);
  $dataFormattata = date('d/m', $timestamp);

  return "$giorno $dataFormattata";
}

// Renderizza una riga della classifica
function renderDateItem($date, $info, $totalVotes, $adaptiveNames = [])
{
  $count = $info['count'];
  $names = $info['names'];
  $percentage = ($count / $totalVotes) * 100;
  ?>
  <li class="date-item p-3 bg-slate-50 rounded-md border border-slate-200">
    <div class="flex justify-between items-center mb-2">
      <div class="flex items-center">
        <span class="text-sm font-medium text-slate-700 mr-3">
          <?= htmlspecialchars(formatDateItalian($date)) ?>
        </span>
        <span class="text-xs text-slate-500">
          <?= $count ?> vot<?= ($count == 1) ? 'o' : 'i' ?>
        </span>
      </div>
      <span class="text-xs text-slate-400"><?= number_format($percentage, 0) ?>%</span>
    </div>
    <div class="w-full bg-slate-200 rounded-full h-1.5 mb-2">
      <div class="bg-slate-600 h-1.5 rounded-full" style="width: <?= $percentage ?>%"></div>
    </div>
    <div class="flex flex-wrap gap-1.5">
      <?php foreach ($names as $name): ?>
        <?php $isAdaptiveName = in_array($name, $adaptiveNames); ?>
        <span class="inline-flex items-center text-xs <?= $isAdaptiveName ? 'bg-slate-100 border border-dashed border-slate-400' : 'bg-slate-200' ?> text-slate-600 px-2 py-0.5 rounded"<?= $isAdaptiveName ? ' title="Si adatta alla data più votata"' : '' ?>>
          <i data-lucide="<?= $isAdaptiveName ? 'shuffle' : 'user' ?>" class="w-3 h-3 mr-1"></i><?= htmlspecialchars($name) ?>
        </span>
      <?php endforeach; ?>
    </div>
  </li>
  <?php
}

// Calcola la data più popolare
$mostPopularDate = !empty($counter) ? key($counter) : null;
$totalVotes = array_sum($counter);
$totalDates = count($counter);

// Classifica: prime 3 date in evidenza, le altre in una sezione richiudibile
$topDates = array_slice($dateGroups, 0, 3, true);
$otherDates = array_slice($dateGroups, 3, null, true);

// Numero di votanti (nomi distinti, case-insensitive)
$voterNames = [];
foreach ($data as $entry) {
  $name = is_array($entry) ? ($entry['name'] ?? 'Anonimo') : 'Anonimo';
  $voterNames[mb_strtolower($name)] = true;
}
$totalVoters = count($voterNames);

?>
<!DOCTYPE html>
<html lang="it">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Report</title>
  <link href="http://minisoft.it/cdn/icons/collection/idea.png" rel="shortcut icon" type="image/x-icon" />
  <link href="https://prgz.it/datePicker/webclip.png" rel="apple-touch-icon" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    .card {
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
      border: 1px solid #e2e8f0;
    }

    .btn {
      transition: background-color 0.15s ease;
    }

    .chevron {
      transition: transform 0.15s ease;
    }

    details[open] .chevron {
      transform: rotate(180deg);
    }

    @media (max-width: 768px) {
      .card {
        padding: 1.5rem !important;
      }

      .stats-card {
        padding: 1rem !important;
      }

      .date-item {
        padding: 0.75rem !important;
      }
    }
  </style>
</head>

<body class="min-h-[100dvh] py-12 px-4 bg-slate-50">
  <div class="max-w-2xl mx-auto">
    <div class="card bg-white p-8 rounded-lg">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <h1 class="text-xl font-semibold text-slate-800 flex items-center">
          <i data-lucide="bar-chart-2" class="w-5 h-5 mr-2 text-slate-500"></i>Report
          <span class="ml-3 inline-flex items-center bg-slate-100 text-slate-600 text-xs font-medium px-2.5 py-1 rounded border border-slate-200">
            <i data-lucide="tag" class="w-3 h-3 mr-1.5"></i><?= htmlspecialchars($event) ?>
          </span>
        </h1>

        <a href="index.php?event=<?= urlencode($event) ?>" class="btn bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-2.5 rounded-md text-sm font-medium inline-flex items-center justify-center w-full sm:w-auto border border-slate-200">
          <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i> Indietro
        </a>
      </div>

      <?php if (!empty($counter)): ?>
        <!-- Stats Cards -->
        <div class="grid grid-cols-2 gap-3 mb-6">
          <div class="stats-card bg-slate-50 border border-slate-200 rounded-md py-3 px-4">
            <div class="text-xs text-slate-500 mb-1">Votanti</div>
            <div class="text-lg font-semibold text-slate-800"><?= $totalVoters ?></div>
          </div>

          <div class="stats-card bg-slate-50 border border-slate-200 rounded-md py-3 px-4">
            <div class="text-xs text-slate-500 mb-1">Date proposte</div>
            <div class="text-lg font-semibold text-slate-800"><?= $totalDates ?></div>
          </div>

          <?php if ($mostPopularDate): ?>
            <div class="stats-card col-span-2 bg-slate-50 border border-slate-200 rounded-md py-3 px-4">
              <div class="text-xs text-slate-500 mb-1">Data scelta</div>
              <div class="text-lg font-semibold text-slate-800"><?= formatDateItalian($mostPopularDate) ?></div>
            </div>
          <?php endif; ?>
        </div>

        <h2 class="text-sm font-medium text-slate-500 mb-3 uppercase tracking-wide">
          Classifica
        </h2>

        <ul class="space-y-2">
          <?php foreach ($topDates as $date => $info) renderDateItem($date, $info, $totalVotes, $adaptiveNames); ?>
        </ul>

        <?php if (!empty($otherDates)): ?>
          <details class="mt-3">
            <summary class="btn cursor-pointer select-none list-none [&::-webkit-details-marker]:hidden p-3 bg-slate-50 hover:bg-slate-100 rounded-md border border-slate-200 text-sm font-medium text-slate-600 flex items-center">
              <i data-lucide="chevron-down" class="chevron w-4 h-4 mr-2"></i>
              Altre date (<?= count($otherDates) ?>)
            </summary>
            <ul class="space-y-2 mt-2">
              <?php foreach ($otherDates as $date => $info) renderDateItem($date, $info, $totalVotes, $adaptiveNames); ?>
            </ul>
          </details>
        <?php endif; ?>
      <?php else: ?>
        <div class="bg-slate-50 border border-slate-200 p-6 rounded-md text-center">
          <p class="text-slate-500 text-sm mb-4">Nessun dato disponibile. Non ci sono ancora date selezionate.</p>
          <?php if (!empty($adaptiveNames)): ?>
            <p class="text-slate-400 text-xs mb-4 flex items-center justify-center">
              <i data-lucide="shuffle" class="w-3 h-3 mr-1.5"></i>
              Si adattano alla data più votata: <?= htmlspecialchars(implode(', ', $adaptiveNames)) ?>
            </p>
          <?php endif; ?>
          <a href="index.php?event=<?= urlencode($event) ?>" class="btn inline-block bg-slate-800 hover:bg-slate-700 text-white px-5 py-2.5 rounded-md text-sm font-medium">
            Seleziona date
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <script>lucide.createIcons();</script>
</body>

</html>