<?php
// FILE: api_check_mese.php
// Restituisce i giorni del mese da bloccare nel calendario di prenotazione.

require_once 'db_connect.php';
require_once 'disponibilita.php';
date_default_timezone_set('Europe/Rome');

// Disabilita la visualizzazione degli errori HTML per evitare di rompere il formato JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

$input = json_decode(file_get_contents('php://input'), true);
$mese  = isset($input['mese']) ? intval($input['mese']) : (int)date('m');
$anno  = isset($input['anno']) ? intval($input['anno']) : (int)date('Y');

// Validazione input
if ($mese < 1 || $mese > 12 || $anno < 2000 || $anno > 2100) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

$giorni_bloccati = [];
$oggi            = date('Y-m-d');
$giorni_nel_mese = cal_days_in_month(CAL_GREGORIAN, $mese, $anno);

// Analizza ogni giorno del mese
for ($i = 1; $i <= $giorni_nel_mese; $i++) {
    $data_ciclo       = sprintf("%04d-%02d-%02d", $anno, $mese, $i);
    $giorno_settimana = (int)date('N', strtotime($data_ciclo)); // 1=Lun, 7=Dom

    // Giorni passati → bloccati
    if ($data_ciclo < $oggi) {
        $giorni_bloccati[] = $i;
        continue;
    }

    // Sono prenotabili solo martedì, mercoledì, venerdì e sabato.
    if (!in_array($giorno_settimana, [2, 3, 5, 6], true)) {
        $giorni_bloccati[] = $i;
        continue;
    }

    // Non consentire prenotazioni per il giorno stesso.
    if ($data_ciclo === $oggi) {
        $giorni_bloccati[] = $i;
        continue;
    }

    if (disponibilita_giorno_bloccato($conn, $data_ciclo)) {
        $giorni_bloccati[] = $i;
    }
}

header('Content-Type: application/json');
echo json_encode($giorni_bloccati);

$conn->close();
?>