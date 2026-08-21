<?php
require_once 'security.php';
require_admin_login();
require_csrf_token();
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id     = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $azione = $_POST['azione'] ?? '';

    if ($id <= 0 || !in_array($azione, ['elimina', 'salva'], true)) {
        $conn->close();
        header("Location: admin_calendario.php");
        exit;
    }

    if ($azione == 'elimina') {
        $stmt = $conn->prepare("DELETE FROM prenotazioni WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    } elseif ($azione == 'salva') {
        $data = $_POST['nuova_data'] ?? '';
        $ora = $_POST['nuova_ora'] ?? '';
        $status = $_POST['nuovo_status'] ?? '';
        $data_inizio = DateTime::createFromFormat('!Y-m-d H:i', "$data $ora");

        if (!$data_inizio || !in_array($status, ['in_attesa', 'confermata', 'cancellata'], true)) {
            $conn->close();
            header("Location: admin_calendario.php");
            exit;
        }

        $stmt_servizio = $conn->prepare("SELECT servizio FROM prenotazioni WHERE id = ?");
        $stmt_servizio->bind_param("i", $id);
        $stmt_servizio->execute();
        $servizio = $stmt_servizio->get_result()->fetch_assoc()['servizio'] ?? 'controllo';
        $stmt_servizio->close();

        $durata = ($servizio === 'prima_visita') ? 60 : 30;
        $data_inizio_completa = $data_inizio->format('Y-m-d H:i:s');
        $data_fine_completa = (clone $data_inizio)->modify("+$durata minutes")->format('Y-m-d H:i:s');
        $stmt = $conn->prepare("UPDATE prenotazioni SET data_inizio = ?, data_fine = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sssi", $data_inizio_completa, $data_fine_completa, $status, $id);
        $stmt->execute();
        $stmt->close();
    }
}
$conn->close();
header("Location: admin_calendario.php");
exit;
