<?php
require_once 'security.php';
require_admin_login();
require_once 'db_connect.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$response = [
    'count' => 0,
    'latest_id' => 0,
    'latest_name' => ''
];

$count_result = $conn->query("SELECT COUNT(*) AS totale FROM prenotazioni WHERE status = 'in_attesa'");
if ($count_result) {
    $response['count'] = (int)($count_result->fetch_assoc()['totale'] ?? 0);
}

$latest_result = $conn->query(
    "SELECT id, nome FROM prenotazioni
     WHERE status = 'in_attesa' ORDER BY id DESC LIMIT 1"
);
if ($latest_result && ($latest = $latest_result->fetch_assoc())) {
    $response['latest_id'] = (int)$latest['id'];
    $response['latest_name'] = $latest['nome'];
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);