<?php
// Gestione del parametro URL "event" condiviso tra le pagine.
// Ogni evento ha il proprio file dati (db_<evento>.json) e il proprio cookie di voto.

// Restituisce il nome dell'evento dal parametro URL "event".
// Se il parametro manca o non è valido, mostra una pagina di errore e termina.
function getEventOrFail()
{
  $event = trim($_GET['event'] ?? '');

  // Solo lettere, numeri, trattini e underscore: il nome viene usato anche come nome file.
  // Minuscolo per rendere l'evento case-insensitive (stesso file e cookie per Cena/cena).
  if ($event !== '' && preg_match('/^[A-Za-z0-9_-]{1,50}$/', $event)) {
    return strtolower($event);
  }

  eventErrorPage(
    400,
    'Evento non specificato',
    'Questa pagina richiede un evento valido nel parametro URL <code class="bg-slate-100 px-1.5 py-0.5 rounded text-xs">event</code>.',
    'Esempio: <code class="bg-slate-100 px-1.5 py-0.5 rounded">?event=nome-evento</code> (lettere, numeri, trattini e underscore)'
  );
}

// Mostra una pagina di errore e termina ($message e $hint sono HTML statico)
function eventErrorPage($code, $title, $message, $hint = '')
{
  http_response_code($code);
?>
<!DOCTYPE html>
<html lang="it">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title) ?></title>
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
  </style>
</head>

<body class="min-h-[100dvh] flex items-center justify-center p-4 bg-slate-50">
  <div class="card bg-white p-8 rounded-lg w-full max-w-md text-center">
    <h1 class="text-xl font-semibold mb-4 text-slate-800 flex items-center justify-center">
      <i data-lucide="alert-circle" class="w-5 h-5 mr-2 text-red-500"></i><?= htmlspecialchars($title) ?>
    </h1>
    <p class="text-slate-600 text-sm mb-2">
      <?= $message ?>
    </p>
    <?php if ($hint !== ''): ?>
      <p class="text-slate-400 text-xs">
        <?= $hint ?>
      </p>
    <?php endif; ?>
  </div>
  <script>lucide.createIcons();</script>
</body>

</html>
<?php
  exit;
}

// Percorso del file dati dell'evento
function eventDbFile($event)
{
  return __DIR__ . '/db_' . $event . '.json';
}

// Dati dell'evento, salvati nel suo file JSON come:
//   {"closed": false, "votes": [...]}
// Per chiudere un evento basta impostare "closed": true nel file: le
// votazioni si fermano e resta consultabile solo il report.
// I file nel formato precedente (la sola lista dei voti) vengono convertiti
// al primo accesso, così la proprietà "closed" è già lì da modificare.
function eventLoad($event)
{
  $file = eventDbFile($event);
  $json = file_exists($file) ? trim(file_get_contents($file)) : '';
  if ($json === '') {
    return ['closed' => false, 'votes' => []];
  }

  $data = json_decode($json, true);
  if (!is_array($data)) {
    // Un file illeggibile (es. dopo una modifica a mano sbagliata) non va mai
    // trattato come vuoto: il primo voto successivo lo sovrascriverebbe
    eventErrorPage(
      500,
      'Dati evento non leggibili',
      'Il file dati di questo evento non è un JSON valido: va corretto prima di poter votare o consultare il report.'
    );
  }

  if ($data === [] || isset($data[0])) {
    $data = ['closed' => false, 'votes' => $data];
    eventSave($event, $data);
  }

  // "closed" e "votes" normalizzati e in testa; altre proprietà aggiunte a mano restano
  return [
    'closed' => filter_var($data['closed'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'votes' => is_array($data['votes'] ?? null) ? $data['votes'] : [],
  ] + $data;
}

// Salva i dati dell'evento in un JSON leggibile e modificabile a mano.
// Se la codifica fallisse non scrive nulla, per non azzerare il file.
function eventSave($event, $data)
{
  $data['votes'] = array_values($data['votes']);
  $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
  if ($json !== false) {
    file_put_contents(eventDbFile($event), $json . "\n");
  }
}

// Nome del cookie "ha già votato" dell'evento
function eventCookieName($event)
{
  return 'already_submitted_' . $event;
}

// Nome con cui questo browser ha già votato per l'evento, oppure null se non
// ha ancora votato
function eventSubmittedName($event)
{
  return $_COOKIE[eventCookieName($event)] ?? null;
}

// Elimina il cookie di voto dell'evento
function eventForgetSubmission($event)
{
  setcookie(eventCookieName($event), '', time() - 3600);
}
