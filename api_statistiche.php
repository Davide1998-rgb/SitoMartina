<?php
// PERCORSO SICURO: fuori dalla webroot pubblica.
// Crea la cartella: mkdir /var/www/private_data && chmod 750 /var/www/private_data
// Adatta il percorso al tuo hosting.
define('STATS_DIR', dirname(__DIR__) . '/private_data/');

$tempo_speso  = isset($_POST['tempo'])     ? (int)$_POST['tempo']     : 0;
$ha_prenotato = isset($_POST['prenotato']) ? (int)$_POST['prenotato'] : 0;
$evento       = $_POST['evento'] ?? '';
$user_agent   = $_SERVER['HTTP_USER_AGENT'] ?? '';

$is_bot = 0;
foreach (['bot','crawl','spider','slurp','google','bing','yandex'] as $kw) {
    if (stripos($user_agent, $kw) !== false) { $is_bot = 1; break; }
}
if ($is_bot) $tempo_speso = 0;

$mese_corrente   = date('Y_m');
$giorno_corrente = date('d');
$file_stats      = STATS_DIR . "stats_" . $mese_corrente . ".json";

if (!is_dir(STATS_DIR)) mkdir(STATS_DIR, 0750, true);

// FIX: lock esclusivo per evitare race condition su scritture concorrenti
$fp = fopen($file_stats, 'c+');
if ($fp && flock($fp, LOCK_EX)) {
    $contenuto = stream_get_contents($fp);
    $dati = !empty($contenuto) ? json_decode($contenuto, true) : [];
    if (!is_array($dati)) $dati = [];

    if (!isset($dati[$giorno_corrente])) {
        $dati[$giorno_corrente] = ['visite_umane' => 0, 'visite_bot' => 0, 'tempo_totale_secondi' => 0, 'prenotazioni' => 0];
    }

    if ($evento === 'prenotazione') {
        $dati[$giorno_corrente]['prenotazioni'] += 1;
    } elseif ($is_bot) {
        $dati[$giorno_corrente]['visite_bot'] += 1;
    } else {
        $dati[$giorno_corrente]['visite_umane']         += 1;
        $dati[$giorno_corrente]['tempo_totale_secondi'] += $tempo_speso;
        if ($ha_prenotato) $dati[$giorno_corrente]['prenotazioni'] += 1;
    }

    rewind($fp); ftruncate($fp, 0);
    fwrite($fp, json_encode($dati, JSON_PRETTY_PRINT));
    flock($fp, LOCK_UN);
}
if ($fp) fclose($fp);
?>
