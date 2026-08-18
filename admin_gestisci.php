<?php
// FILE: admin_gestisci.php

session_start();

if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    // FIX: redirect invece di die(), coerente con tutti gli altri file admin
    header("Location: login.php");
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require_once 'config_mail.php';
require_once 'db_connect.php';

if (!isset($_GET['id']) || !isset($_GET['azione'])) {
    header("Location: admin_planner.php");
    exit;
}

$id    = intval($_GET['id']);
$azione = $_GET['azione'];

// Validazione azione: solo valori previsti
if (!in_array($azione, ['conferma_email', 'conferma_no_email', 'rifiuta'])) {
    header("Location: admin_planner.php");
    exit;
}

// Recupero dati prenotazione con prepared statement
$stmt = $conn->prepare("SELECT * FROM prenotazioni WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res  = $stmt->get_result();
$stmt->close();

if ($res->num_rows === 0) {
    die("Prenotazione non trovata.");
}

$row           = $res->fetch_assoc();
$email_cliente = $row['email'];
$nome          = $row['nome'];
$data_human    = date("d/m/Y", strtotime($row['data_inizio']));
$ora_human     = date("H:i",   strtotime($row['data_inizio']));
$msg_esito     = "";

// --- CASO 1: CONFERMA CON EMAIL ---
if ($azione == 'conferma_email') {

    $stmt2 = $conn->prepare("UPDATE prenotazioni SET status = 'confermata' WHERE id = ?");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $stmt2->close();

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_USER, MAIL_FROM_NAME);
        $mail->addAddress($email_cliente);
        $mail->isHTML(true);
        $mail->Subject = "✅ Appuntamento Confermato - Dott.ssa Violo";
        $mail->Body = "
        <div style='font-family:sans-serif; padding:20px; background:#FBF3E4;'>
            <div style='background:white; padding:30px; border-radius:10px; max-width:600px; margin:0 auto; border-top:4px solid #668073;'>
                <h1 style='color:#668073;'>Confermato!</h1>
                <p>Ciao $nome,</p>
                <p>Il tuo appuntamento del <strong>$data_human</strong> alle ore <strong>$ora_human</strong> è confermato.</p>
                <p>A presto,<br>Dott.ssa Violo</p>
            </div>
        </div>";

        $mail->send();
        $msg_esito = "Prenotazione confermata e <strong>EMAIL INVIATA</strong> a $nome.";
    } catch (Exception $e) {
        $msg_esito = "Prenotazione confermata, ma errore invio email: " . $mail->ErrorInfo;
    }

// --- CASO 2: CONFERMA SENZA EMAIL ---
} elseif ($azione == 'conferma_no_email') {
    $stmt2 = $conn->prepare("UPDATE prenotazioni SET status = 'confermata' WHERE id = ?");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $stmt2->close();
    $msg_esito = "Prenotazione confermata <strong>senza</strong> inviare email.";

// --- CASO 3: RIFIUTA ---
} elseif ($azione == 'rifiuta') {
    $stmt2 = $conn->prepare("UPDATE prenotazioni SET status = 'rifiutata' WHERE id = ?");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $stmt2->close();
    $msg_esito = "Prenotazione <strong>rifiutata</strong>. Il posto è tornato libero.";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Esito</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
</head>
<body style="font-family:'Montserrat',sans-serif; background:#f0f2f5; display:flex; justify-content:center; align-items:center; height:100vh; margin:0;">
    <div style="background:white; padding:40px; border-radius:15px; text-align:center; box-shadow:0 10px 25px rgba(0,0,0,0.1); border-top:5px solid #668073; max-width:400px; width:90%;">
        <h2 style="color:#333; margin-top:0;">Operazione Completata</h2>
        <p style="font-size:1.1rem; color:#555;"><?php echo $msg_esito; ?></p>
        <a href="admin_planner.php"
           style="display:inline-block; margin-top:20px; text-decoration:none; background:#668073; color:white; padding:12px 25px; border-radius:50px; font-weight:bold;">
            Torna al Planner
        </a>
    </div>
</body>
</html>
