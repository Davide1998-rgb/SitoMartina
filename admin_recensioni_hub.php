<?php
// FILE: admin_recensioni_hub.php

require_once 'security.php';
require_admin_login();

require_once 'db_connect.php';
require_once 'aggiorna_index_recensioni.php';

// --- LOGICA 1: AZIONI RAPIDE (Approva / Elimina via POST con CSRF) ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && isset($_POST['id'])) {
    require_csrf_token();
    $id_rec = intval($_POST['id']);
    $action = $_POST['action'];

    if (in_array($action, ['approve', 'delete'], true)) {
        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE recensioni SET approvata = 1 WHERE id = ?");
        } else {
            $stmt = $conn->prepare("DELETE FROM recensioni WHERE id = ?");
        }
        $stmt->bind_param("i", $id_rec);
        $stmt->execute();
        $stmt->close();
        aggiornaIndexRecensioni($conn);

        header("Location: admin_recensioni_hub.php");
        exit;
    }
}

// --- LOGICA 2: PREPARAZIONE LISTA INVIO ---
$step       = (isset($_POST['step']) && $_POST['step'] === 'conferma') ? 'conferma' : 'selezione';
$lista_invio = [];

if ($step === 'conferma' && $_SERVER["REQUEST_METHOD"] === "POST") {
    require_csrf_token();

    if (isset($_POST['tipo_invio']) && $_POST['tipo_invio'] === 'manuale') {
        $nome  = trim($_POST['manual_nome'] ?? '');
        $email = trim($_POST['manual_email'] ?? '');
        if ($nome !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $lista_invio[] = ['nome' => $nome, 'email' => $email, 'tipo' => 'Manuale'];
        }

    } elseif (isset($_POST['ids']) && is_array($_POST['ids'])) {
        foreach ($_POST['ids'] as $raw_id) {
            $id  = intval($raw_id);
            $stmt = $conn->prepare("SELECT nome, email FROM prenotazioni WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $r = $res->fetch_assoc();
                $lista_invio[] = ['nome' => $r['nome'], 'email' => $r['email'], 'tipo' => 'Auto (Controllo)'];
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="icon" type="image/png" href="img/logo.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Hub Recensioni</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { box-sizing:border-box; -webkit-tap-highlight-color:transparent; }
        body { font-family:'Montserrat',sans-serif; background:#F0F2F5; margin:0; padding:15px; color:#333; }
        .container { max-width:900px; margin:0 auto; width:100%; }
        h2 { margin-top:0; font-size:1.5rem; }

        /* Griglia opzioni */
        .option-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-top:20px; }
        .option-box  { background:white; padding:25px; border-radius:15px; box-shadow:0 5px 15px rgba(0,0,0,0.05); display:flex; flex-direction:column; }
        .option-title { font-size:1.2rem; color:#668073; font-weight:bold; margin-bottom:15px; border-bottom:2px solid #F0F2F5; padding-bottom:10px; display:flex; align-items:center; gap:10px; }

        /* Form */
        input[type="text"], input[type="email"] { width:100%; padding:12px; margin:5px 0 15px; border:1px solid #ccc; border-radius:8px; font-size:16px; box-sizing:border-box; }
        select { width:100%; padding:12px; margin-bottom:20px; border:1px solid #ccc; border-radius:8px; background:white; font-size:16px; }
        .btn { background:#668073; color:white; padding:15px 20px; border:none; cursor:pointer; font-weight:bold; border-radius:8px; width:100%; margin-top:auto; font-size:1rem; transition:background 0.2s; text-align:center; text-decoration:none; display:inline-block; font-family:inherit; }
        .btn:hover { background:#556b60; }
        .btn-confirm { background:#2e7d32; }
        .btn-cancel  { flex:1; background:#e0e0e0; color:#333; text-align:center; padding:15px; border-radius:8px; text-decoration:none; font-weight:bold; display:flex; align-items:center; justify-content:center; }

        /* Liste */
        .scroll-list { max-height:300px; overflow-y:auto; margin:15px 0; border:1px solid #eee; padding:10px; border-radius:8px; background:#fdfdfd; }
        .paziente-row { display:flex; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid #eee; }
        .paziente-row:last-child { border-bottom:none; }

        /* Card recensioni */
        .approval-section { margin-bottom:30px; }
        .approval-card { background:#fff; padding:20px; border-radius:12px; margin-bottom:15px; border-left:5px solid #E67E22; box-shadow:0 3px 10px rgba(0,0,0,0.05); }
        .approval-card.approved { border-left-color:#2e7d32; }
        .approval-header  { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
        .approval-author  { font-weight:bold; font-size:1.1rem; color:#333; }
        .approval-stars   { color:#f1c40f; letter-spacing:2px; }
        .approval-text    { font-style:italic; color:#555; line-height:1.5; background:#fafafa; padding:10px; border-radius:8px; margin-bottom:15px; }
        .approval-actions { display:flex; gap:10px; }
        .btn-approve  { background:#27ae60; flex:1; color:white; padding:10px; border-radius:6px; text-decoration:none; text-align:center; font-weight:bold; font-size:0.9rem; font-family:inherit; border:none; cursor:pointer; }
        .btn-delete   { background:#c0392b; flex:1; color:white; padding:10px; border-radius:6px; text-decoration:none; text-align:center; font-weight:bold; font-size:0.9rem; font-family:inherit; border:none; cursor:pointer; }
        .btn-delete-full { flex:auto; width:100%; }

        /* Tabella anteprima */
        .preview-table { width:100%; border-collapse:collapse; margin-bottom:20px; }
        .preview-table th, .preview-table td { padding:12px 15px; border-bottom:1px solid #eee; text-align:left; }
        .preview-table th { background:#f9f9f9; color:#668073; font-weight:600; }

        @media (max-width:768px) {
            .option-grid { grid-template-columns:1fr; gap:15px; }
        }
    </style>
</head>
<body>
<?php include 'admin_topbar.php'; ?>
<div class="container">

<?php if ($step === 'selezione'): ?>

    <?php
    // --- SEZIONE: Recensioni in attesa di approvazione ---
    $stmt_w = $conn->prepare("SELECT * FROM recensioni WHERE approvata = 0 ORDER BY id DESC");
    $stmt_w->execute();
    $res_waiting = $stmt_w->get_result();
    $stmt_w->close();

    if ($res_waiting->num_rows > 0):
    ?>
        <div class="approval-section">
            <h2 style="color:#E67E22; margin-bottom:15px; font-size:1.3rem;">
                <i class='bx bxs-bell-ring'></i> In attesa di approvazione (<?php echo $res_waiting->num_rows; ?>)
            </h2>

            <?php while ($rev = $res_waiting->fetch_assoc()):
                $stars = str_repeat("★", $rev['voto'])
                       . str_repeat("<span style='color:#ddd'>★</span>", 5 - $rev['voto']);
            ?>
            <div class="approval-card">
                <div class="approval-header">
                    <div class="approval-author"><?php echo htmlspecialchars($rev['nome'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="approval-stars"><?php echo $stars; ?></div>
                </div>
                <div class="approval-text">"<?php echo nl2br(htmlspecialchars($rev['testo'], ENT_QUOTES, 'UTF-8')); ?>"</div>
                <div class="approval-actions">
                    <form method="POST" style="flex:1; margin:0;" onsubmit="return confirm('Sei sicura di voler ELIMINARE questa recensione?');">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="id" value="<?php echo (int)$rev['id']; ?>">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="btn-delete" style="width:100%;">
                            <i class='bx bx-trash'></i> Rifiuta
                        </button>
                    </form>
                    <form method="POST" style="flex:1; margin:0;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="id" value="<?php echo (int)$rev['id']; ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn-approve" style="width:100%;">
                            <i class='bx bx-check-circle'></i> Pubblica
                        </button>
                    </form>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>

    <?php
    // --- SEZIONE: Recensioni già pubblicate ---
    $stmt_a = $conn->prepare("SELECT * FROM recensioni WHERE approvata = 1 ORDER BY id DESC");
    $stmt_a->execute();
    $res_approved = $stmt_a->get_result();
    $stmt_a->close();

    if ($res_approved->num_rows > 0):
    ?>
        <div class="approval-section">
            <h2 style="color:#2e7d32; margin-bottom:15px; font-size:1.3rem;">
                <i class='bx bx-check-circle'></i> Recensioni Pubblicate (<?php echo $res_approved->num_rows; ?>)
            </h2>
            <p style="color:#666; font-size:0.9rem; margin-bottom:10px;">Queste recensioni sono visibili sul sito.</p>

            <div class="scroll-list" style="max-height:400px;">
                <?php while ($rev = $res_approved->fetch_assoc()):
                    $stars = str_repeat("★", $rev['voto'])
                           . str_repeat("<span style='color:#ddd'>★</span>", 5 - $rev['voto']);
                ?>
                <div class="approval-card approved">
                    <div class="approval-header">
                        <div class="approval-author"><?php echo htmlspecialchars($rev['nome'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="approval-stars"><?php echo $stars; ?></div>
                    </div>
                    <div class="approval-text">"<?php echo nl2br(htmlspecialchars($rev['testo'], ENT_QUOTES, 'UTF-8')); ?>"</div>
                    <div class="approval-actions">
                        <form method="POST" style="width:100%; margin:0;" onsubmit="return confirm('Vuoi cancellare questa recensione dal sito?');">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo (int)$rev['id']; ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="btn-delete btn-delete-full">
                                <i class='bx bx-trash'></i> Elimina
                            </button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <hr style="border:0; border-top:1px solid #ddd; margin:30px 0;">
    <?php endif; ?>

    <!-- SEZIONE: Richiedi nuove recensioni -->
    <h2 style="color:#1A2621;">Richiedi Nuove Recensioni</h2>
    <div class="option-grid">

        <!-- Box Invio Automatico -->
        <div class="option-box">
            <div class="option-title"><i class='bx bx-radar'></i> Invio Automatico</div>
            <p style="font-size:0.9rem; color:#666; flex-grow:1;">Pazienti venuti oggi per un controllo.</p>
            <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="step" value="conferma">
                <input type="hidden" name="tipo_invio" value="auto">
                <div class="scroll-list">
                    <?php
                    $data_oggi = date('Y-m-d');
                    $stmt_oggi = $conn->prepare(
                        "SELECT id, nome, email FROM prenotazioni
                         WHERE status = 'confermata' AND servizio = 'controllo'
                         AND DATE(data_inizio) = ?"
                    );
                    $stmt_oggi->bind_param("s", $data_oggi);
                    $stmt_oggi->execute();
                    $res_oggi = $stmt_oggi->get_result();
                    $stmt_oggi->close();

                    if ($res_oggi->num_rows > 0) {
                        while ($row = $res_oggi->fetch_assoc()) {
                            $n = htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8');
                            $e = htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8');
                            $row_id = (int)$row['id'];
                            echo "<div class='paziente-row'>
                                    <input type='checkbox' name='ids[]' value='{$row_id}' checked>
                                    <div><strong>$n</strong><br><small>$e</small></div>
                                  </div>";
                        }
                    } else {
                        echo "<p style='color:#999; text-align:center; padding:20px;'>Nessun controllo oggi.</p>";
                    }
                    ?>
                </div>
                <button type="submit" class="btn">Prepara Invio</button>
            </form>
        </div>

        <!-- Box Invio Manuale -->
        <div class="option-box">
            <div class="option-title"><i class='bx bx-user'></i> Invio Manuale</div>
            <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="step" value="conferma">
                <input type="hidden" name="tipo_invio" value="manuale">

                <label style="font-weight:600; font-size:0.9rem; display:block; color:#668073; margin-bottom:5px;">
                    Seleziona Cliente (opzionale)
                </label>
                <select id="select_cliente" onchange="compilaCampi()">
                    <option value="">-- Scegli dalla lista --</option>
                    <?php
                    $stmt_cli = $conn->prepare(
                        "SELECT DISTINCT nome, email FROM prenotazioni WHERE email != '' ORDER BY nome ASC"
                    );
                    $stmt_cli->execute();
                    $res_cli = $stmt_cli->get_result();
                    $stmt_cli->close();

                    while ($cl = $res_cli->fetch_assoc()) {
                        $ns = htmlspecialchars($cl['nome'], ENT_QUOTES, 'UTF-8');
                        $es = htmlspecialchars($cl['email'], ENT_QUOTES, 'UTF-8');
                        echo "<option value='$ns' data-email='$es'>$ns</option>";
                    }
                    ?>
                </select>

                <div style="text-align:center; margin:-10px 0 15px; font-size:0.8rem; color:#999;">
                    — OPPURE INSERISCI MANUALMENTE —
                </div>

                <label style="font-weight:600; font-size:0.9rem;">Nome Paziente</label>
                <input type="text"  id="manual_nome"  name="manual_nome"  required placeholder="Es. Mario Rossi">

                <label style="font-weight:600; font-size:0.9rem;">Indirizzo Email</label>
                <input type="email" id="manual_email" name="manual_email" required placeholder="email@esempio.com">

                <button type="submit" class="btn">Prepara Invio</button>
            </form>
        </div>

    </div><!-- fine option-grid -->

<?php elseif ($step === 'conferma'): ?>

    <h2 style="color:#668073;">Conferma Invio</h2>

    <?php if (!empty($lista_invio)): ?>
        <table class="preview-table">
            <thead><tr><th>Nome</th><th>Email</th><th>Tipo</th></tr></thead>
            <tbody>
                <?php foreach ($lista_invio as $p): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($p['nome'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                    <td><?php echo htmlspecialchars($p['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><small><?php echo htmlspecialchars($p['tipo'], ENT_QUOTES, 'UTF-8'); ?></small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <form action="processa_invio_recensioni.php" method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="pacchetto_dati"
                   value="<?php echo htmlspecialchars(base64_encode(json_encode($lista_invio)), ENT_QUOTES, 'UTF-8'); ?>">
            <div style="display:flex; gap:10px; margin-top:20px;">
                <a href="admin_recensioni_hub.php" class="btn-cancel">Annulla</a>
                <button type="submit" class="btn btn-confirm" style="flex:2;">Invia Mail</button>
            </div>
        </form>

    <?php else: ?>
        <p style="color:#888;">Nessun destinatario selezionato o dati non validi.</p>
        <a href="admin_recensioni_hub.php" class="btn" style="max-width:200px;">Indietro</a>
    <?php endif; ?>

<?php endif; ?>

</div><!-- fine container -->

<script>
    function compilaCampi() {
        const select = document.getElementById('select_cliente');
        const opt    = select.options[select.selectedIndex];
        if (select.value !== "") {
            document.getElementById('manual_nome').value  = select.value;
            document.getElementById('manual_email').value = opt.getAttribute('data-email');
        } else {
            document.getElementById('manual_nome').value  = "";
            document.getElementById('manual_email').value = "";
        }
    }
</script>
</body>
</html>
