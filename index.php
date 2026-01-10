<?php
$alreadySubmitted = isset($_COOKIE['already_submitted']);
$errorMessage = '';

// Configuration settings
$maxDates = 6; // Maximum number of dates selectable - change this value to adjust the limit

// Salvataggio dei dati se il form è inviato
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadySubmitted) {
  $dates = $_POST['dates'] ?? [];
  $nome = trim($_POST['nome'] ?? '');

  if (!empty($dates) && !empty($nome)) {
    $datesArray = explode(',', $dates);
    $datesArray = array_filter($datesArray);

    // Limit to max dates (ensure backend validation too)
    if (count($datesArray) > $maxDates) {
      $datesArray = array_slice($datesArray, -$maxDates);
    }

    $data = json_decode(file_get_contents('db.json'), true) ?? [];

    // Controlla se il nome esiste già
    $existingNames = array_unique(array_column($data, 'name'));
    $nomeNormalized = mb_strtolower($nome);
    $nameExists = false;
    foreach ($existingNames as $existingName) {
      if (mb_strtolower($existingName) === $nomeNormalized) {
        $nameExists = true;
        break;
      }
    }

    if ($nameExists) {
      $errorMessage = 'Questo nome è già stato utilizzato. Inserisci un nome diverso.';
    } else {
      foreach ($datesArray as $date) {
        $data[] = ['date' => trim($date), 'name' => $nome];
      }
      file_put_contents('db.json', json_encode($data));

      // Imposta il cookie per 1 settimana
      setcookie('already_submitted', 'true', time() + (7 * 24 * 60 * 60));

      header('Location: report.php');
      exit;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="it">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Adesso basta</title>
  <meta property="og:title" content="Adesso basta">
  <meta name="description" content="Mi sto incazzando">
  <meta property="og:description" content="Mi sto incazzando">
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
      background: #f8fafc;
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

    @media (max-width: 640px) {
      .card {
        padding: 1.5rem !important;
      }

      .flatpickr-calendar {
        width: 90% !important;
        max-width: 310px !important;
      }
    }
  </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4">
  <div class="card bg-white p-8 rounded-lg w-full max-w-md">
    <h1 class="text-xl font-semibold mb-6 text-slate-800 flex items-center justify-center">
      <?php if ($alreadySubmitted): ?>
        <i data-lucide="check-circle" class="w-5 h-5 mr-2 text-slate-500"></i>Hai già votato
      <?php else: ?>
        <i data-lucide="calendar" class="w-5 h-5 mr-2 text-slate-500"></i>Seleziona date
      <?php endif; ?>
    </h1>

    <?php if (!$alreadySubmitted): ?>
      <?php if ($errorMessage): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md mb-4 text-sm flex items-center">
          <i data-lucide="alert-circle" class="w-4 h-4 mr-2 flex-shrink-0"></i>
          <?= htmlspecialchars($errorMessage) ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="space-y-4">
        <div>
          <label for="nome" class="block text-sm font-medium text-slate-700 mb-1">Il tuo nome</label>
          <input type="text" id="nome" name="nome" required placeholder="Inserisci il tuo nome" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-md text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-300<?= $errorMessage ? ' border-red-300' : '' ?>">
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
        <p class="text-slate-600 text-sm">Hai già inviato la tua selezione. Puoi visualizzare il report delle date più selezionate.</p>
      </div>

      <a href="report.php" class="btn block text-center w-full bg-slate-800 hover:bg-slate-700 text-white px-5 py-2.5 rounded-md text-sm font-medium">
        <i data-lucide="bar-chart-2" class="w-4 h-4 mr-2 inline"></i> Vai al report
      </a>
    <?php endif; ?>

    <div class="mt-6 text-center text-slate-400 text-xs">
      <p>Seleziona le date che preferisci prima che mi incazzo sul serio.</p>
    </div>
  </div>

  <?php if (!$alreadySubmitted): ?>
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
        onChange: function(selectedDates) {
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
        },
        onMonthChange: function(selectedDates, dateStr, instance) {
          // Use a different approach to display the month name
          updateMonthDisplay(instance);
        },
        onReady: function(selectedDates, dateStr, instance) {
          // Initial month display update
          updateMonthDisplay(instance);
        }
      });

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