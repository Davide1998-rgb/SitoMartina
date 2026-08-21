<?php
// FILE: conferma_cliente.php
// Pagina pubblica: il paziente clicca "Sì, ci sarò" dall'email di promemoria.

require_once 'security.php';
require_once 'db_connect.php';

$id    = isset($_GET['id']) ? intval($_GET['id']) : 0;
$token = $_GET['token'] ?? '';

$esito_valido = false;

// Verifica che l'ID esista e che il token HMAC corrisponda
if ($id > 0 && verify_action_token('conferma_cliente', $id, $token)) {
    $stmt = $conn->prepare("UPDATE prenotazioni SET conferma_cliente = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    $esito_valido = true;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="icon" type="image/png" href="img/logo.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conferma Presenza - Dott.ssa Violo</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #FBF3E4;
            font-family: 'Montserrat', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-top: 5px solid #668073;
        }
        h1 { font-family:'Playfair Display',serif; color:#668073; margin-bottom:10px; }
        p  { color:#555; line-height:1.6; }
        .icon { font-size:50px; margin-bottom:20px; display:block; }
        .btn {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #668073;
            border: 2px solid #668073;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: bold;
            transition: all 0.3s;
        }
        .btn:hover { background:#668073; color:white; }
    </style>
</head>
<body>
    <div class="card">
        <?php if ($esito_valido): ?>
            <span class="icon">✅</span>
            <h1>Grazie!</h1>
            <p>La tua presenza per domani è stata confermata correttamente.</p>
            <p>La Dott.ssa Violo ti aspetta in studio.</p>
        <?php else: ?>
            <span class="icon">⚠️</span>
            <h1 style="color:#d9534f;">Link non valido</h1>
            <p>Questo link di conferma non è valido o è scaduto.</p>
            <p>Per informazioni, contatta direttamente la Dott.ssa Violo.</p>
        <?php endif; ?>
        <a href="index.html" class="btn">Torna al sito</a>
    </div>
</body>
</html>
