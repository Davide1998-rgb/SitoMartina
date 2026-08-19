<?php
// FILE: salva_recensione.php
// Salva una recensione inserita manualmente dall'admin (già approvata).

session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'db_connect.php';

$icona    = "";
$titolo   = "";
$messaggio = "";
$colore   = "";
$link_back = "dashboard.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome  = trim($_POST['nome'] ?? '');
    $testo = trim($_POST['testo'] ?? '');
    $voto  = (int)$_POST['voto'];

    if ($nome === '' || strlen($nome) > 150 || $testo === '' || strlen($testo) > 5000 || $voto < 1 || $voto > 5) {
        die("Dati recensione non validi. Torna indietro e riprova.");
    }

    // FIX: prepared statement al posto di real_escape_string + interpolazione
    $stmt = $conn->prepare(
        "INSERT INTO recensioni (nome, testo, voto, approvata) VALUES (?, ?, ?, 1)"
    );
    $stmt->bind_param("ssi", $nome, $testo, $voto);

    if ($stmt->execute()) {
        $colore    = "#668073";
        $icona     = "bx-check-circle";
        $titolo    = "Recensione Salvata!";
        $messaggio = "La recensione di <strong>" . htmlspecialchars($nome) . "</strong> è stata aggiunta con successo.";
    } else {
        $colore    = "#d9534f";
        $icona     = "bx-error-circle";
        $titolo    = "Errore!";
        $messaggio = "Impossibile salvare la recensione.<br>Dettagli: " . $conn->error;
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esito Salvataggio</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body {
            background-color: #FBF3E4;
            font-family: 'Montserrat', sans-serif;
            margin: 0; padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .card {
            background: #FFFFFF;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            text-align: center;
            max-width: 400px;
            width: 90%;
            border-top: 5px solid <?php echo $colore; ?>;
        }
        .icon-box {
            font-size: 60px;
            color: <?php echo $colore; ?>;
            margin-bottom: 20px;
            animation: popIn 0.5s ease;
        }
        h1 {
            font-family: 'Playfair Display', serif;
            color: #1A2621;
            margin: 0 0 15px 0;
            font-size: 24px;
        }
        p { color:#666; line-height:1.6; margin-bottom:30px; }
        .btn {
            background-color: #668073;
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
        }
        .btn:hover {
            background-color: #556b60;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(102,128,115,0.3);
        }
        @keyframes popIn {
            0%   { transform:scale(0); opacity:0; }
            80%  { transform:scale(1.1); }
            100% { transform:scale(1); opacity:1; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-box">
            <i class='bx <?php echo $icona; ?>'></i>
        </div>
        <h1><?php echo $titolo; ?></h1>
        <p><?php echo $messaggio; ?></p>
        <a href="<?php echo $link_back; ?>" class="btn">Torna alla Dashboard</a>
    </div>
</body>
</html>
