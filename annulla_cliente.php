<?php
require_once 'security.php';
require_once 'db_connect.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$token = (string)($_GET['token'] ?? '');
$annullata = false;

if ($id > 0 && verify_action_token('annulla_cliente', $id, $token)) {
    $stmt = $conn->prepare("UPDATE prenotazioni SET status = 'annullata' WHERE id = ? AND status = 'confermata'");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $annullata = $stmt->affected_rows > 0;
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Annullamento appuntamento - Dott.ssa Violo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="admin-result <?= $annullata ? 'success' : 'error' ?>">
        <h1><?= $annullata ? 'Appuntamento annullato' : 'Link non valido' ?></h1>
        <p><?= $annullata
            ? 'La richiesta è stata annullata. Grazie per averci avvisato.'
            : 'Il link non è valido, è già stato utilizzato oppure l’appuntamento non può più essere annullato.' ?></p>
        <a href="index.html">Torna al sito</a>
    </main>
</body>
</html>
