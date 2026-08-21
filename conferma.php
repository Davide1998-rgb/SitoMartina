<?php
require_once 'security.php';
start_secure_session();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require_once 'config_mail.php';
require_once 'db_connect.php';

$id    = isset($_GET['id']) ? intval($_GET['id']) : 0;
$token = $_GET['token'] ?? '';

$is_logged = isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true;
$is_valid_token = ($id > 0 && verify_action_token('conferma', $id, $token));

if (!$is_logged && !$is_valid_token) {
    header("Location: login.php");
    exit;
}

if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM prenotazioni WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result && $result->num_rows > 0) {
        $row           = $result->fetch_assoc();
        $nome          = $row['nome'];
        $nome_safe     = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
        $email_cliente = $row['email'];
        $data_human    = date("d/m/Y", strtotime($row['data_inizio']));
        $ora_human     = date("H:i",   strtotime($row['data_inizio']));
        $token_annulla = generate_action_token('annulla_cliente', $id);
        $link_annulla  = BASE_URL . "/annulla_cliente.php?id=$id&token=$token_annulla";

        $stmt2 = $conn->prepare("UPDATE prenotazioni SET status = 'confermata' WHERE id = ?");
        $stmt2->bind_param("i", $id);

        if ($stmt2->execute()) {
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
<div style='background-color:#FBF3E4;padding:40px;font-family:Helvetica,Arial,sans-serif;'>
<div style='max-width:600px;margin:0 auto;background:#FFFFFF;border-radius:15px;padding:30px;text-align:center;'>
  <div style='background-color:#e8f5e9;color:#668073;width:60px;height:60px;line-height:60px;border-radius:50%;display:inline-block;font-size:30px;'>✔</div>
  <h1 style='color:#668073;font-family:Georgia,serif;'>Confermato!</h1>
  <p style='color:#1A2621;'>Il tuo appuntamento è stato fissato in agenda.</p>
  <div style='background:#FBF3E4;border-radius:10px;padding:20px;margin:30px 0;text-align:left;'>
    <p style='color:#668073;font-size:0.9rem;text-transform:uppercase;font-weight:bold;'>Dettagli Appuntamento</p>
    <hr style='border:0;border-top:1px solid #dcdcdc;margin:10px 0;'>
    <p style='color:#1A2621;'><strong>📅 Quando:</strong> $data_human</p>
    <p style='color:#1A2621;'><strong>🕒 Ora:</strong> $ora_human</p>
        <p style='color:#1A2621;'><strong>📍 Dove:</strong> Corso della Repubblica, 5 - Cassino</p>
  </div>
  <p style='color:#555;font-size:0.95rem;'>Se devi disdire, avvisami con almeno 24h di anticipo.</p>
        <a href='$link_annulla' style='display:inline-block;color:#b23b3b;text-decoration:underline;margin-top:10px;'>Annulla appuntamento</a><br>
    <a href='https://wa.me/393331909733' style='display:inline-block;background:#668073;color:white;text-decoration:none;padding:12px 25px;border-radius:50px;font-weight:bold;margin-top:20px;'>Contattami su WhatsApp</a>
</div>
</div>";

                $mail->send();
                echo "<div style='font-family:sans-serif;text-align:center;padding:50px;background:#e8f5e9;'>
                        <h1 style='color:#2e7d32;'>Prenotazione Confermata!</h1>
                        <p>Email inviata a <strong>$nome_safe</strong>.</p>
                        <a href='admin_planner.php' style='color:#668073;font-weight:bold;'>Vai al Planner</a>
                      </div>";
            } catch (Exception $e) {
                echo "<div style='font-family:sans-serif;text-align:center;padding:50px;'>
                        <p>Prenotazione salvata, errore email: " . htmlspecialchars($mail->ErrorInfo, ENT_QUOTES, 'UTF-8') . "</p>
                        <a href='admin_planner.php'>Vai al Planner</a>
                      </div>";
            }
        } else {
            echo "Errore DB: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8');
        }
    } else {
        echo "<div style='font-family:sans-serif;text-align:center;padding:50px;'>Prenotazione non trovata.</div>";
    }
}
$conn->close();
?>
