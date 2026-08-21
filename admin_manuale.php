<?php
// FILE: admin_manuale.php
// Inserimento manuale di un appuntamento da parte dell'admin.

require_once 'security.php';
require_admin_login();
require_once 'db_connect.php';

$messaggio = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_csrf_token();

    $nome     = trim($_POST['nome'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $data     = trim($_POST['data'] ?? '');
    $ora      = trim($_POST['ora'] ?? '');
    $servizio = in_array($_POST['servizio'] ?? '', ['prima_visita', 'controllo'], true) ? $_POST['servizio'] : 'controllo';

    $data_valida = DateTime::createFromFormat('!Y-m-d H:i', "$data $ora");

    if ($nome === '' || strlen($nome) > 150 || $telefono === '' || !$data_valida) {
        $messaggio = "<div style='color:red; background:#ffebee; padding:10px; border-radius:5px; margin-bottom:15px;'>
                        Dati inseriti non validi. Controlla e riprova.
                      </div>";
    } else {
        $durata      = ($servizio === 'prima_visita') ? 60 : 30;
        $data_inizio = $data_valida->format('Y-m-d H:i:s');
        $data_fine   = (clone $data_valida)->modify("+$durata minutes")->format('Y-m-d H:i:s');

        $stmt = $conn->prepare(
            "INSERT INTO prenotazioni (nome, email, telefono, servizio, data_inizio, data_fine, status)
             VALUES (?, ?, ?, ?, ?, ?, 'confermata')"
        );
        $stmt->bind_param("ssssss", $nome, $email, $telefono, $servizio, $data_inizio, $data_fine);

        if ($stmt->execute()) {
            $messaggio = "<div style='color:green; font-weight:bold; background:#e8f5e9; padding:10px; border-radius:5px; margin-bottom:15px;'>
                            ✅ Appuntamento inserito con successo!
                          </div>";
        } else {
            $messaggio = "<div style='color:red; background:#ffebee; padding:10px; border-radius:5px; margin-bottom:15px;'>
                            Errore: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8') . "
                          </div>";
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="icon" type="image/png" href="img/logo.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inserimento Manuale</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family:'Montserrat',sans-serif; background:#F0F2F5; padding:20px; }
        .container { max-width:500px; margin:0 auto; background:white; padding:30px; border-radius:10px; box-shadow:0 5px 15px rgba(0,0,0,0.1); }
        label { display:block; margin-top:15px; font-weight:600; font-size:0.9rem; color:#444; }
        input, select { width:100%; padding:12px; margin:8px 0; border:1px solid #ccc; border-radius:5px; box-sizing:border-box; font-size:16px; }
        input:focus, select:focus { outline:none; border-color:#668073; }
        .btn { background:#668073; color:white; padding:14px; border:none; width:100%; cursor:pointer; font-weight:bold; border-radius:5px; font-size:1rem; margin-top:20px; }
        .btn:hover { background:#4a5e54; }
    </style>
</head>
<body>
    <?php include 'admin_topbar.php'; ?>
    <div class="container">
        <h2 style="color:#1A2621; margin-top:0;">Nuovo Appuntamento</h2>
        <p style="font-size:0.9rem; color:#666;">Inserisci un appuntamento preso al telefono o di persona.</p>

        <?php echo $messaggio; ?>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <label>Nome e Cognome</label>
            <input type="text" name="nome" required placeholder="Es. Mario Rossi">

            <label>Email <span style="font-weight:normal; color:#999;">(opzionale)</span></label>
            <input type="email" name="email" placeholder="email@esempio.com">

            <label>Telefono</label>
            <input type="tel" name="telefono" required placeholder="Es. 3471234567">

            <label>Tipo Visita</label>
            <select name="servizio">
                <option value="prima_visita">Prima Visita (60 min)</option>
                <option value="controllo">Controllo (30 min)</option>
            </select>

            <label>Data</label>
            <input type="date" name="data" required>

            <label>Ora Inizio</label>
            <input type="time" name="ora" required>

            <button type="submit" class="btn">Salva in Agenda</button>
        </form>
    </div>
</body>
</html>
