<?php
// Legge le date salvate
$data = json_decode(file_get_contents('db.json'), true) ?? [];

// Conta quante volte ogni data è stata selezionata
$counter = array_count_values($data);

// Ordina per conteggio decrescente
arsort($counter);

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

// Calcola la data più popolare
$mostPopularDate = !empty($counter) ? key($counter) : null;
$totalVotes = array_sum($counter);
$totalDates = count($counter);

// Prepara dati per il grafico
$chartData = [];
$colors = [
  'rgba(79, 70, 229, 0.8)', // Indigo
  'rgba(16, 185, 129, 0.8)', // Emerald
  'rgba(245, 158, 11, 0.8)', // Amber
  'rgba(239, 68, 68, 0.8)',  // Red
  'rgba(99, 102, 241, 0.8)'  // Blue
];

$i = 0;
foreach ($counter as $date => $count) {
  $chartData[] = [
    'date' => formatDateItalian($date),
    'count' => $count,
    'color' => $colors[$i % count($colors)]
  ];
  $i++;
  if ($i >= 5) break; // Limita a 3 date per il grafico
}
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
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

    .badge {
      position: relative;
      overflow: hidden;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .animate-fade-in {
      animation: fadeIn 0.6s ease forwards;
    }

    .chart-container {
      opacity: 0;
      animation: fadeIn 0.8s ease 0.3s forwards;
    }

    /* Mobile Optimizations */
    @media (max-width: 768px) {
      .card {
        padding: 1.25rem !important;
      }

      h1 {
        font-size: 1.5rem !important;
      }

      h2 {
        font-size: 1.25rem !important;
      }

      .stats-card {
        padding: 1rem !important;
      }

      .date-item {
        padding: 0.75rem !important;
      }

      .badge {
        font-size: 0.75rem !important;
        padding: 0.25rem 0.75rem !important;
      }

      canvas {
        height: 200px !important;
      }
    }
  </style>
</head>

<body class="min-h-screen py-12 px-4">
  <div class="max-w-4xl mx-auto">
    <div class="card bg-white p-8 rounded-2xl animate-fade-in">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <h1 class="text-3xl font-bold text-indigo-700">
          <i class="fas fa-chart-bar mr-2"></i>Report
        </h1>

        <a href="index.php" class="btn bg-indigo-600 text-white px-4 py-2 rounded-lg inline-flex items-center justify-center w-full sm:w-auto">
          <i class="fas fa-arrow-left mr-2"></i> Indietro
        </a>
      </div>

      <?php if (!empty($counter)): ?>
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
          <div class="hidden stats-card bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-xl p-6 text-white">
            <div class="text-xl opacity-80 mb-1"><i class="fas fa-users mr-2"></i>Voti</div>
            <div class="text-3xl font-bold"><?= $totalVotes ?></div>
          </div>

          <div class="stats-card bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-xl p-6 text-white">
            <div class="text-xl opacity-80 mb-1"><i class="fas fa-calendar-alt mr-2"></i>Date</div>
            <div class="text-3xl font-bold"><?= $totalDates ?></div>
          </div>

          <?php if ($mostPopularDate): ?>
            <div class="stats-card bg-gradient-to-r from-amber-500 to-amber-600 rounded-xl p-6 text-white">
              <div class="text-xl opacity-80 mb-1"><i class="fas fa-star mr-2"></i>Data scelta</div>
              <div class="text-3xl font-bold"><?= formatDateItalian($mostPopularDate) ?></div>
            </div>
          <?php endif; ?>
        </div>

        <div class="chart-container bg-white p-4 rounded-xl shadow-sm mb-8">
          <canvas id="dateChart" width="400" height="200"></canvas>
        </div>

        <h2 class="text-xl font-semibold text-gray-700 mb-4">
          <i class="fas fa-list-ol mr-2"></i>Classifica
        </h2>

        <ul class="space-y-3">
          <?php foreach ($counter as $date => $count): ?>
            <?php
            // Calcola la percentuale per la progress bar
            $percentage = ($count / $totalVotes) * 100;

            // Determina il colore in base alla popolarità
            $bgColor = 'bg-indigo-100 text-indigo-800';
            $barColor = 'bg-indigo-500';

            if ($percentage > 75) {
              $bgColor = 'bg-emerald-100 text-emerald-800';
              $barColor = 'bg-emerald-500';
            } elseif ($percentage > 50) {
              $bgColor = 'bg-blue-100 text-blue-800';
              $barColor = 'bg-blue-500';
            } elseif ($percentage > 25) {
              $bgColor = 'bg-amber-100 text-amber-800';
              $barColor = 'bg-amber-500';
            }
            ?>
            <li class="date-item p-4 bg-gray-50 rounded-lg shadow-sm border border-gray-200 transition duration-200">
              <div class="flex justify-between items-center mb-2">
                <div class="flex items-center">
                  <span class="badge <?= $bgColor ?> text-sm font-medium px-4 py-1.5 rounded-full mr-3">
                    <?= htmlspecialchars(formatDateItalian($date)) ?>
                  </span>
                  <span class="font-semibold text-gray-700">
                    <?= $count ?> vot<?= ($count == 1) ? 'o' : 'i' ?>
                  </span>
                </div>
                <span class="text-sm text-gray-500"><?= number_format($percentage, 1) ?>%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="<?= $barColor ?> h-2.5 rounded-full" style="width: <?= $percentage ?>%"></div>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <div class="bg-amber-50 border-l-4 border-amber-500 p-6 rounded-md flex items-center">
          <i class="fas fa-exclamation-circle text-amber-500 text-3xl mr-4"></i>
          <div>
            <h3 class="text-xl font-semibold text-amber-800">Nessun dato disponibile</h3>
            <p class="text-amber-700">Non ci sono ancora date selezionate nel sistema.</p>
          </div>
        </div>

        <div class="mt-8 text-center">
          <a href="index.php" class="btn inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg">
            <i class="fas fa-calendar-plus mr-2"></i> Seleziona Date
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($chartData)): ?>
    <script>
      // Prepara i dati per il grafico
      const chartData = <?= json_encode($chartData) ?>;

      // Estrae le etichette e i dati per il grafico
      const labels = chartData.map(item => item.date);
      const data = chartData.map(item => item.count);
      const backgroundColors = chartData.map(item => item.color);

      // Crea il grafico
      const ctx = document.getElementById('dateChart').getContext('2d');
      const dateChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [{
            label: 'Numero di selezioni',
            data: data,
            backgroundColor: backgroundColors,
            borderColor: backgroundColors.map(color => color.replace('0.8', '1')),
            borderWidth: 1,
            borderRadius: 6,
            barThickness: 30,
            maxBarThickness: 40
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              backgroundColor: 'rgba(49, 46, 129, 0.9)',
              titleFont: {
                size: 14,
                weight: 'bold'
              },
              bodyFont: {
                size: 13
              },
              padding: 12,
              displayColors: false,
              callbacks: {
                label: function(context) {
                  const value = context.raw;
                  return `${value} vot${value === 1 ? 'o' : 'i'} (${((value / <?= $totalVotes ?>) * 100).toFixed(1)}%)`;
                }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              grid: {
                display: true,
                color: 'rgba(0, 0, 0, 0.05)'
              },
              ticks: {
                precision: 0,
                font: {
                  size: 12
                }
              }
            },
            x: {
              grid: {
                display: false
              },
              ticks: {
                font: {
                  size: 12,
                  weight: 'bold'
                }
              }
            }
          },
          animation: {
            duration: 1500
          }
        }
      });
    </script>
  <?php endif; ?>
</body>

</html>