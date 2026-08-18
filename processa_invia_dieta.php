<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header("Location: login.php");
    exit;
}

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'config_mail.php';

$messaggio_esito = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. GESTIONE DESTINATARIO (Codice invariato)
    $nome_dest = "";
    $email_dest = "";

    if ($_POST['modalita'] == 'elenco') {
        if (!empty($_POST['email_elenco'])) {
            $dati = explode('|', $_POST['email_elenco']);
            $email_dest = $dati[0];
            $nome_dest = $dati[1];
        }
    } else {
        $nome_dest = htmlspecialchars($_POST['nome_manuale']);
        $email_dest = filter_var($_POST['email_manuale'], FILTER_SANITIZE_EMAIL);
    }

    if (empty($email_dest) || empty($nome_dest)) {
        die("Errore: Dati destinatario mancanti.");
    }

    // 2. CONTROLLO OBBLIGATORIO FILE (Server Side)
    
    // CASO A: Nessun file inviato o errore di caricamento vuoto
    if (!isset($_FILES['file_dieta']) || $_FILES['file_dieta']['error'] == UPLOAD_ERR_NO_FILE) {
        die("<div style='text-align:center; padding:50px; color:red; font-family:sans-serif;'>
                <h1>⛔ ALT! Manca l'allegato.</h1>
                <p>È obbligatorio inserire la dieta in PDF prima di inviare.</p>
                <a href='admin_invia_dieta.php' style='background:#ccc; padding:10px; text-decoration:none; color:black; border-radius:5px;'>Torna Indietro</a>
             </div>");
    }

    // CASO B: Altri errori di caricamento
    if ($_FILES['file_dieta']['error'] !== UPLOAD_ERR_OK) {
        die("Errore durante il caricamento del file. Codice errore: " . $_FILES['file_dieta']['error']);
    }

    $file_tmp = $_FILES['file_dieta']['tmp_name'];
    $file_name = $_FILES['file_dieta']['name'];
    
    // CASO C: Controllo Estensione (.pdf)
    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    if ($ext != "pdf") {
        die("<div style='text-align:center; padding:50px; color:red; font-family:sans-serif;'>
                <h1>🚫 Formato Errato</h1>
                <p>Hai caricato un file <strong>.$ext</strong>.</p>
                <p>Devi caricare un file <strong>.pdf</strong>.</p>
                <a href='admin_invia_dieta.php'>Riprova</a>
             </div>");
    }

    // CASO D: Controllo Sicurezza MIME Type (Anti-furbetti)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file_tmp);
    finfo_close($finfo);

    if ($mime != 'application/pdf') {
        die("<div style='text-align:center; padding:50px; color:red; font-family:sans-serif;'>
                <h1>🚫 File non valido</h1>
                <p>Il file sembra un PDF ma non lo è (MIME type errato).</p>
                <a href='admin_invia_dieta.php'>Riprova</a>
             </div>");
    }

    // Messaggio Admin
    $messaggio_admin = nl2br(htmlspecialchars($_POST['messaggio']));
    if (empty($messaggio_admin)) $messaggio_admin = "In allegato trovi il tuo nuovo piano nutrizionale.";

    // 3. INVIO EMAIL
    $mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;      // Prende dal config
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USER;      // Prende dal config
    $mail->Password   = MAIL_PASS;      // Prende dal config
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = MAIL_PORT;      // Prende dal config
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom(MAIL_USER, MAIL_FROM_NAME);
        $mail->addAddress($email_dest, $nome_dest);

        // ALLEGATO
        $mail->addAttachment($file_tmp, $file_name);

        $mail->isHTML(true);
        $mail->Subject = "Il tuo Piano Nutrizionale - Dott.ssa Violo";
        
       $mail->Body = "
        <div style='background-color: #FBF3E4; padding: 20px; font-family: Helvetica, Arial, sans-serif;'>
            <table align='center' border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 600px; background: #FFFFFF; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);'>
                <tr>
                    <td align='center' style='padding: 30px 20px;'>
                        
                        <h2 style='color: #668073; margin: 0 0 10px 0; font-size: 24px;'>Ciao $nome_dest!</h2>
                        <p style='color: #888; margin: 0 0 25px 0; font-size: 16px;'>Ecco il tuo piano nutrizionale.</p>
                        
                        <div style='background: #f9f9f9; padding: 20px; border-left: 4px solid #668073; color: #555; line-height: 1.6; text-align: left; border-radius: 4px; font-size: 16px;'>
                            $messaggio_admin
                        </div>

                        <div style='margin-top: 30px;'>
                            <p style='font-weight: bold; color: #1A2621; font-size: 16px; margin-bottom: 5px;'>📎 Allegato presente</p>
                            <p style='color: #666; font-size: 14px; margin-top: 0;'>Trovi la tua dieta in formato PDF allegata a questa email.</p>
                        </div>

                        <hr style='border: 0; border-top: 1px solid #eee; margin: 30px 0;'>
                        
                        <p style='color: #aaa; font-size: 14px; margin: 0;'>
                            Dott.ssa Martina Violo
                        </p>
                    
                    </td>
                </tr>
            </table>
        </div>";

        $mail->send();
        
        $messaggio_esito = "
        <div style='text-align:center; padding: 50px; font-family: sans-serif;'>
            <h1 style='color:green;'>Dieta Inviata con Successo! ✅</h1>
            <p>Email inviata a <strong>$nome_dest</strong></p>
            <br>
            <a href='admin_invia_dieta.php' style='background:#668073; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Invia un'altra</a>
            <br><br>
            <a href='dashboard.php' style='color:#666;'>Torna alla Dashboard</a>
        </div>";

    } catch (Exception $e) {
        $messaggio_esito = "Errore invio email: {$mail->ErrorInfo}";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Esito Invio</title>
</head>
<body style="background:#F0F2F5; margin:0;">
    <?php echo $messaggio_esito; ?>
</body>
</html>