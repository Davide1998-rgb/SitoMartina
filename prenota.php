<?php
require_once 'security.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require_once 'config_mail.php';
require_once 'db_connect.php';
require_once 'disponibilita.php';
date_default_timezone_set('Europe/Rome');

function redirect_booking_error(string $message): void {
    $safeMessage = rawurlencode($message);
    header('Location: index.html?error=' . $safeMessage . '#prenota', true, 302);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 1. Controllo Honeypot Anti-Bot
    if (!empty($_POST['website_hp'])) {
        redirect_booking_error('Richiesta non valida.');
    }

    if (!isset($_POST['privacy_consent'])) {
        redirect_booking_error('Devi accettare la privacy.');
    }

    $nome            = trim($_POST['nome'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $telefono        = trim($_POST['telefono'] ?? '');
    $modalita_visita = $_POST['modalita_visita'] ?? '';
    $servizio        = $_POST['servizio'] ?? '';
    $data_giorno     = $_POST['data'] ?? '';
    $ora_inizio      = $_POST['ora'] ?? '';
    $data_inizio = DateTime::createFromFormat('!Y-m-d H:i', "$data_giorno $ora_inizio");
    $errori_data = DateTime::getLastErrors();

    $orario_valido = $data_inizio &&
        ($errori_data === false || ($errori_data['warning_count'] === 0 && $errori_data['error_count'] === 0));
    if (
        $nome === '' || strlen($nome) > 150 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $telefono === '' || strlen($telefono) > 40 ||
        !in_array($modalita_visita, ['online', 'studio'], true) ||
        !in_array($servizio, ['prima_visita', 'controllo'], true) ||
        !$orario_valido
    ) {
        redirect_booking_error('Dati prenotazione non validi. Torna indietro e riprova.');
    }

    $durata = ($servizio === 'prima_visita') ? 60 : 30;
    $data_fine = (clone $data_inizio)->modify("+$durata minutes");
    $giorno_settimana = (int)$data_inizio->format('N');
    $minuti_inizio = ((int)$data_inizio->format('H') * 60) + (int)$data_inizio->format('i');
    $minuti_fine = ((int)$data_fine->format('H') * 60) + (int)$data_fine->format('i');
    $fine_fascia = match ($giorno_settimana) {
        2 => 20 * 60,
        3, 5 => 20 * 60,
        6 => ($servizio === 'prima_visita') ? 13 * 60 : (13 * 60 + 30),
        default => 0
    };
    $inizio_fascia = ($giorno_settimana === 2) ? 14 * 60 : 9 * 60;
    $fine_fuori_orario = $giorno_settimana === 6 && $minuti_fine > $fine_fascia;
    if (
        $data_inizio->format('Y-m-d') <= (new DateTime('today'))->format('Y-m-d') ||
        !in_array($giorno_settimana, [2, 3, 5, 6], true) ||
        $minuti_inizio < $inizio_fascia || $fine_fuori_orario ||
        ((int)$data_inizio->format('i') % 30 !== 0)
    ) {
        redirect_booking_error('Giorno o orario non disponibile. Torna indietro e scegli un altro slot.');
    }

    if (disponibilita_fascia_bloccata($conn, $data_giorno, $ora_inizio, $data_fine->format('H:i'))) {
        redirect_booking_error('Giorno o orario non disponibile. Torna indietro e scegli un altro slot.');
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
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        $stmt_check->close();
        redirect_booking_error('Spiacenti, l\'orario scelto è appena stato occupato. Riprova.');
    }
    $stmt_check->close();

    // Inserimento
    $stmt_ins = $conn->prepare(
        "INSERT INTO prenotazioni (nome, email, telefono, modalita_visita, servizio, data_inizio, data_fine, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'in_attesa')"
    );
    $stmt_ins->bind_param("sssssss", $nome, $email, $telefono, $modalita_visita, $servizio, $data_inizio_db, $data_fine_db);

    if ($stmt_ins->execute()) {
        $id_prenotazione = $conn->insert_id;
        $stmt_ins->close();

        $tel_clean     = preg_replace('/[^0-9]/', '', $telefono);
        $link_wa       = "https://wa.me/39$tel_clean";
        
        // Generazione link firmati con HMAC per conferma/rifiuto sicuri 1-click
        $token_conferma = generate_action_token('conferma', $id_prenotazione);
        $token_rifiuta  = generate_action_token('rifiuta', $id_prenotazione);
        $link_conferma  = BASE_URL . "/conferma.php?id=$id_prenotazione&token=$token_conferma";
        $link_rifiuta   = BASE_URL . "/rifiuta.php?id=$id_prenotazione&token=$token_rifiuta";

        $nome_safe      = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
        $tel_safe       = htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8');
        $servizio_safe  = htmlspecialchars(ucfirst(str_replace('_', ' ', $servizio)), ENT_QUOTES, 'UTF-8');
        $modalita_label = $modalita_visita === 'online' ? 'Online' : 'In Studio';
        $modalita_safe  = htmlspecialchars($modalita_label, ENT_QUOTES, 'UTF-8');

        // EMAIL 1: alla dottoressa
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
            $mail->addAddress(MAIL_USER);
            $mail->isHTML(true);
            $mail->Subject = "🔔 Nuova Prenotazione: $nome_safe";
            $mail->Body = "<div style='background:#FBF3E4;padding:20px;font-family:Arial,sans-serif;'>
<div style='background:#FFF;padding:20px;border-radius:10px;border-left:5px solid #668073;'>
<h2 style='color:#668073;'>Nuova Richiesta</h2>
<p><strong>Paziente:</strong> $nome_safe</p>
<p><strong>Servizio:</strong> $servizio_safe</p>
<p><strong>Modalita:</strong> $modalita_safe</p>
<p><strong>Data:</strong> $data_it alle $ora_inizio</p>
<p><strong>Tel:</strong> $tel_safe</p>
<hr style='border:0;border-top:1px solid #eee;margin:20px 0;'>
<p>
  <a href='$link_conferma' style='color:#2e7d32;font-weight:bold;text-decoration:none;'>✅ CONFERMA</a> |
  <a href='$link_rifiuta'  style='color:#c62828;font-weight:bold;text-decoration:none;'>❌ RIFIUTA</a> |
  <a href='$link_wa'       style='color:#25D366;font-weight:bold;text-decoration:none;'>💬 WHATSAPP</a>
</p>
</div></div>";
            $mail->send();
            $mail->smtpClose();
        } catch (Exception $e) { /* silenzioso */ }

        // EMAIL 2: al paziente
        $mail2 = new PHPMailer(true);
        try {
            $mail2->isSMTP();
            $mail2->Host       = MAIL_HOST;
            $mail2->SMTPAuth   = true;
            $mail2->Username   = MAIL_USER;
            $mail2->Password   = MAIL_PASS;
            $mail2->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail2->Port       = MAIL_PORT;
            $mail2->CharSet    = 'UTF-8';
            $mail2->setFrom(MAIL_USER, MAIL_FROM_NAME);
            $mail2->addAddress($email);
            $mail2->isHTML(true);
            $mail2->Subject = "Richiesta ricevuta - Dott.ssa Violo";
            $mail2->Body = "<div style='background:#FBF3E4;padding:40px;font-family:Arial,sans-serif;'>
<div style='max-width:600px;margin:0 auto;background:#FFFFFF;border-radius:15px;padding:30px;'>
<h1 style='color:#668073;'>Dott.ssa Martina Violo</h1>
<h2 style='color:#1A2621;'>Grazie, $nome_safe.</h2>
<p style='color:#555;'>Ho ricevuto la tua richiesta.</p>
<div style='background:#F8F9FA;border-left:4px solid #668073;padding:15px;margin:20px 0;'>
  <p><strong>📅 Data:</strong> $data_it</p>
  <p><strong>🕒 Ora:</strong> $ora_inizio</p>
  <p><strong>� Modalita:</strong> $modalita_safe</p>
  <p><strong>�📋 Stato:</strong> <span style='color:#E67E22;font-weight:bold;'>In attesa di conferma</span></p>
</div>
<p style='color:#555;'>Riceverai presto una conferma definitiva.</p>
</div></div>";
            $mail2->send();
        } catch (Exception $e) { /* silenzioso */ }

        header("Location: conferma_richiesta.html?conversione=1");
        exit;
    } else {
        echo "Errore Database: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8');
    }
}
$conn->close();
