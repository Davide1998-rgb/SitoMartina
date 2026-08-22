<?php
require_once 'security.php';
require_admin_login();
require_once 'db_connect.php';

date_default_timezone_set('Europe/Rome');

$result = $conn->query(
    "SELECT id, nome, email, telefono, modalita_visita, servizio, data_inizio, data_fine, status
     FROM prenotazioni
     ORDER BY data_inizio DESC"
);

$filename = 'backup-prenotazioni-' . date('Y-m-d-His') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');

$output = fopen('php://output', 'wb');
fwrite($output, "\xEF\xBB\xBF");
fputcsv($output, ['ID', 'Nome', 'Email', 'Telefono', 'Modalita', 'Servizio', 'Inizio', 'Fine', 'Stato'], ';');

if ($result) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row, ';');
    }
}

fclose($output);
$conn->close();
exit;
