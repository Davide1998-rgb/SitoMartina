<?php
require_once 'security.php';
start_secure_session();
require_once 'db_connect.php';

$id    = isset($_GET['id']) ? intval($_GET['id']) : 0;
$token = $_GET['token'] ?? '';

$is_logged = isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true;
$is_valid_token = ($id > 0 && verify_action_token('rifiuta', $id, $token));

if (!$is_logged && !$is_valid_token) {
    header("Location: login.php");
    exit;
}

if ($id > 0) {
    $stmt = $conn->prepare("SELECT nome, telefono FROM prenotazioni WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $nome = $row['nome'];
        $telefono = $row['telefono'];
        $nome_safe = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
        $telefono_safe = htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8');
        $stmt->close();

        $stmt2 = $conn->prepare("UPDATE prenotazioni SET status = 'rifiutata' WHERE id = ?");
        $stmt2->bind_param("i", $id);
        if ($stmt2->execute()) {
            echo "<div style='font-family:sans-serif;text-align:center;padding:50px;background:#fff0f0;'>
                    <h1 style='color:#d9534f;'>Prenotazione Rifiutata</h1>
                    <p>Hai rifiutato la richiesta di <strong>$nome_safe</strong>.</p>
                    <hr style='width:50%;margin:20px auto;'>
                    <p><strong>⚠️ AZIONE RICHIESTA:</strong> Contatta il paziente per avvisarlo.</p>
                    <p>Telefono: <strong>$telefono_safe</strong></p>
                    <a href='admin_planner.php' style='color:#668073;font-weight:bold;'>Vai al Planner</a>
                  </div>";
        } else {
            echo "Errore: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8');
        }
        $stmt2->close();
    } else {
        $stmt->close();
        echo "<div style='font-family:sans-serif;text-align:center;padding:50px;'>Prenotazione non trovata.</div>";
    }
}
$conn->close();
?>
