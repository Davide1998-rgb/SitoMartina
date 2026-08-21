<?php
require_once 'security.php';
start_secure_session();

require_once 'db_connect.php';
require_once 'aggiorna_index_recensioni.php';

$id     = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = $_GET['action'] ?? '';
$token  = $_GET['token'] ?? '';

if (!in_array($action, ['approve', 'delete'], true) || $id <= 0) {
    die("Parametri non validi.");
}

$token_action_name = ($action === 'approve') ? 'approve_review' : 'delete_review';
$is_logged = isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true;
$is_valid_token = verify_action_token($token_action_name, $id, $token);

if (!$is_logged && !$is_valid_token) {
    header("Location: login.php");
    exit;
}

if ($action === 'approve') {
    $stmt = $conn->prepare("UPDATE recensioni SET approvata = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $msg  = "Recensione pubblicata con successo! Ora è visibile sul sito.";
    $color = "green";
} else {
    $stmt = $conn->prepare("DELETE FROM recensioni WHERE id = ?");
    $stmt->bind_param("i", $id);
    $msg  = "Recensione eliminata definitivamente.";
    $color = "red";
}

if ($stmt->execute()) {
    $stmt->close();
    aggiornaIndexRecensioni($conn);
    echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>
            <h1 style='color:$color;'>Eseguito!</h1>
            <p>$msg</p>
            <a href='dashboard.php' style='display:inline-block; margin-top:15px; text-decoration:none; background:#668073; color:white; padding:10px 20px; border-radius:5px;'>Vai alla Dashboard</a>
          </div>";
} else {
    echo "Errore: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8');
}

$conn->close();
?>
