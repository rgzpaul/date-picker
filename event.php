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

  http_response_code(400);
?>
<!DOCTYPE html>
<html lang="it">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Evento non specificato</title>
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
      <i data-lucide="alert-circle" class="w-5 h-5 mr-2 text-red-500"></i>Evento non specificato
    </h1>
    <p class="text-slate-600 text-sm mb-2">
      Questa pagina richiede un evento valido nel parametro URL <code class="bg-slate-100 px-1.5 py-0.5 rounded text-xs">event</code>.
    </p>
    <p class="text-slate-400 text-xs">
      Esempio: <code class="bg-slate-100 px-1.5 py-0.5 rounded">?event=nome-evento</code> (lettere, numeri, trattini e underscore)
    </p>
  </div>
  <script>lucide.createIcons();</script>
</body>

</html>
<?php
  exit;
}

// Percorso del file dati dell'evento.
// Migra al volo eventuali file creati prima della normalizzazione in minuscolo
// (es. db_Cena.json -> db_cena.json), unendo i voti se esistono entrambi.
function eventDbFile($event)
{
  $target = 'db_' . $event . '.json';
  $file = __DIR__ . '/' . $target;

  foreach (glob(__DIR__ . '/db_*.json') as $candidate) {
    $base = basename($candidate);
    if ($base !== $target && strtolower($base) === $target) {
      if (!file_exists($file)) {
        rename($candidate, $file);
      } else {
        $old = json_decode(file_get_contents($candidate), true) ?? [];
        $new = json_decode(file_get_contents($file), true) ?? [];
        $merged = [];
        $seen = [];
        foreach (array_merge($old, $new) as $entry) {
          $key = json_encode($entry);
          if (!isset($seen[$key])) {
            $seen[$key] = true;
            $merged[] = $entry;
          }
        }
        // Prima elimina, poi scrivi: sicuro anche su filesystem case-insensitive
        unlink($candidate);
        file_put_contents($file, json_encode($merged));
      }
      break;
    }
  }

  return $file;
}

// Nome del cookie "ha già votato" dell'evento
function eventCookieName($event)
{
  return 'already_submitted_' . $event;
}

// Nome con cui questo browser ha già votato per l'evento, oppure null se non
// ha ancora votato. Stringa vuota se il cookie è di una versione precedente
// che non memorizzava il nome (valore 'true').
// Confronto case-insensitive sul nome del cookie per riconoscere anche i
// cookie impostati prima della normalizzazione (es. already_submitted_Cena).
function eventSubmittedName($event)
{
  $target = eventCookieName($event);
  $legacyFound = null;
  foreach ($_COOKIE as $name => $value) {
    if (strtolower($name) === $target) {
      if ($value !== 'true') {
        return $value;
      }
      $legacyFound = '';
    }
  }
  return $legacyFound;
}
