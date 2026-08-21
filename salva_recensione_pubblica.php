<?php
// FILE: salva_recensione_pubblica.php
// Riceve la recensione pubblica dal form lascia_recensione.html,
// la salva come non approvata e notifica la dottoressa via email con token HMAC sicuri.

require_once 'security.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require_once 'config_mail.php';
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // 1. Controllo Honeypot Anti-Spam
    if (!empty($_POST['website_hp'])) {
        die("Richiesta non valida.");
    }

    $nome  = trim($_POST['nome'] ?? '');
    $testo = trim($_POST['testo'] ?? '');
    $voto  = isset($_POST['voto']) ? (int)$_POST['voto'] : 0;

    // Validazione lato server
    if ($nome === '' || strlen($nome) > 150 || $testo === '' || strlen($testo) > 5000 || $voto < 1 || $voto > 5) {
        die("Dati non validi. Torna indietro e riprova.");
    }

    $stmt = $conn->prepare(
        "INSERT INTO recensioni (nome, testo, voto, fonte, approvata) VALUES (?, ?, ?, 'sito', 0)"
    );
    $stmt->bind_param("ssi", $nome, $testo, $voto);

    if ($stmt->execute()) {
        $id_recensione = $conn->insert_id;
        $stmt->close();

        // Token HMAC per link sicuri 1-click nell'email alla dottoressa
        $token_approva = generate_action_token('approve_review', $id_recensione);
        $token_elimina = generate_action_token('delete_review', $id_recensione);

        $link_approva = BASE_URL . "/azione_recensione.php?id=$id_recensione&action=approve&token=$token_approva";
        $link_elimina = BASE_URL . "/azione_recensione.php?id=$id_recensione&action=delete&token=$token_elimina";

        $nome_safe = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
        $testo_safe = nl2br(htmlspecialchars($testo, ENT_QUOTES, 'UTF-8'));

        // Email di notifica alla dottoressa
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
            $mail->Subject = "Nuova Recensione da APPROVARE: $nome_safe";
            $mail->Body = "
            <div style='font-family:Arial,sans-serif; padding:20px; background:#FBF3E4;'>
              <div style='max-width:600px; margin:0 auto; background:white; padding:25px; border-radius:10px;'>
                <h3 style='color:#668073;'>Nuova Recensione Ricevuta</h3>
                <p><strong>Autore:</strong> $nome_safe</p>
                <p><strong>Voto:</strong> $voto Stelle</p>
                <p><strong>Testo:</strong><br><em>$testo_safe</em></p>
                <hr style='border:0; border-top:1px solid #eee; margin:20px 0;'>
                <p>Cosa vuoi fare?</p>
                <p>
                  <a href='$link_approva' style='background:#27ae60; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>✅ Pubblica sul sito</a>
                  &nbsp;&nbsp;
                  <a href='$link_elimina' style='background:#c0392b; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>❌ Cancella</a>
                </p>
              </div>
            </div>";

            $mail->send();
        } catch (Exception $e) {
            // Errore silenzioso
        }

        echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>
                <h1 style='color:#668073;'>Grazie!</h1>
                <p>La tua recensione è stata inviata ed è in fase di approvazione.</p>
                <a href='index.html' style='color:#668073; font-weight:bold;'>Torna al sito</a>
              </div>";
    } else {
        echo "Errore salvataggio: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8');
    }
}

$conn->close();
?>
