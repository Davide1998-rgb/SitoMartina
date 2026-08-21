<?php
require_once 'security.php';
require_admin_login();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require_once 'config_mail.php';
require_once 'db_connect.php';

$messaggio = "";

// Gestione Azioni in POST con CSRF
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && isset($_POST['id'])) {
    require_csrf_token();

    $id     = intval($_POST['id']);
    $action = $_POST['action'];

    // Recupera la prenotazione
    $stmt = $conn->prepare("SELECT * FROM prenotazioni WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res  = $stmt->get_result();
    $pren = $res->fetch_assoc();
    $stmt->close();

    if ($pren) {
        $nome_safe = htmlspecialchars($pren['nome'], ENT_QUOTES, 'UTF-8');
        $email_safe = htmlspecialchars($pren['email'], ENT_QUOTES, 'UTF-8');

        if ($action === 'conferma') {
            $stmt_up = $conn->prepare("UPDATE prenotazioni SET status = 'confermata' WHERE id = ?");
            $stmt_up->bind_param("i", $id);

            if ($stmt_up->execute()) {
                $email_cliente = $pren['email'];
                $data_human    = date("d/m/Y", strtotime($pren['data_inizio']));
                $ora_human     = date("H:i",   strtotime($pren['data_inizio']));

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
                      <p style='color:#1A2621;'>Ciao $nome_safe, il tuo appuntamento è stato fissato in agenda.</p>
                      <div style='background:#FBF3E4;border-radius:10px;padding:20px;margin:30px 0;text-align:left;'>
                        <p style='color:#668073;font-size:0.9rem;text-transform:uppercase;font-weight:bold;'>Dettagli Appuntamento</p>
                        <hr style='border:0;border-top:1px solid #dcdcdc;margin:10px 0;'>
                        <p style='color:#1A2621;'><strong>📅 Quando:</strong> $data_human</p>
                        <p style='color:#1A2621;'><strong>🕒 Ora:</strong> $ora_human</p>
                        <p style='color:#1A2621;'><strong>📍 Dove:</strong> Corso della Repubblica, 5 - Cassino</p>
                      </div>
                      <p style='color:#555;font-size:0.95rem;'>Se devi disdire, avvisami con almeno 24h di anticipo.</p>
                      <a href='https://wa.me/393331909733' style='display:inline-block;background:#668073;color:white;text-decoration:none;padding:12px 25px;border-radius:50px;font-weight:bold;margin-top:20px;'>Contattami su WhatsApp</a>
                    </div>
                    </div>";

                    $mail->send();
                    $messaggio = "<div class='alert success'>Appuntamento di <strong>$nome_safe</strong> confermato e mail inviata a $email_safe!</div>";
                } catch (Exception $e) {
                    $messaggio = "<div class='alert warning'>Appuntamento confermato su DB, ma errore mail: " . htmlspecialchars($mail->ErrorInfo, ENT_QUOTES, 'UTF-8') . "</div>";
                }
            }
            $stmt_up->close();

        } elseif ($action === 'annulla') {
            $stmt_up = $conn->prepare("UPDATE prenotazioni SET status = 'annullata' WHERE id = ?");
            $stmt_up->bind_param("i", $id);
            if ($stmt_up->execute()) {
                $messaggio = "<div class='alert error'>Richiesta di <strong>$nome_safe</strong> annullata.</div>";
            }
            $stmt_up->close();
        }
    }
}

// Estrazione richieste filtrabili con prepared statement
$filtro = trim((string)($_GET['q'] ?? ''));
$data_filtro = trim((string)($_GET['data'] ?? ''));
$query_list = "SELECT * FROM prenotazioni WHERE status = 'in_attesa'";
$tipi = '';
$parametri = [];
if ($filtro !== '') {
    $query_list .= " AND (nome LIKE ? OR email LIKE ? OR telefono LIKE ?)";
    $valore_filtro = '%' . $filtro . '%';
    $tipi .= 'sss';
    $parametri[] = $valore_filtro;
    $parametri[] = $valore_filtro;
    $parametri[] = $valore_filtro;
}
if ($data_filtro !== '') {
    $query_list .= " AND DATE(data_inizio) = ?";
    $tipi .= 's';
    $parametri[] = $data_filtro;
}
$query_list .= " ORDER BY data_inizio ASC";
$stmt_list = $conn->prepare($query_list);
if ($filtro !== '' && $data_filtro !== '') {
    $stmt_list->bind_param('ssss', $valore_filtro, $valore_filtro, $valore_filtro, $data_filtro);
} elseif ($filtro !== '') {
    $stmt_list->bind_param('sss', $valore_filtro, $valore_filtro, $valore_filtro);
} elseif ($data_filtro !== '') {
    $stmt_list->bind_param('s', $data_filtro);
}
$stmt_list->execute();
$result = $stmt_list->get_result();
$stmt_list->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="icon" type="image/png" href="img/logo.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
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
        .request-filters { display:flex; align-items:end; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem; padding:1rem; background:#fff; border-radius:1rem; box-shadow:0 5px 15px rgba(0,0,0,0.04); }
        .request-filters label { display:flex; flex-direction:column; gap:0.35rem; color:#668073; font-size:0.78rem; font-weight:600; }
        .request-filters input { min-width:12rem; padding:0.7rem; border:1px solid #DDE5E1; border-radius:0.6rem; font:inherit; }
        .request-filters .btn-act { min-height:42px; }
        .table-scroll { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #eee; }
        th { color: #668073; font-weight: 600; font-size:0.9rem; text-transform: uppercase; }
        
        .btn-act {
            display: inline-flex; align-items: center; justify-content: center; gap: 4px;
            padding: 0.5rem 0.9rem; border-radius: 20px; text-decoration: none; font-size: 0.85rem; font-weight: 600; color: white; transition: 0.2s;
            border: none; cursor: pointer; font-family: inherit;
        }
        .btn-confirm { background-color: #28a745; }
        .btn-confirm:hover { background-color: #218838; }
        .btn-cancel { background-color: #dc3545; }
        .btn-cancel:hover { background-color: #c82333; }

        .actions-cell {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            min-width: 9.5rem;
        }
        .actions-cell form { width: 100%; margin: 0; }
        .actions-cell .btn-act { width: 100%; }

        @media (max-width: 600px) {
            .admin-header {
                flex-direction: column;
                align-items: stretch;
                gap: 0.75rem;
                padding: 1rem;
                text-align: center;
            }
            .admin-header h1 { font-size: 1.25rem; }
            .back-btn { display: inline-flex; justify-content: center; align-items: center; }
            .container { margin: 1.5rem auto; padding: 0 0.75rem; }
            .table-card { padding: 1rem; }
            .request-filters { align-items:stretch; flex-direction:column; }
            .request-filters input, .request-filters .btn-act { width:100%; }
            table { min-width: 860px; }
            th, td { padding: 0.75rem; }
        }
    </style>
    <link rel="stylesheet" href="admin.css">
</head>
<body>

    <div class="admin-header">
        <h1>Richieste da Confermare</h1>
        <a href="dashboard.php" class="back-btn"><i class='bx bx-arrow-back'></i> Torna alla Dashboard</a>
    </div>

    <div class="container">
        <?= $messaggio; ?>

        <form method="GET" class="request-filters" aria-label="Filtra richieste">
            <label>
                Cerca cliente
                <input type="search" name="q" value="<?= htmlspecialchars($filtro, ENT_QUOTES, 'UTF-8') ?>" placeholder="Nome, email o telefono">
            </label>
            <label>
                Data
                <input type="date" name="data" value="<?= htmlspecialchars($data_filtro, ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <button type="submit" class="btn-act btn-confirm"><i class='bx bx-filter-alt'></i> Filtra</button>
            <a href="admin_richieste.php" class="btn-act btn-no-mail">Reset</a>
        </form>

        <div class="table-card">
            <div class="table-scroll">
            <?php if ($result && $result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Azione</th>
                            <th>Data & Ora</th>
                            <th>Servizio</th>
                            <th>Paziente</th>
                            <th>Email / Tel</th>
                            <th>WhatsApp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php
                                $numero_whatsapp = preg_replace('/\D+/', '', (string)$row['telefono']);
                                if ($numero_whatsapp !== '' && substr($numero_whatsapp, 0, 2) !== '39') {
                                    $numero_whatsapp = '39' . $numero_whatsapp;
                                }
                                $servizio_label = $row['servizio'] === 'prima_visita' ? 'Prima visita' : 'Controllo';
                            ?>
                            <tr>
                                <td>
                                    <div class="actions-cell">
                                        <form method="POST" onsubmit="return confirm('Confermare la prenotazione e inviare la mail al cliente?');">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                            <input type="hidden" name="action" value="conferma">
                                            <button type="submit" class="btn-act btn-confirm">
                                                <i class='bx bx-check'></i> Conferma
                                            </button>
                                        </form>
                                        <form method="POST" onsubmit="return confirm('Annullare la richiesta?');">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                            <input type="hidden" name="action" value="annulla">
                                            <button type="submit" class="btn-act btn-cancel">
                                                <i class='bx bx-x'></i> Annulla
                                            </button>
                                        </form>
                                    </div>
                                </td>
                                <td>
                                    <strong><?= date("d/m/Y", strtotime($row['data_inizio'])) ?></strong><br>
                                    <small style="color:#888;"><?= date("H:i", strtotime($row['data_inizio'])) ?> - <?= date("H:i", strtotime($row['data_fine'])) ?></small>
                                </td>
                                <td><?= htmlspecialchars($servizio_label, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><strong><?= htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                <td>
                                    <?= htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') ?><br>
                                    <small style="color:#888;"><?= htmlspecialchars($row['telefono'], ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td>
                                    <?php if ($numero_whatsapp !== ''): ?>
                                        <a href="https://wa.me/<?= htmlspecialchars($numero_whatsapp, ENT_QUOTES, 'UTF-8') ?>" class="btn-act btn-whatsapp whatsapp-business-link" data-phone="<?= htmlspecialchars($numero_whatsapp, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                                            <i class='bx bxl-whatsapp'></i> Chat
                                        </a>
                                    <?php else: ?>
                                        <span style="color:#999;">Nessun numero</span>
                                    <?php endif; ?>
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
    </div>

    <script>
        document.querySelectorAll('.whatsapp-business-link').forEach(function(link) {
            link.addEventListener('click', function(event) {
                if (!/iPhone|iPad|iPod|Android/i.test(navigator.userAgent)) return;

                event.preventDefault();
                var fallbackUrl = link.href;
                var businessUrl = 'whatsapp-business://send?phone=' + link.dataset.phone;
                var appOpened = false;

                document.addEventListener('visibilitychange', function() {
                    if (document.visibilityState === 'hidden') appOpened = true;
                }, { once: true });

                window.location.href = businessUrl;
                setTimeout(function() {
                    if (!appOpened) window.location.href = fallbackUrl;
                }, 1200);
            });
        });
    </script>
</body>
</html>