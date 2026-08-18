<?php
// FILE: conferma_cliente.php
// Pagina pubblica: il paziente clicca "Sì, ci sarò" dall'email di promemoria.

require_once 'db_connect.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// FIX: prepared statement invece di query diretta con id interpolato
if ($id > 0) {
    $stmt = $conn->prepare("UPDATE prenotazioni SET conferma_cliente = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
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
        <span class="icon">✅</span>
        <h1>Grazie!</h1>
        <p>La tua presenza per domani è stata confermata correttamente.</p>
        <p>La Dott.ssa Violo ti aspetta in studio.</p>
        <a href="index.html" class="btn">Torna al sito</a>
    </div>
</body>
</html>
