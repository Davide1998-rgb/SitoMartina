<?php
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
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

$messaggio = "";

// Gestione Azioni in 1-Click
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id     = intval($_GET['id']);
    $action = $_GET['action'];

    // Recupera la prenotazione
    $stmt = $conn->prepare("SELECT * FROM prenotazioni WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res  = $stmt->get_result();
    $pren = $res->fetch_assoc();
    $stmt->close();

    if ($pren) {
        if ($action === 'conferma') {
            // Aggiorna lo stato nel DB
            $stmt_up = $conn->prepare("UPDATE prenotazioni SET status = 'confermata' WHERE id = ?");
            $stmt_up->bind_param("i", $id);

            if ($stmt_up->execute()) {
                $nome          = $pren['nome'];
                $email_cliente = $pren['email'];
                $data_human    = date("d/m/Y", strtotime($pren['data_inizio']));
                $ora_human     = date("H:i",   strtotime($pren['data_inizio']));

                // Invio email tramite PHPMailer
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
                    $mail->Body    = "
                    <div style='background-color:#FBF3E4;padding:40px;font-family:Helvetica,Arial,sans-serif;'>
                    <div style='max-width:600px;margin:0 auto;background:#FFFFFF;border-radius:15px;padding:30px;text-align:center;'>
                      <div style='background-color:#e8f5e9;color:#668073;width:60px;height:60px;line-height:60px;border-radius:50%;display:inline-block;font-size:30px;'>✔</div>
                      <h1 style='color:#668073;font-family:Georgia,serif;'>Confermato!</h1>
                      <p style='color:#1A2621;'>Ciao $nome, il tuo appuntamento è stato fissato in agenda.</p>
                      <div style='background:#FBF3E4;border-radius:10px;padding:20px;margin:30px 0;text-align:left;'>
                        <p style='color:#668073;font-size:0.9rem;text-transform:uppercase;font-weight:bold;'>Dettagli Appuntamento</p>
                        <hr style='border:0;border-top:1px solid #dcdcdc;margin:10px 0;'>
                        <p style='color:#1A2621;'><strong>📅 Quando:</strong> $data_human</p>
                        <p style='color:#1A2621;'><strong>🕒 Ora:</strong> $ora_human</p>
                        <p style='color:#1A2621;'><strong>📍 Dove:</strong> Piazza Enrico Risi 23, Sant'Elia</p>
                      </div>
                      <p style='color:#555;font-size:0.95rem;'>Se devi disdire, avvisami con almeno 24h di anticipo.</p>
                      <a href='https://wa.me/393472796818' style='display:inline-block;background:#668073;color:white;text-decoration:none;padding:12px 25px;border-radius:50px;font-weight:bold;margin-top:20px;'>Contattami su WhatsApp</a>
                    </div>
                    </div>";

                    $mail->send();
                    $messaggio = "<div class='alert success'>Appuntamento di <strong>$nome</strong> confermato e mail inviata a $email_cliente!</div>";
                } catch (Exception $e) {
                    $messaggio = "<div class='alert warning'>Appuntamento confermato su DB, ma errore mail: {$mail->ErrorInfo}</div>";
                }
            }
            $stmt_up->close();

        } elseif ($action === 'annulla') {
            $stmt_up = $conn->prepare("UPDATE prenotazioni SET status = 'annullata' WHERE id = ?");
            $stmt_up->bind_param("i", $id);
            if ($stmt_up->execute()) {
                $messaggio = "<div class='alert error'>Richiesta di <strong>{$pren['nome']}</strong> annullata.</div>";
            }
            $stmt_up->close();
        }
    }
}

// Estrazione appuntamenti in attesa
$result = $conn->query("SELECT * FROM prenotazioni WHERE status = 'in_attesa' ORDER BY data_inizio ASC");
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Richieste in Attesa - Dott.ssa Martina Violo</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #FBF3E4;
            background-image: url("https://www.transparenttextures.com/patterns/cream-paper.png");
            background-attachment: fixed;
            margin: 0; padding: 0; color: #4a4a4a;
        }
        .admin-header {
            background-color: #668073; color: white; padding: 1.5rem 2rem;
            display: flex; justify-content: space-between; align-items: center;
        }
        .admin-header h1 { margin: 0; font-family: 'Playfair Display', serif; font-size: 1.5rem; }
        .back-btn { background: rgba(255,255,255,0.2); color: white; text-decoration: none; padding: 0.5rem 1rem; border-radius: 2rem; font-size:0.9rem; }
        .back-btn:hover { background: white; color: #668073; }

        .container { max-width: 950px; margin: 2.5rem auto; padding: 0 1.5rem; }
        .alert { padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; font-weight: 500; }
        .alert.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert.warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }

        .table-card { background: white; border-radius: 1.2rem; padding: 1.5rem; box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #eee; }
        th { color: #668073; font-weight: 600; font-size:0.9rem; text-transform: uppercase; }
        
        .btn-act {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 0.5rem 0.9rem; border-radius: 20px; text-decoration: none; font-size: 0.85rem; font-weight: 600; color: white; transition: 0.2s;
        }
        .btn-confirm { background-color: #28a745; }
        .btn-confirm:hover { background-color: #218838; }
        .btn-cancel { background-color: #dc3545; margin-left: 4px; }
        .btn-cancel:hover { background-color: #c82333; }
    </style>
</head>
<body>

    <div class="admin-header">
        <h1>Richieste da Confermare</h1>
        <a href="dashboard.php" class="back-btn"><i class='bx bx-arrow-back'></i> Torna alla Dashboard</a>
    </div>

    <div class="container">
        <?= $messaggio; ?>

        <div class="table-card">
            <?php if ($result && $result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Paziente</th>
                            <th>Servizio</th>
                            <th>Data & Ora</th>
                            <th>Email / Tel</th>
                            <th>Azione 1-Click</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['nome']) ?></strong></td>
                                <td><?= htmlspecialchars($row['servizio']) ?></td>
                                <td>
                                    <?= date("d/m/Y", strtotime($row['data_inizio'])) ?><br>
                                    <small style="color:#888;"><?= date("H:i", strtotime($row['data_inizio'])) ?> - <?= date("H:i", strtotime($row['data_fine'])) ?></small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($row['email']) ?><br>
                                    <small style="color:#888;"><?= htmlspecialchars($row['telefono']) ?></small>
                                </td>
                                <td>
                                    <a href="admin_richieste.php?action=conferma&id=<?= $row['id'] ?>" class="btn-act btn-confirm" onclick="return confirm('Confermare la prenotazione e inviare la mail al cliente?');">
                                        <i class='bx bx-check'></i> Conferma
                                    </a>
                                    <a href="admin_richieste.php?action=annulla&id=<?= $row['id'] ?>" class="btn-act btn-cancel" onclick="return confirm('Annullare la richiesta?');">
                                        <i class='bx bx-x'></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #888; padding: 2rem 0;">Nessuna richiesta in attesa di conferma al momento.</p>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>