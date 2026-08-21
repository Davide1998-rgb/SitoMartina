<?php
require_once 'db_connect.php';
require_once 'disponibilita.php';
date_default_timezone_set('Europe/Rome');

// Disabilita la visualizzazione degli errori HTML per evitare di rompere il formato JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

$input          = json_decode(file_get_contents('php://input'), true);
$data_scelta    = isset($input['data'])   ? trim($input['data'])     : '';
$durata_minuti  = isset($input['durata']) ? intval($input['durata']) : 30;

$data_validata = DateTime::createFromFormat('!Y-m-d', $data_scelta);
$errori_data = DateTime::getLastErrors();
$data_valida = $data_validata &&
    ($errori_data === false || ($errori_data['warning_count'] === 0 && $errori_data['error_count'] === 0));

if (!$data_valida || !in_array($durata_minuti, [30, 60], true)) {
    header('Content-Type: application/json');
    echo json_encode(["consigliati" => [], "altri" => []]);
    exit;
}

$oggi = date('Y-m-d');

$orari_possibili = [];

function generaSlot($inizio, $fine, $durata, &$array_target) {
    $start = strtotime($inizio);
    $end   = strtotime($fine);
    while ($start <= $end) {
        $array_target[] = date("H:i", $start);
        $start += (30 * 60); // Step di 30 minuti
    }
}

$giorno_settimana = (int)date('N', strtotime($data_scelta));

// Il giorno stesso e i giorni di chiusura non sono prenotabili.
if ($data_scelta <= $oggi || !in_array($giorno_settimana, [2, 3, 5, 6], true)) {
    header('Content-Type: application/json');
    echo json_encode(["consigliati" => [], "altri" => []]);
    exit;
}

// Fasce orarie dello studio, variabili in base al giorno e al servizio.
if ($giorno_settimana === 2) {
    generaSlot("14:00", "20:00", $durata_minuti, $orari_possibili);
} elseif ($giorno_settimana === 6) {
    generaSlot("09:00", $durata_minuti === 60 ? "13:00" : "13:30", $durata_minuti, $orari_possibili);
} else {
    generaSlot("09:00", "20:00", $durata_minuti, $orari_possibili);
}

// 1. Recupero prenotazioni esistenti dal database
$prenotazioni_esistenti = [];
$stmt = $conn->prepare(
    "SELECT data_inizio, data_fine FROM prenotazioni
     WHERE DATE(data_inizio) = ? AND status IN ('confermata', 'in_attesa')"
);
$stmt->bind_param("s", $data_scelta);
$stmt->execute();
$res_pren = $stmt->get_result();
while ($p = $res_pren->fetch_assoc()) {
    $prenotazioni_esistenti[] = [
        'start' => strtotime($p['data_inizio']),
        'end'   => strtotime($p['data_fine'])
    ];
}
$stmt->close();

// 2. Filtraggio orari liberi
$orari_disponibili = [];
foreach ($orari_possibili as $slot) {
    $slot_start  = strtotime($data_scelta . ' ' . $slot);
    $slot_end    = $slot_start + ($durata_minuti * 60);
    $sovrapposto = false;

    if (disponibilita_fascia_bloccata($conn, $data_scelta, $slot, date('H:i', $slot_end))) {
        continue;
    }

    if (!$sovrapposto) {
        foreach ($prenotazioni_esistenti as $pren) {
            if ($slot_start < $pren['end'] && $slot_end > $pren['start']) {
                $sovrapposto = true;
                break;
            }
        }
    }

    if (!$sovrapposto) {
        $orari_disponibili[] = $slot;
    }
}

// 3. Calcolo orari CONSIGLIATI (subito prima o subito dopo una visita già prenotata)
$consigliati = [];

if (!empty($prenotazioni_esistenti)) {
    foreach ($orari_disponibili as $ora) {
        $slot_start = strtotime($data_scelta . ' ' . $ora);
        $slot_end   = $slot_start + ($durata_minuti * 60);

        foreach ($prenotazioni_esistenti as $pren) {
            if ($slot_end == $pren['start'] || $slot_start == $pren['end']) {
                $consigliati[] = $ora;
                break;
            }
        }
    }
}

// Se non ci sono adiacenze o non ci sono prenotazioni nel giorno, proponi i primi 3 orari liberi
if (empty($consigliati) && !empty($orari_disponibili)) {
    $consigliati = array_slice($orari_disponibili, 0, 3);
}

// 4. Invio risposta JSON
header('Content-Type: application/json');
echo json_encode([
    "consigliati" => array_values(array_unique($consigliati)),
    "altri"       => array_values($orari_disponibili)
]);
?>