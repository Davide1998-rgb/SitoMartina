<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$sql = "SELECT nome, testo, voto FROM recensioni WHERE approvata = 1 ORDER BY id DESC";
$result = $conn->query($sql);

$recensioni = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $recensioni[] = $row;
    }
}

echo json_encode($recensioni);
$conn->close();
?>