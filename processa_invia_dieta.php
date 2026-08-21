<?php
require_once 'security.php';
require_admin_login();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'config_mail.php';

$messaggio_esito = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_csrf_token();

    // 1. GESTIONE DESTINATARIO
    $nome_dest = "";
    $email_dest = "";
    $modalita_richiesta = $_POST['modalita'] ?? 'elenco';
    $modalita = in_array($modalita_richiesta, ['elenco', 'manuale'], true) ? $modalita_richiesta : 'elenco';

    if ($modalita === 'elenco') {
        if (!empty($_POST['email_elenco'])) {
            $dati = explode('|', $_POST['email_elenco']);
            $email_dest = trim($dati[0] ?? '');
            $nome_dest = trim($dati[1] ?? '');
        }
    } else {
        $nome_dest = trim($_POST['nome_manuale'] ?? '');
        $email_dest = trim($_POST['email_manuale'] ?? '');
    }

    if ($nome_dest === '' || !filter_var($email_dest, FILTER_VALIDATE_EMAIL)) {
        die("<div class='admin-result error'><h1>Dati destinatario non validi</h1><p>Controlla nome ed email e riprova.</p><a href='admin_invia_dieta.php'>Torna indietro</a></div>");
    }

    // 2. CONTROLLO OBBLIGATORIO FILE (Server Side)
    if (!isset($_FILES['file_dieta']) || $_FILES['file_dieta']['error'] == UPLOAD_ERR_NO_FILE) {
        die("<div class='admin-result error'>
            <h1>Allegato mancante</h1>
            <p>È obbligatorio inserire la dieta in PDF prima di inviare.</p>
            <a href='admin_invia_dieta.php'>Torna indietro</a>
             </div>");
    }

    if ($_FILES['file_dieta']['error'] !== UPLOAD_ERR_OK) {
        die("Errore durante il caricamento del file. Codice errore: " . (int)$_FILES['file_dieta']['error']);
    }

    // Limite dimensione max 25MB
    if ($_FILES['file_dieta']['size'] > 25 * 1024 * 1024) {
        die("<div class='admin-result error'><h1>File troppo grande</h1><p>Il file supera il limite massimo consentito di 25MB.</p><a href='admin_invia_dieta.php'>Torna indietro</a></div>");
    }

    $file_tmp = $_FILES['file_dieta']['tmp_name'];
    $file_name = basename($_FILES['file_dieta']['name']);
    
    // Controllo Estensione (.pdf)
    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    if ($ext !== "pdf") {
        $ext_safe = htmlspecialchars($ext, ENT_QUOTES, 'UTF-8');
        die("<div class='admin-result error'>
            <h1>Formato non valido</h1>
                <p>Hai caricato un file <strong>.$ext_safe</strong>.</p>
                <p>Devi caricare un file <strong>.pdf</strong>.</p>
                <a href='admin_invia_dieta.php'>Riprova</a>
             </div>");
    }

    // Controllo MIME Type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file_tmp);
    finfo_close($finfo);

    if ($mime !== 'application/pdf') {
        die("<div class='admin-result error'>
            <h1>File non valido</h1>
                <p>Il file non corrisponde a un formato PDF valido.</p>
                <a href='admin_invia_dieta.php'>Riprova</a>
             </div>");
    }

    // Messaggio Admin
    $messaggio_testo = trim($_POST['messaggio'] ?? '');
    $messaggio_admin = nl2br(htmlspecialchars($messaggio_testo, ENT_QUOTES, 'UTF-8'));
    $blocco_messaggio = $messaggio_admin !== '' ? "
                        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='background:#F8FBF9; border-left:4px solid #668073; border-radius:10px;'>
                            <tr>
                                <td style='padding:20px; color:#4A4A4A; font-family:Montserrat, Arial, sans-serif; font-size:15px; line-height:1.7; text-align:left;'>
                                    $messaggio_admin
                                </td>
                            </tr>
                        </table>" : '';
    $nome_dest_html = htmlspecialchars($nome_dest, ENT_QUOTES, 'UTF-8');
    $oggetto = trim($_POST['oggetto'] ?? '');
    $oggetto = preg_replace('/[\r\n]+/', ' ', $oggetto);
    if ($oggetto === '') $oggetto = 'Il tuo Piano Nutrizionale - Dott.ssa Violo';

    // 3. INVIO EMAIL
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
        $mail->addAddress($email_dest, $nome_dest);

        // ALLEGATO
        $mail->addAttachment($file_tmp, $file_name);

        $mail->isHTML(true);
        $mail->Subject = $oggetto;
        
        $mail->Body = "
        <div style='background-color:#FBF3E4; padding:32px 16px; font-family:Montserrat, Arial, sans-serif; color:#4A4A4A;'>
            <table align='center' border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width:620px; background:#FFFFFF; border-radius:18px; border-top:5px solid #668073; box-shadow:0 12px 30px rgba(102,128,115,0.12);'>
                <tr>
                    <td align='center' style='padding:38px 28px 32px;'>
                        <p style='color:#668073; font-family:Montserrat, Arial, sans-serif; font-size:11px; font-weight:bold; letter-spacing:2px; margin:0 0 12px; text-transform:uppercase;'>Dott.ssa Martina Violo</p>
                        <h1 style='color:#1A2621; font-family:Georgia, Times New Roman, serif; font-size:28px; font-weight:bold; line-height:1.2; margin:0 0 10px;'>Ciao $nome_dest_html!</h1>
                        <p style='color:#668073; font-family:Georgia, Times New Roman, serif; font-size:18px; font-style:italic; line-height:1.5; margin:0 0 28px;'>Ecco il tuo piano nutrizionale.</p>

                        $blocco_messaggio

                        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='margin-top:24px; background:#FBF3E4; border-radius:10px;'>
                            <tr>
                                <td style='padding:18px 20px; text-align:left;'>
                                    <p style='color:#1A2621; font-size:15px; font-weight:bold; margin:0 0 5px;'>Allegato presente</p>
                                    <p style='color:#668073; font-size:13px; line-height:1.5; margin:0;'>Trovi la tua dieta in formato PDF allegata a questa email.</p>
                                </td>
                            </tr>
                        </table>

                        <hr style='border:0; border-top:1px solid #E8EEE9; margin:30px 0 20px;'>
                        <p style='color:#668073; font-family:Georgia, Times New Roman, serif; font-size:16px; font-style:italic; margin:0;'>Con cura,<br>Dott.ssa Martina Violo</p>
                    </td>
                </tr>
            </table>
        </div>";

        $mail->send();
        
        $messaggio_esito = "
        <div class='admin-result success'>
            <h1>Dieta inviata con successo</h1>
            <p>Email inviata a <strong>$nome_dest_html</strong></p>
            <a href='admin_invia_dieta.php'>Invia un'altra</a>
            <a href='dashboard.php'>Torna alla Dashboard</a>
        </div>";

    } catch (Exception $e) {
        $messaggio_esito = "<div class='admin-result error'><h1>Errore durante l'invio</h1><p>La mail non è stata inviata. Riprova o controlla la configurazione SMTP.</p><a href='admin_invia_dieta.php'>Torna indietro</a></div>";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="icon" type="image/png" href="img/logo.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Esito Invio</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <?php echo $messaggio_esito; ?>
</body>
</html>