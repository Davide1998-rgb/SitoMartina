<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) { header("Location: login.php"); exit; }
require 'PHPMailer/Exception.php'; require 'PHPMailer/PHPMailer.php'; require 'PHPMailer/SMTP.php';
require_once 'config_mail.php';

$report = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['pacchetto_dati'])) {
    $lista_destinatari = json_decode(base64_decode($_POST['pacchetto_dati']), true);
    // FIX: usa BASE_URL da config_mail.php
    $link_recensione = BASE_URL . "/lascia_recensione.html";

    if (is_array($lista_destinatari)) {
        foreach ($lista_destinatari as $persona) {
            $nome = $persona['nome']; $email = $persona['email'];
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP(); $mail->Host = MAIL_HOST; $mail->SMTPAuth = true;
                $mail->Username = MAIL_USER; $mail->Password = MAIL_PASS;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = MAIL_PORT; $mail->CharSet = 'UTF-8';
                // FIX: usa MAIL_FROM_NAME coerentemente (prima usava $mail->Username come nome)
                $mail->setFrom(MAIL_USER, MAIL_FROM_NAME);
                $mail->addAddress($email, $nome);
                $mail->isHTML(true);
                $mail->Subject = "Com'è andato il controllo? Raccontamelo!";
                $mail->Body = "<!DOCTYPE html><html lang='it'><head><meta charset='UTF-8'>
<meta name='color-scheme' content='light only'>
<style>:root{color-scheme:light;}body,html{background-color:#FBF3E4!important;margin:0;padding:0;}</style>
</head>
<body style='background-color:#FBF3E4;margin:0;padding:0;'>
<div style='background-color:#FBF3E4;padding:20px;font-family:Helvetica,Arial,sans-serif;'>
<table align='center' border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width:600px;background:#FFFFFF;border-radius:15px;overflow:hidden;'>
  <tr><td align='center' style='background-color:#668073;padding:20px;'>
    <h2 style='color:#FFFFFF;margin:0;font-family:Georgia,serif;'>Grazie per la visita!</h2>
  </td></tr>
  <tr><td align='center' style='padding:30px 20px;'>
    <h3 style='color:#668073;margin:0 0 15px;'>Ciao $nome,</h3>
    <p style='color:#555;font-size:16px;line-height:1.5;margin-bottom:20px;'>Mi piacerebbe sapere come ti stai trovando nel tuo percorso.</p>
    <table border='0' cellspacing='0' cellpadding='0'>
      <tr><td align='center' style='border-radius:50px;' bgcolor='#668073'>
        <a href='$link_recensione' target='_blank' style='font-size:16px;color:#ffffff;text-decoration:none;padding:15px 30px;border-radius:50px;display:inline-block;font-weight:bold;'>
          ⭐ Lascia una Recensione
        </a>
      </td></tr>
    </table>
    <p style='font-size:12px;color:#999;margin-top:20px;'>A presto,<br><strong>Dott.ssa Martina Violo</strong></p>
  </td></tr>
</table>
</div></body></html>";
                $mail->send();
                $report[] = "<li style='color:green'>✅ Inviata a <strong>$nome</strong> ($email)</li>";
            } catch (Exception $e) {
                $report[] = "<li style='color:red'>❌ Errore $nome: {$mail->ErrorInfo}</li>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it"><head><meta charset="UTF-8"><title>Esito Invio</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
<style>
body{font-family:'Montserrat',sans-serif;background:#F0F2F5;padding:20px;display:flex;justify-content:center;align-items:center;min-height:100vh;}
.box{background:white;padding:40px;border-radius:15px;width:100%;max-width:600px;box-shadow:0 5px 15px rgba(0,0,0,0.1);}
ul{list-style:none;padding:0;} li{padding:10px;border-bottom:1px solid #eee;}
.btn{display:inline-block;background:#668073;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-top:20px;font-weight:bold;}
</style></head>
<body><div class="box">
    <h2 style="color:#668073;">Rapporto Invio</h2>
    <?php if (!empty($report)): ?><ul><?php foreach ($report as $r) echo $r; ?></ul>
    <?php else: ?><p>Nessuna email inviata.</p><?php endif; ?>
    <a href="admin_recensioni_hub.php" class="btn">Torna all'Hub</a>
    <a href="dashboard.php" class="btn" style="background:#ddd;color:#333;margin-left:10px;">Dashboard</a>
</div></body></html>
