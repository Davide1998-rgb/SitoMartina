<?php
session_start();
// FIX: controllo completo (prima era solo isset senza === true)
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header("Location: login.php"); exit;
}
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id    = intval($_POST['id']);
    $azione = $_POST['azione'];

    if ($azione == 'elimina') {
        $stmt = $conn->prepare("DELETE FROM prenotazioni WHERE id = ?");
        $stmt->bind_param("i", $id); $stmt->execute(); $stmt->close();
    } elseif ($azione == 'salva') {
        $data_inizio_completa = $_POST['nuova_data'] . " " . $_POST['nuova_ora'] . ":00";
        $status = $_POST['nuovo_status'];
        $stmt = $conn->prepare("UPDATE prenotazioni SET data_inizio = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssi", $data_inizio_completa, $status, $id);
        $stmt->execute(); $stmt->close();
    }
}
$conn->close();
header("Location: admin_calendario.php"); exit;
?>
