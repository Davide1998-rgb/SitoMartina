<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require_once 'config_mail.php';
require_once 'db_connect.php';
date_default_timezone_set('Europe/Rome');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['privacy_consent'])) { die("Devi accettare la privacy."); }

    $nome        = trim($_POST['nome'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $telefono    = trim($_POST['telefono'] ?? '');
    $servizio    = $_POST['servizio'] ?? '';
    $data_giorno = $_POST['data'] ?? '';
    $ora_inizio  = $_POST['ora'] ?? '';
    $data_inizio = DateTime::createFromFormat('!Y-m-d H:i', "$data_giorno $ora_inizio");
    $errori_data = DateTime::getLastErrors();

    $orario_valido = $data_inizio &&
        ($errori_data === false || ($errori_data['warning_count'] === 0 && $errori_data['error_count'] === 0));
    if (
        $nome === '' || strlen($nome) > 150 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $telefono === '' || strlen($telefono) > 40 ||
        !in_array($servizio, ['prima_visita', 'controllo'], true) ||
        !$orario_valido
    ) {
        die("Dati prenotazione non validi. Torna indietro e riprova.");
    }

    $durata = ($servizio === 'prima_visita') ? 60 : 30;
    $data_fine = (clone $data_inizio)->modify("+$durata minutes");
    $giorno_settimana = (int)$data_inizio->format('N');
    $ora_decimale = (int)$data_inizio->format('H') + ((int)$data_inizio->format('i') / 60);
    $fine_decimale = $ora_decimale + ($durata / 60);
    if (
        $data_inizio < new DateTime('now') ||
        $giorno_settimana === 7 ||
        !(($ora_decimale >= 9 && $fine_decimale <= 13) || ($ora_decimale >= 14 && $fine_decimale <= 18)) ||
        ((int)$data_inizio->format('i') % 30 !== 0)
    ) {
        die("Giorno o orario non disponibile. Torna indietro e scegli un altro slot.");
    }

    $data_it = $data_inizio->format('d/m/Y');
    $data_inizio_db = $data_inizio->format('Y-m-d H:i:s');
    $data_fine_db   = $data_fine->format('Y-m-d H:i:s');

    // Controllo sovrapposizioni
    $stmt_check = $conn->prepare(
        "SELECT id FROM prenotazioni WHERE data_inizio < ? AND data_fine > ?
         AND (status = 'confermata' OR status = 'in_attesa')"
    );
    $stmt_check->bind_param("ss", $data_fine_db, $data_inizio_db);
    $stmt_check->execute(); $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        $stmt_check->close();
        echo "<script>alert('Spiacenti, orario appena occupato. Riprova.'); window.location.href='index.html';</script>";
        exit;
    }
    $stmt_check->close();

    // Inserimento
    $stmt_ins = $conn->prepare(
        "INSERT INTO prenotazioni (nome, email, telefono, servizio, data_inizio, data_fine, status)
         VALUES (?, ?, ?, ?, ?, ?, 'in_attesa')"
    );
    $stmt_ins->bind_param("ssssss", $nome, $email, $telefono, $servizio, $data_inizio_db, $data_fine_db);

    if ($stmt_ins->execute()) {
        $id_prenotazione = $conn->insert_id; $stmt_ins->close();
        $tel_clean     = preg_replace('/[^0-9]/', '', $telefono);
        $link_wa       = "https://wa.me/39$tel_clean";
        $link_conferma = BASE_URL . "/conferma.php?id=$id_prenotazione";
        $link_rifiuta  = BASE_URL . "/rifiuta.php?id=$id_prenotazione";

        // EMAIL 1: alla dottoressa — FIX: era $email (paziente), ora è MAIL_USER
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP(); $mail->Host = MAIL_HOST; $mail->SMTPAuth = true;
            $mail->Username = MAIL_USER; $mail->Password = MAIL_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = MAIL_PORT; $mail->CharSet = 'UTF-8';
            $mail->setFrom(MAIL_USER, MAIL_FROM_NAME);
            $mail->addAddress(MAIL_USER); // ← alla dottoressa
            $mail->isHTML(true);
            $mail->Subject = "🔔 Nuova Prenotazione: $nome";
            $mail->Body = "<div style='background:#FBF3E4;padding:20px;font-family:Arial,sans-serif;'>
<div style='background:#FFF;padding:20px;border-radius:10px;border-left:5px solid #668073;'>
<h2 style='color:#668073;'>Nuova Richiesta</h2>
<p><strong>Paziente:</strong> $nome</p>
<p><strong>Servizio:</strong> " . ucfirst(str_replace('_', ' ', $servizio)) . "</p>
<p><strong>Data:</strong> $data_it alle $ora_inizio</p>
<p><strong>Tel:</strong> $telefono</p>
<hr style='border:0;border-top:1px solid #eee;margin:20px 0;'>
<p>
  <a href='$link_conferma' style='color:#2e7d32;font-weight:bold;text-decoration:none;'>✅ CONFERMA</a> |
  <a href='$link_rifiuta'  style='color:#c62828;font-weight:bold;text-decoration:none;'>❌ RIFIUTA</a> |
  <a href='$link_wa'       style='color:#25D366;font-weight:bold;text-decoration:none;'>💬 WHATSAPP</a>
</p>
</div></div>";
            $mail->send(); $mail->smtpClose();
        } catch (Exception $e) { /* silenzioso */ }

        // EMAIL 2: al paziente
        $mail2 = new PHPMailer(true);
        try {
            $mail2->isSMTP(); $mail2->Host = MAIL_HOST; $mail2->SMTPAuth = true;
            $mail2->Username = MAIL_USER; $mail2->Password = MAIL_PASS;
            $mail2->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail2->Port = MAIL_PORT; $mail2->CharSet = 'UTF-8';
            $mail2->setFrom(MAIL_USER, MAIL_FROM_NAME);
            $mail2->addAddress($email);
            $mail2->isHTML(true);
            $mail2->Subject = "Richiesta ricevuta - Dott.ssa Violo";
            $mail2->Body = "<div style='background:#FBF3E4;padding:40px;font-family:Arial,sans-serif;'>
<div style='max-width:600px;margin:0 auto;background:#FFFFFF;border-radius:15px;padding:30px;'>
<h1 style='color:#668073;'>Dott.ssa Martina Violo</h1>
<h2 style='color:#1A2621;'>Grazie, $nome.</h2>
<p style='color:#555;'>Ho ricevuto la tua richiesta.</p>
<div style='background:#F8F9FA;border-left:4px solid #668073;padding:15px;margin:20px 0;'>
  <p><strong>📅 Data:</strong> $data_it</p>
  <p><strong>🕒 Ora:</strong> $ora_inizio</p>
  <p><strong>📋 Stato:</strong> <span style='color:#E67E22;font-weight:bold;'>In attesa di conferma</span></p>
</div>
<p style='color:#555;'>Riceverai presto una conferma definitiva.</p>
</div></div>";
            $mail2->send();
        } catch (Exception $e) { /* silenzioso */ }

        header("Location: conferma_richiesta.html"); exit;
    } else {
        echo "Errore Database: " . $conn->error;
    }
}
$conn->close();
?>
