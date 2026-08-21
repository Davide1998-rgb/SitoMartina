<?php
require_once 'security.php';

// Protezione: accessibile solo da CLI oppure via HTTP con token segreto
$isCli = (php_sapi_name() === 'cli');
$token = $_GET['token'] ?? '';
$hasValidToken = hash_equals(CRON_SECRET_KEY, (string)$token);

if (!$isCli && !$hasValidToken) {
    http_response_code(403);
    die("Accesso negato.");
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require_once 'config_mail.php';
require_once 'db_connect.php';

$domani = date('Y-m-d', strtotime('+1 day'));

$stmt = $conn->prepare(
    "SELECT * FROM prenotazioni WHERE DATE(data_inizio) = ? AND status = 'confermata'"
);
$stmt->bind_param("s", $domani);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

$inviate = 0;
$errori = 0;

if ($result && $result->num_rows > 0) {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = MAIL_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = MAIL_USER;
    $mail->Password = MAIL_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = MAIL_PORT;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom(MAIL_USER, MAIL_FROM_NAME);

    while ($row = $result->fetch_assoc()) {
        try {
            $mail->clearAddresses();
            $mail->addAddress($row['email']);
            $ora_app       = date('H:i', strtotime($row['data_inizio']));
            
            // Generazione token HMAC per la conferma cliente
            $token_cliente = generate_action_token('conferma_cliente', $row['id']);
            $link_conferma = BASE_URL . "/conferma_cliente.php?id=" . $row['id'] . "&token=" . $token_cliente;
            $token_annulla = generate_action_token('annulla_cliente', $row['id']);
            $link_annulla = BASE_URL . "/annulla_cliente.php?id=" . $row['id'] . "&token=" . $token_annulla;
            $nome_paziente = htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8');

            $mail->Subject = "Promemoria Appuntamento per Domani - Dott.ssa Violo";
            $mail->isHTML(true);
            $mail->Body = "
<div style='background-color:#FBF3E4;padding:20px;font-family:Helvetica,Arial,sans-serif;'>
<table align='center' border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width:600px;background:#FFFFFF;border-radius:15px;'>
  <tr><td align='center' style='background-color:#668073;padding:20px;border-radius:15px 15px 0 0;'>
    <h2 style='color:#FFFFFF;margin:0;font-family:Georgia,serif;'>Promemoria</h2>
  </td></tr>
  <tr><td align='center' style='padding:30px 20px;'>
    <p style='color:#555;font-size:16px;'>Gentile $nome_paziente,</p>
    <p style='color:#555;font-size:16px;'>Ti ricordo il tuo appuntamento per domani:</p>
    <div style='background:#f9f9f9;padding:20px;margin:25px 0;border-radius:10px;border:1px solid #eee;'>
      <strong style='color:#668073;font-size:20px;display:block;'>Ore $ora_app</strong>
      <span style='color:#777;font-size:14px;'>Presso Studio Dott.ssa Violo</span>
    </div>
    <p style='color:#555;font-size:16px;'>Per favore, conferma la tua presenza:</p>
    <table border='0' cellspacing='0' cellpadding='0'>
      <tr><td align='center' bgcolor='#668073' style='border-radius:50px;'>
        <a href='$link_conferma' target='_blank' style='font-size:16px;color:#ffffff;text-decoration:none;padding:15px 30px;border-radius:50px;border:1px solid #668073;display:inline-block;font-weight:bold;white-space:nowrap;'>
          👍 SÌ, CI SARÒ
        </a>
      </td></tr>
    </table>
    <p style='margin-top:30px;font-size:13px;color:#999;'>Se non puoi venire, avvisa su WhatsApp il prima possibile.</p>
    <p style='font-size:13px;'><a href='$link_annulla' style='color:#b23b3b;'>Annulla appuntamento</a></p>
  </td></tr>
</table>
</div>";
            $mail->send();
            $inviate++;
        } catch (Exception $e) {
            $errori++;
        }
    }
}

$conn->close();

if ($isCli) {
    echo "Promemoria inviati: $inviate, Errori: $errori\n";
} else {
    echo "Esecuzione completata. Promemoria inviati: $inviate, Errori: $errori.";
}
?>
