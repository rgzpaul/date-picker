<?php
require __DIR__ . '/event.php';
$event = getEventOrFail();
$dbFile = eventDbFile($event);
$cookieName = eventCookieName($event);

$submittedName = eventSubmittedName($event);
$alreadySubmitted = $submittedName !== null;
$isChanging = $alreadySubmitted && isset($_GET['modifica']);
$showForm = !$alreadySubmitted || $isChanging;
// In modifica l'identità è fissata dal cookie: il nome non si cambia
// (resta libero solo per i cookie di vecchio tipo che non memorizzavano il nome)
$nomeLocked = $isChanging && $submittedName !== '';

// Configuration settings
$maxDates = 6; // Maximum number of dates selectable - change this value to adjust the limit

// Salvataggio dei dati se il form è inviato.
// Un nuovo invio con un nome già presente (confronto case-insensitive)
// sovrascrive il voto precedente: è così che si cambia voto, anche da
// un device senza cookie. In modifica, l'invio senza alcuna data
// ritira il voto: le proprie voci vengono eliminate e il cookie rimosso.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $dates = $_POST['dates'] ?? '';
  $dates = is_string($dates) ? $dates : '';
  $nome = trim($_POST['nome'] ?? '');
  $isWithdrawal = $isChanging && $dates === '' && $nome !== '';

  if (($dates !== '' || $isWithdrawal) && $nome !== '') {
    $datesArray = $isWithdrawal ? [] : array_filter(explode(',', $dates));

    // Limit to max dates (ensure backend validation too)
    if (count($datesArray) > $maxDates) {
      $datesArray = array_slice($datesArray, -$maxDates);
    }

    $data = file_exists($dbFile) ? (json_decode(file_get_contents($dbFile), true) ?? []) : [];

    // Rimuove l'eventuale voto esistente con lo stesso nome
    $nomeNormalized = mb_strtolower($nome);
    $data = array_values(array_filter($data, function ($entry) use ($nomeNormalized) {
      $entryName = is_array($entry) ? ($entry['name'] ?? '') : '';
      return mb_strtolower($entryName) !== $nomeNormalized;
    }));

    foreach ($datesArray as $date) {
      $data[] = ['date' => trim($date), 'name' => $nome];
    }
    file_put_contents($dbFile, json_encode($data));

    if ($isWithdrawal) {
      // Torna allo stato "non hai votato"
      eventForgetSubmission($event);
    } else {
      // Il cookie memorizza il nome usato, per ritrovarlo al cambio voto (21 giorni)
      setcookie($cookieName, $nome, strtotime('+21 days'));
    }

    header('Location: report.php?event=' . urlencode($event));
    exit;
  }
}

// In caso di cambio voto, date già votate da precaricare nel calendario
// (solo odierne o future: le passate non sono più selezionabili)
$existingDates = [];
if ($isChanging && $submittedName !== '') {
  $data = file_exists($dbFile) ? (json_decode(file_get_contents($dbFile), true) ?? []) : [];
  $target = mb_strtolower($submittedName);
  foreach ($data as $entry) {
    if (is_array($entry) && isset($entry['date']) && mb_strtolower($entry['name'] ?? '') === $target) {
      $timestamp = strtotime($entry['date']);
      if ($timestamp !== false && $timestamp >= strtotime('today')) {
        $existingDates[] = $entry['date'];
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="it">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Selezione date</title>
  <meta property="og:title" content="Selezione date">
  <meta name="description" content="Indica le date in cui sei disponibile e invia la tua selezione.">
  <meta property="og:description" content="Indica le date in cui sei disponibile e invia la tua selezione.">
  <link href="http://minisoft.it/cdn/icons/collection/idea.png" rel="shortcut icon" type="image/x-icon" />
  <link href="https://prgz.it/datePicker/webclip.png" rel="apple-touch-icon" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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

    .flatpickr-calendar {
      width: auto !important;
      max-width: 90% !important;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
      border-radius: 8px !important;
      border: 1px solid #e2e8f0 !important;
      overflow: hidden !important;
      top: 50% !important;
      left: 50% !important;
      transform: translate(-50%, -50%) !important;
      z-index: 9999;
      font-family: 'Inter', sans-serif !important;
    }

    .flatpickr-innerContainer {
      overflow-x: auto;
    }

    .flatpickr-day.selected {
      background: #334155 !important;
      border-color: #334155 !important;
    }

    .flatpickr-day:hover {
      background: #f1f5f9 !important;
      border-color: #f1f5f9 !important;
    }

    .flatpickr-day.selected:hover {
      background: #1e293b !important;
      border-color: #1e293b !important;
    }

    .flatpickr-current-month .flatpickr-monthDropdown-months,
    .flatpickr-current-month .numInputWrapper {
      display: none !important;
    }

    .flatpickr-current-month {
      display: flex !important;
      justify-content: center !important;
      padding-top: 5px !important;
    }

    .custom-month-display {
      font-size: 0.875rem;
      font-weight: 500;
      padding: 8px 0;
      text-align: center;
      color: #334155;
    }
  </style>
</head>

<body class="min-h-[100dvh] flex items-center justify-center p-4 bg-slate-50">
  <div class="card bg-white p-8 rounded-lg w-full max-w-md">
    <h1 class="text-xl font-semibold mb-2 text-slate-800 flex items-center justify-center">
      <?php if ($isChanging): ?>
        <i data-lucide="pencil" class="w-5 h-5 mr-2 text-slate-500"></i>Modifica il tuo voto
      <?php elseif ($alreadySubmitted): ?>
        <i data-lucide="check-circle" class="w-5 h-5 mr-2 text-slate-500"></i>Hai già votato
      <?php else: ?>
        <i data-lucide="calendar" class="w-5 h-5 mr-2 text-slate-500"></i>Seleziona date
      <?php endif; ?>
    </h1>

    <div class="flex justify-center mb-6">
      <span class="inline-flex items-center bg-slate-100 text-slate-600 text-xs font-medium px-2.5 py-1 rounded border border-slate-200">
        <i data-lucide="tag" class="w-3 h-3 mr-1.5"></i><?= htmlspecialchars($event) ?>
      </span>
    </div>

    <?php if ($showForm): ?>
      <form method="POST" class="space-y-4">
        <div>
          <label for="nome" class="block text-sm font-medium text-slate-700 mb-1">Il tuo nome</label>
          <input type="text" id="nome" name="nome" required placeholder="Inserisci il tuo nome" value="<?= htmlspecialchars($nomeLocked ? $submittedName : ($_POST['nome'] ?? $submittedName ?? '')) ?>"<?= $nomeLocked ? ' readonly' : '' ?> class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-md text-sm placeholder-slate-400 focus:outline-none<?= $nomeLocked ? ' text-slate-500 cursor-not-allowed' : ' text-slate-700 focus:ring-2 focus:ring-slate-300 focus:border-slate-300' ?>">
        </div>

        <div class="flex items-center justify-end mb-1">
          <span class="text-slate-400 text-xs font-medium">
            Max <?= $maxDates ?> date
          </span>
        </div>

        <div id="selected-dates" class="flex flex-wrap gap-2 min-h-[56px] p-3 bg-slate-50 rounded-md border border-slate-200"></div>

        <input type="hidden" id="dates" name="dates" required>

        <button type="button" id="open-calendar" class="btn w-full bg-slate-800 hover:bg-slate-700 text-white px-5 py-2.5 rounded-md text-sm font-medium flex items-center justify-center">
          <i data-lucide="calendar-plus" class="w-4 h-4 mr-2"></i> Scegli date
        </button>

        <button type="submit" class="btn w-full bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-2.5 rounded-md text-sm font-medium flex items-center justify-center border border-slate-200">
          <i data-lucide="save" class="w-4 h-4 mr-2"></i> Invia selezione
        </button>
      </form>
    <?php else: ?>
      <div class="bg-slate-50 border border-slate-200 p-4 mb-4 rounded-md">
        <p class="text-slate-600 text-sm"><?= $submittedName !== '' ? htmlspecialchars($submittedName) . ', hai' : 'Hai' ?> già inviato la tua selezione. Puoi modificarla oppure visualizzare il report delle date più selezionate.</p>
      </div>

      <div class="space-y-4">
        <a href="index.php?event=<?= urlencode($event) ?>&modifica=1" class="btn w-full bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-2.5 rounded-md text-sm font-medium flex items-center justify-center border border-slate-200">
          <i data-lucide="pencil" class="w-4 h-4 mr-2"></i> Modifica il tuo voto
        </a>

        <a href="report.php?event=<?= urlencode($event) ?>" class="btn block text-center w-full bg-slate-800 hover:bg-slate-700 text-white px-5 py-2.5 rounded-md text-sm font-medium">
          <i data-lucide="bar-chart-2" class="w-4 h-4 mr-2 inline"></i> Vai al report
        </a>
      </div>
    <?php endif; ?>

    <?php if (!$alreadySubmitted): ?>
      <div class="mt-6 text-center text-slate-400 text-xs">
        <p>Seleziona le date che preferisci prima che mi incazzo sul serio.</p>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($showForm): ?>
    <script>
      function formatDateItalian(dateObj) {
        const giorniSettimana = ['DOM', 'LUN', 'MAR', 'MER', 'GIO', 'VEN', 'SAB'];
        const giorno = giorniSettimana[dateObj.getDay()];
        const giornoNumero = String(dateObj.getDate()).padStart(2, '0');
        const meseNumero = String(dateObj.getMonth() + 1).padStart(2, '0');
        return `${giorno} ${giornoNumero}/${meseNumero}`;
      }

      const fakeInput = document.createElement('input');
      fakeInput.style.position = 'absolute';
      fakeInput.style.left = '-9999px';
      document.body.appendChild(fakeInput);

      // Month names in Italian
      const mesiItaliani = [
        'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
        'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'
      ];

      // Get the max dates value directly from PHP
      const maxDatesAllowed = <?= $maxDates ?>;

      const calendar = flatpickr(fakeInput, {
        mode: 'multiple',
        dateFormat: 'Y-m-d',
        defaultDate: <?= json_encode($existingDates) ?>,
        minDate: 'today',
        showMonths: 1,
        animate: true,
        maxDate: new Date().fp_incr(60), // Limit to 60 days in the future
        locale: {
          firstDayOfWeek: 1, // Start with Monday
          weekdays: {
            shorthand: ['DOM', 'LUN', 'MAR', 'MER', 'GIO', 'VEN', 'SAB'],
            longhand: ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato']
          },
          months: {
            shorthand: ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'],
            longhand: mesiItaliani
          }
        },
        onChange: syncSelection,
        onMonthChange: function(selectedDates, dateStr, instance) {
          updateMonthDisplay(instance);
        },
        onReady: function(selectedDates, dateStr, instance) {
          updateMonthDisplay(instance);
        }
      });

      // Aggiorna i tag delle date scelte e il campo nascosto del form
      function syncSelection(selectedDates) {
        const selectedDatesContainer = document.getElementById('selected-dates');
          selectedDatesContainer.innerHTML = '';

          const datesInput = document.getElementById('dates');
          const formattedDates = [];

          // Limit to max dates
          if (selectedDates.length > maxDatesAllowed) {
            // Remove the oldest dates if over the limit
            selectedDates = selectedDates.slice(selectedDates.length - maxDatesAllowed);
            // Update the flatpickr instance with the reduced selection
            calendar.setDate(selectedDates);
          }

          selectedDates.forEach(date => {
            const tag = document.createElement('span');
            tag.className = 'date-tag bg-slate-100 text-slate-700 text-xs font-medium px-2.5 py-1 rounded flex items-center';

            const text = document.createTextNode(formatDateItalian(date));
            tag.appendChild(text);

            selectedDatesContainer.appendChild(tag);

            // Usa una funzione che gestisca correttamente il timezone
            // invece di toISOString che converte in UTC e può causare spostamenti di data
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const localISODate = `${year}-${month}-${day}`;

            formattedDates.push(localISODate);
          });

          if (selectedDates.length === 0) {
            selectedDatesContainer.innerHTML = '<p class="text-slate-400 text-center w-full my-2 text-xs">Nessuna data selezionata</p>';
          }

          datesInput.value = formattedDates.join(',');
      }

      // Mostra subito le date precaricate (cambio voto) o il placeholder
      syncSelection(calendar.selectedDates);

      <?php if ($isChanging): ?>
      // In modifica, inviare senza alcuna data ritira il voto: chiedi conferma
      document.querySelector('form').addEventListener('submit', function(e) {
        if (document.getElementById('dates').value === '' &&
            !confirm('Nessuna data selezionata: confermi di voler ritirare il tuo voto?')) {
          e.preventDefault();
        }
      });
      <?php endif; ?>

      // Helper function to update the month display
      function updateMonthDisplay(instance) {
        try {
          const currentMonth = instance.currentMonth;
          const currentYear = instance.currentYear;
          const monthName = mesiItaliani[currentMonth];

          // Find the header element and manipulate it more safely
          const monthElement = document.querySelector('.flatpickr-month');

          // Check if the element exists before trying to modify it
          if (monthElement) {
            // Clear existing month display if any
            const existingDisplay = monthElement.querySelector('.custom-month-display');
            if (existingDisplay) {
              existingDisplay.remove();
            }

            // Create a new element for the month display
            const monthDisplay = document.createElement('div');
            monthDisplay.className = 'custom-month-display';
            monthDisplay.textContent = `${monthName} ${currentYear}`;

            // Add it to the month element
            monthElement.appendChild(monthDisplay);
          }
        } catch (error) {
          console.error('Error updating month display:', error);
        }
      }

      document.getElementById('open-calendar').addEventListener('click', function() {
        calendar.open();
      });
    </script>
  <?php endif; ?>
  <script>lucide.createIcons();</script>
</body>

</html>