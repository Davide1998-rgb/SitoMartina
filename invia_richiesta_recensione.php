<?php
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) { header("Location: login.php"); exit; }

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/Exception.php'; require 'PHPMailer/PHPMailer.php'; require 'PHPMailer/SMTP.php';
require_once 'config_mail.php';

$messaggio = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome_cliente  = htmlspecialchars($_POST['nome']);
    $email_cliente = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    // FIX: usa BASE_URL da config_mail.php invece di stringa hardcoded
    $link_recensione = BASE_URL . "/lascia_recensione.html";

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP(); $mail->Host = MAIL_HOST; $mail->SMTPAuth = true;
        $mail->Username = MAIL_USER; $mail->Password = MAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = MAIL_PORT; $mail->CharSet = 'UTF-8';
        $mail->setFrom(MAIL_USER, MAIL_FROM_NAME);
        $mail->addAddress($email_cliente, $nome_cliente);
        $mail->isHTML(true);
        $mail->Subject = "Come procede il tuo percorso con me? Raccontamelo!";
        // FIX: rimosso il doppio </div> finale che causava errore di parsing PHP
        $mail->Body = "
<div style='background-color:#FBF3E4;padding:20px;font-family:Helvetica,Arial,sans-serif;'>
<table align='center' border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width:600px;background:#FFFFFF;border-radius:15px;'>
  <tr><td align='center' style='padding:30px;'>
    <h1 style='color:#668073;margin:0 0 15px;font-size:24px;'>Ciao $nome_cliente!</h1>
    <p style='color:#555;font-size:16px;line-height:1.6;margin:0 0 25px;'>
      Grazie per esserti affidata a me.<br>Mi piacerebbe sapere come ti sei trovata.
    </p>
    <table border='0' cellspacing='0' cellpadding='0'>
      <tr><td align='center' bgcolor='#668073' style='border-radius:50px;'>
        <a href='$link_recensione' target='_blank' style='font-size:16px;color:#ffffff;text-decoration:none;padding:15px 30px;border-radius:50px;display:inline-block;font-weight:bold;white-space:nowrap;'>
          ⭐ Lascia una Recensione
        </a>
      </td></tr>
    </table>
    <p style='color:#888;font-size:14px;margin-top:30px;'>
      Ci vuole meno di un minuto.<br>A presto,<br><strong>Dott.ssa Martina Violo</strong>
    </p>
  </td></tr>
</table>
</div>";
        $mail->send();
        $messaggio = "<div style='color:green;background:#e8f5e9;padding:10px;border-radius:5px;margin-bottom:20px;'>✅ Invito inviato a $nome_cliente!</div>";
    } catch (Exception $e) {
        $messaggio = "<div style='color:red;background:#ffebee;padding:10px;border-radius:5px;margin-bottom:20px;'>❌ Errore: {$mail->ErrorInfo}</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invia Richiesta Recensione</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family:'Montserrat',sans-serif; background:#F0F2F5; padding:20px; }
        .container { max-width:500px; margin:0 auto; background:white; padding:30px; border-radius:10px; box-shadow:0 5px 15px rgba(0,0,0,0.1); }
        h2 { color:#668073; text-align:center; margin-bottom:10px; }
        p.subtitle { text-align:center; color:#666; font-size:0.9rem; margin-bottom:30px; }
        label { display:block; margin-top:15px; font-weight:600; }
        input { width:100%; padding:12px; margin:8px 0; border:1px solid #ccc; border-radius:5px; box-sizing:border-box; font-size:16px; }
        .btn { background:#668073; color:white; padding:12px; border:none; width:100%; cursor:pointer; font-weight:bold; border-radius:5px; font-size:1rem; margin-top:20px; }
        .btn:hover { background:#556b60; }
    </style>
</head>
<body>
    <?php include 'admin_topbar.php'; ?>
    <div class="container">
        <h2>Richiedi Recensione</h2>
        <p class="subtitle">Invia un invito via email a un paziente recente.</p>
        <?php echo $messaggio; ?>
        <form method="POST">
            <label>Nome Paziente</label>
            <input type="text" name="nome" required placeholder="Es. Giulia">
            <label>Email Paziente</label>
            <input type="email" name="email" required placeholder="email@cliente.com">
            <button type="submit" class="btn">Invia Invito</button>
        </form>
    </div>
</body>
</html>
