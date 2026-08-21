<?php
require_once 'security.php';
require_admin_login();
require_once 'db_connect.php';
require_once 'disponibilita.php';

date_default_timezone_set('Europe/Rome');
disponibilita_assicura_tabella($conn);
$messaggio = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $azione = $_POST['azione'] ?? '';

    if ($azione === 'elimina') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM disponibilita_blocchi WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        $messaggio = '<div class="notice success">Regola eliminata.</div>';
    } elseif ($azione === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare('UPDATE disponibilita_blocchi SET attivo = IF(attivo = 1, 0, 1) WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        $messaggio = '<div class="notice success">Stato della regola aggiornato.</div>';
    } elseif ($azione === 'crea') {
        $titolo = trim((string)($_POST['titolo'] ?? ''));
        $tipo = $_POST['tipo'] ?? '';
        $dataInizio = trim((string)($_POST['data_inizio'] ?? ''));
        $dataFine = trim((string)($_POST['data_fine'] ?? ''));
        $oraInizio = trim((string)($_POST['ora_inizio'] ?? ''));
        $oraFine = trim((string)($_POST['ora_fine'] ?? ''));
        $permanente = isset($_POST['permanente']) && $tipo !== 'giorno';
        $giorni = array_map('intval', $_POST['giorni'] ?? []);
        $giorni = array_values(array_filter($giorni, static fn(int $giorno): bool => $giorno >= 1 && $giorno <= 7));
        $giorniCsv = $giorni ? implode(',', $giorni) : null;
        $tipoSalvato = $tipo;

        $dataValida = DateTime::createFromFormat('!Y-m-d', $dataInizio);
        $fineValida = $dataFine === '' ? true : (bool)DateTime::createFromFormat('!Y-m-d', $dataFine);
        $tipoValido = in_array($tipo, ['giorno', 'periodo', 'fascia', 'ricorrente'], true);
        $fasciaValida = ($oraInizio === '' && $oraFine === '') || ($oraInizio !== '' && $oraFine !== '' && $oraInizio < $oraFine);
        $regolaValida = $titolo !== '' && strlen($titolo) <= 150 && $dataValida && $fineValida && $tipoValido && $fasciaValida;

        if ($tipo === 'giorno') {
            $dataFine = $dataInizio;
            $oraInizio = null;
            $oraFine = null;
        } elseif ($tipo === 'periodo') {
            $oraInizio = null;
            $oraFine = null;
            $regolaValida = $regolaValida && $dataFine !== '' && $dataFine >= $dataInizio;
        } elseif ($tipo === 'fascia') {
            $dataFine = $dataInizio;
            $regolaValida = $regolaValida && $oraInizio !== null && $oraFine !== null;
            if ($permanente) {
                $tipoSalvato = 'ricorrente';
                $giorniCsv = (string)date('N', strtotime($dataInizio));
                $dataFine = '';
            }
        } elseif ($tipo === 'ricorrente') {
            if ($permanente) {
                $dataFine = '';
            }
            $regolaValida = $regolaValida && count($giorni) > 0 && ($permanente || ($dataFine !== '' && $dataFine >= $dataInizio));
        }

        if ($regolaValida) {
            $stmt = $conn->prepare(
                'INSERT INTO disponibilita_blocchi (titolo, tipo, data_inizio, data_fine, ora_inizio, ora_fine, giorni_settimana) VALUES (?, ?, ?, NULLIF(?, ""), NULLIF(?, ""), NULLIF(?, ""), ?)'
            );
            $dataFineParam = $dataFine;
            $oraInizioParam = $oraInizio ?? '';
            $oraFineParam = $oraFine ?? '';
            $giorniParam = $giorniCsv ?? '';
            $stmt->bind_param('sssssss', $titolo, $tipoSalvato, $dataInizio, $dataFineParam, $oraInizioParam, $oraFineParam, $giorniParam);
            $stmt->execute();
            $stmt->close();
            $messaggio = '<div class="notice success">Regola di indisponibilità salvata.</div>';
        } else {
            $messaggio = '<div class="notice error">Controlla titolo, date, orari e giorni selezionati.</div>';
        }
    }
}

$regole = $conn->query('SELECT * FROM disponibilita_blocchi ORDER BY attivo DESC, data_inizio ASC, id DESC');
$giorniNomi = [1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Gio', 5 => 'Ven', 6 => 'Sab', 7 => 'Dom'];
$tipiNomi = ['giorno' => 'Giorno singolo', 'periodo' => 'Periodo', 'fascia' => 'Fascia oraria', 'ricorrente' => 'Ricorrente'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disponibilità calendario - Admin</title>
    <link rel="stylesheet" href="admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; font-family:'Montserrat', sans-serif !important; }
        .availability-panel h1, .availability-panel h2 { font-family:'Playfair Display', Georgia, serif; }
        .availability-layout { max-width: 1100px; margin: 0 auto; }
        .availability-panel { background:#fff; padding:1.5rem; margin-bottom:1.5rem; }
        .availability-panel h1, .availability-panel h2 { margin-top:0; }
        .availability-form { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:1rem; }
        .availability-form label { display:flex; flex-direction:column; gap:.35rem; color:var(--admin-muted); font-size:.82rem; font-weight:600; }
        .availability-form .full { grid-column:1 / -1; }
        .availability-form small { color:var(--admin-muted); font-weight:400; }
        .availability-form .is-hidden { display:none !important; }
        .permanent-option { justify-content:center; padding:.75rem 1rem; border:1px solid var(--admin-border); border-radius:9px; background:#f8fbf9; }
        .permanent-option span { display:flex; align-items:center; gap:.5rem; color:var(--admin-ink); }
        .permanent-option input { min-height:auto; }
        .weekday-list { display:flex; flex-wrap:wrap; gap:.5rem; }
        .weekday-list label { flex-direction:row; align-items:center; min-height:42px; padding:.55rem .75rem; border:1px solid var(--admin-border); border-radius:9px; color:var(--admin-ink); }
        .weekday-list input { min-height:auto; }
        .availability-actions { display:flex; gap:.5rem; align-items:center; }
        .availability-table { width:100%; border-collapse:collapse; }
        .availability-table th, .availability-table td { padding:.8rem .6rem; text-align:left; border-bottom:1px solid var(--admin-border); vertical-align:top; }
        .availability-table th { color:var(--admin-muted); font-size:.75rem; text-transform:uppercase; }
        .status-active { color:#2e7d32; font-weight:700; }
        .status-off { color:var(--admin-muted); }
        .notice { padding:1rem; margin-bottom:1rem; border-radius:9px; }
        .notice.success { background:#e8f5e9; color:#226b2b; }
        .notice.error { background:#fff1f1; color:#9b2c2c; }
        @media (max-width:700px) { .availability-form { grid-template-columns:1fr; } .availability-form .full { grid-column:auto; } .availability-table { min-width:760px; } .table-scroll { overflow-x:auto; } }
    </style>
</head>
<body>
<?php include 'admin_topbar.php'; ?>
<main class="availability-layout">
    <section class="availability-panel">
        <h1>Controllo disponibilità</h1>
        <p>Blocca date, periodi, fasce orarie o giorni ricorrenti. I blocchi vengono applicati al calendario pubblico e alla conferma della prenotazione.</p>
        <?= $messaggio ?>
        <form method="POST" class="availability-form">
            <?= csrf_field() ?>
            <input type="hidden" name="azione" value="crea">
            <label>Titolo <input name="titolo" required maxlength="150" placeholder="Es. Ferie estive"></label>
            <label>Tipo
                <select name="tipo" id="tipo-regola" required>
                    <option value="giorno">Giorno singolo</option>
                    <option value="fascia">Fascia oraria di un giorno</option>
                    <option value="periodo">Periodo di giorni</option>
                    <option value="ricorrente">Giorni della settimana</option>
                </select>
            </label>
            <label><span id="data-inizio-label">Data</span> <input type="date" name="data_inizio" required></label>
            <label id="data-fine-field">Al <input type="date" name="data_fine" id="data-fine"><small>Serve per un periodo saltuario o una ricorrenza con scadenza.</small></label>
            <label class="time-field">Ora inizio <input type="time" name="ora_inizio"></label>
            <label class="time-field">Ora fine <input type="time" name="ora_fine"></label>
            <label class="permanent-option is-hidden" id="permanent-field"><span><input type="checkbox" name="permanente" id="permanente"> Modifica permanente</span><small>Il blocco ricorrente non avrà una data di fine.</small></label>
            <fieldset class="full weekday-field" hidden>
                <legend>Giorni della settimana</legend>
                <div class="weekday-list">
                    <?php foreach ($giorniNomi as $numero => $nome): ?><label><input type="checkbox" name="giorni[]" value="<?= $numero ?>"> <?= $nome ?></label><?php endforeach; ?>
                </div>
            </fieldset>
            <div class="full"><button class="btn-save" type="submit"><i class="bx bx-save"></i> Salva blocco</button></div>
        </form>
    </section>
    <section class="availability-panel">
        <h2>Regole attive e archiviate</h2>
        <div class="table-scroll">
            <table class="availability-table">
                <thead><tr><th>Regola</th><th>Periodo</th><th>Orario</th><th>Stato</th><th>Azioni</th></tr></thead>
                <tbody>
                <?php if ($regole && $regole->num_rows > 0): while ($regola = $regole->fetch_assoc()):
                    $giorniRegola = array_map('intval', array_filter(explode(',', (string)$regola['giorni_settimana'])));
                    $giorniTesto = implode(', ', array_map(fn($g) => $giorniNomi[$g] ?? '', $giorniRegola));
                ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($regola['titolo'], ENT_QUOTES, 'UTF-8') ?></strong><br><small><?= $tipiNomi[$regola['tipo']] ?? $regola['tipo'] ?><?= $giorniTesto ? ' · ' . htmlspecialchars($giorniTesto, ENT_QUOTES, 'UTF-8') : '' ?></small></td>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($regola['data_inizio'])), ENT_QUOTES, 'UTF-8') ?><?= $regola['data_fine'] ? ' - ' . htmlspecialchars(date('d/m/Y', strtotime($regola['data_fine'])), ENT_QUOTES, 'UTF-8') : ' - senza scadenza' ?></td>
                        <td><?= $regola['ora_inizio'] ? substr($regola['ora_inizio'], 0, 5) . ' - ' . substr($regola['ora_fine'], 0, 5) : 'Tutto il giorno' ?></td>
                        <td class="<?= $regola['attivo'] ? 'status-active' : 'status-off' ?>"><?= $regola['attivo'] ? 'Attiva' : 'Disattivata' ?></td>
                        <td><div class="availability-actions"><form method="POST"><input type="hidden" name="azione" value="toggle"><input type="hidden" name="id" value="<?= (int)$regola['id'] ?>"><?= csrf_field() ?><button class="btn-no-mail" type="submit"><?= $regola['attivo'] ? 'Disattiva' : 'Attiva' ?></button></form><form method="POST" onsubmit="return confirm('Eliminare questa regola?');"><input type="hidden" name="azione" value="elimina"><input type="hidden" name="id" value="<?= (int)$regola['id'] ?>"><?= csrf_field() ?><button class="btn-delete" type="submit">Elimina</button></form></div></td>
                    </tr>
                <?php endwhile; else: ?><tr><td colspan="5">Nessuna regola inserita.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
<script>
const typeSelect = document.getElementById('tipo-regola');
const weekdayField = document.querySelector('.weekday-field');
const timeFields = document.querySelectorAll('.time-field');
const endDate = document.getElementById('data-fine');
const endDateField = document.getElementById('data-fine-field');
const startDateLabel = document.getElementById('data-inizio-label');
const permanentField = document.getElementById('permanent-field');
const permanentCheckbox = document.getElementById('permanente');
function updateRuleFields() {
    const type = typeSelect.value;
    const showTimes = type === 'fascia' || type === 'ricorrente';
    weekdayField.hidden = type !== 'ricorrente';
    weekdayField.classList.toggle('is-hidden', type !== 'ricorrente');
    timeFields.forEach(field => {
        field.hidden = !showTimes;
        field.classList.toggle('is-hidden', !showTimes);
    });
    const recurring = type === 'ricorrente';
    const permanentAllowed = type === 'fascia' || recurring;
    permanentField.classList.toggle('is-hidden', !permanentAllowed);
    permanentCheckbox.disabled = !permanentAllowed;
    if (!permanentAllowed) permanentCheckbox.checked = false;
    endDateField.classList.toggle('is-hidden', type !== 'periodo' && !(recurring && !permanentCheckbox.checked));
    endDate.disabled = type !== 'periodo' && !(recurring && !permanentCheckbox.checked);
    endDate.required = type === 'periodo' || (recurring && !permanentCheckbox.checked);
    startDateLabel.textContent = type === 'periodo' || type === 'ricorrente' ? 'Dal' : 'Data';
}
typeSelect.addEventListener('change', updateRuleFields);
permanentCheckbox.addEventListener('change', updateRuleFields);
updateRuleFields();
</script>
</body>
</html>
