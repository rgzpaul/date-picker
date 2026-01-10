<?php
$alreadySubmitted = isset($_COOKIE['already_submitted']);

// Configuration settings
$maxDates = 6; // Maximum number of dates selectable - change this value to adjust the limit

// Salvataggio dei dati se il form è inviato
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadySubmitted) {
  $dates = $_POST['dates'] ?? [];

  if (!empty($dates)) {
    $datesArray = explode(',', $dates);
    $datesArray = array_filter($datesArray);

    // Limit to max dates (ensure backend validation too)
    if (count($datesArray) > $maxDates) {
      $datesArray = array_slice($datesArray, -$maxDates);
    }

    $data = json_decode(file_get_contents('db.json'), true) ?? [];
    foreach ($datesArray as $date) {
      $data[] = trim($date);
    }
    file_put_contents('db.json', json_encode($data));

    // Imposta il cookie per 1 settimana
    setcookie('already_submitted', 'true', time() + (7 * 24 * 60 * 60));

    header('Location: report.php');
    exit;
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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

  <style>
    body {
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .card {
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .btn {
      transition: all 0.3s ease;
    }

    .btn:active {
      transform: translateY(1px);
    }

    .date-tag {
      animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .flatpickr-calendar {
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2) !important;
      border-radius: 12px !important;
      overflow: hidden !important;
      top: 50% !important;
      left: 50% !important;
      transform: translate(-50%, -50%) !important;
      z-index: 9999;
    }

    .flatpickr-day.selected {
      background: #4F46E5 !important;
      border-color: #4F46E5 !important;
    }

    /* Hide month dropdown and year input */
    .flatpickr-current-month .flatpickr-monthDropdown-months,
    .flatpickr-current-month .numInputWrapper {
      display: none !important;
    }

    /* Center the month text */
    .flatpickr-current-month {
      display: flex !important;
      justify-content: center !important;
      padding-top: 5px !important;
    }

    /* Add custom month display */
    .custom-month-display {
      font-size: 1.2rem;
      font-weight: 600;
      padding: 10px 0;
      text-align: center;
    }

    /* Mobile Optimizations */
    @media (max-width: 640px) {
      .card {
        padding: 1.25rem !important;
      }

      h1 {
        font-size: 1.5rem !important;
      }

      button,
      .btn {
        padding-top: 0.625rem !important;
        padding-bottom: 0.625rem !important;
      }

      .flatpickr-calendar {
        width: 90% !important;
        max-width: 300px !important;
      }
    }
  </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4">
  <div class="card bg-white p-8 rounded-2xl w-full max-w-md">
    <h1 class="text-3xl font-bold mb-6 text-center text-indigo-700">
      <?php if ($alreadySubmitted): ?>
        <span class="text-pink-600"><i class="fas fa-check-circle mr-2"></i>Hai già votato</span>
      <?php else: ?>
        <i class="far fa-calendar-alt mr-2"></i>Seleziona date
      <?php endif; ?>
    </h1>

    <?php if (!$alreadySubmitted): ?>
      <form method="POST" class="space-y-6">
        <div class="flex items-center justify-end mb-2">
          <span class="bg-indigo-100 text-indigo-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
            <i class="fas fa-info-circle mr-1"></i>Massimo <?= $maxDates ?> date
          </span>
        </div>

        <div id="selected-dates" class="flex flex-wrap gap-2 min-h-[60px] p-3 bg-gray-50 rounded-lg border border-gray-200"></div>

        <input type="hidden" id="dates" name="dates" required>

        <button type="button" id="open-calendar" class="btn w-full bg-indigo-600 text-white px-6 py-3 rounded-lg flex items-center justify-center">
          <i class="far fa-calendar-plus mr-2"></i> Scegli date
        </button>

        <button type="submit" class="btn w-full bg-emerald-600 text-white px-6 py-3 rounded-lg flex items-center justify-center">
          <i class="fas fa-save mr-2"></i> Invia selezione
        </button>
      </form>
    <?php else: ?>
      <div class="bg-pink-50 border-l-4 border-pink-500 p-4 mb-6 rounded-md">
        <p class="text-pink-700">Hai già inviato la tua selezione. Puoi visualizzare il report delle date più selezionate.</p>
      </div>

      <a href="report.php" class="btn block text-center w-full bg-indigo-600 text-white px-6 py-3 rounded-lg">
        <i class="fas fa-chart-bar mr-2"></i> Vai al report
      </a>
    <?php endif; ?>

    <div class="mt-6 text-center text-gray-500 text-sm">
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
            tag.className = 'date-tag bg-indigo-100 text-indigo-800 text-sm font-medium px-3 py-1.5 rounded-full flex items-center';

            const icon = document.createElement('i');
            icon.className = 'fas fa-calendar-day mr-1.5';
            tag.appendChild(icon);

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
            selectedDatesContainer.innerHTML = '<p class="text-gray-400 text-center w-full my-2"><i class="far fa-calendar mr-1"></i> Nessuna data selezionata</p>';
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
</body>

</html>