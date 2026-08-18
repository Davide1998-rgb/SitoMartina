<?php
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header("Location: login.php"); exit;
}
require_once 'db_connect.php';

if (isset($_GET['id'])) {
    $id   = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT nome, telefono FROM prenotazioni WHERE id = ?");
    $stmt->bind_param("i", $id); $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc(); $nome = $row['nome']; $telefono = $row['telefono'];
        $stmt->close();

        $stmt2 = $conn->prepare("UPDATE prenotazioni SET status = 'rifiutata' WHERE id = ?");
        $stmt2->bind_param("i", $id);
        if ($stmt2->execute()) {
            echo "<div style='font-family:sans-serif;text-align:center;padding:50px;background:#fff0f0;'>
                    <h1 style='color:#d9534f;'>Prenotazione Rifiutata</h1>
                    <p>Hai rifiutato la richiesta di <strong>$nome</strong>.</p>
                    <hr style='width:50%;margin:20px auto;'>
                    <p><strong>⚠️ AZIONE RICHIESTA:</strong> Contatta il paziente per avvisarlo.</p>
                    <p>Telefono: <strong>$telefono</strong></p>
                    <a href='admin_planner.php' style='color:#668073;font-weight:bold;'>Torna al Planner</a>
                  </div>";
        } else { echo "Errore: " . $conn->error; }
        $stmt2->close();
    } else { $stmt->close(); echo "Prenotazione non trovata."; }
}
$conn->close();
?>
