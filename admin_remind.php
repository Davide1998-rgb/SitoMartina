<?php
require_once 'security.php';
require_admin_login();


require_once 'db_connect.php';
date_default_timezone_set('Europe/Rome');

$domani = new DateTime('tomorrow');
$data_domani = $domani->format('Y-m-d');
$data_richiesta = trim((string)($_GET['data'] ?? ''));
$data_valida = DateTime::createFromFormat('!d/m/Y', $data_richiesta);
if (!$data_valida || $data_valida->format('d/m/Y') !== $data_richiesta) {
        $data_selezionata = $data_domani;
        $data_valida = $domani;
} else {
    $data_selezionata = $data_valida->format('Y-m-d');
}
$giorno_selezionato = $data_valida->format('d/m/Y');

$stmt = $conn->prepare(
    "SELECT nome, telefono, data_inizio
     FROM prenotazioni
         WHERE DATE(data_inizio) = ?
       AND status IN ('confermata', 'in_attesa')
     ORDER BY data_inizio ASC"
);
$stmt->bind_param('s', $data_selezionata);
$stmt->execute();
$appuntamenti = $stmt->get_result();

function numeroWhatsApp($telefono) {
    $numero = preg_replace('/\D+/', '', (string)$telefono);
    if (substr($numero, 0, 2) === '00') {
        $numero = substr($numero, 2);
    }
    if ($numero !== '' && substr($numero, 0, 2) !== '39') {
        $numero = '39' . $numero;
    }
    return $numero;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="icon" type="image/png" href="img/logo.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remind appuntamento - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
    <style>
        body { font-family: 'Montserrat', sans-serif; margin: 0; padding: 20px; }
        .remind-container { max-width: 900px; margin: 0 auto; }
        .remind-header { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1.5rem; }
        .remind-header h1 { margin: 0; color: #1A2621; font-size: 1.8rem; }
        .back-btn { white-space: nowrap; }
        .remind-intro { color: #66736D; margin: 0 0 1.5rem; }
        .date-form { background: #fff; border: 1px solid #DDE5E1; border-radius: 12px; padding: 1rem 1.2rem; display: flex; align-items: end; gap: 0.8rem; margin-bottom: 1.5rem; }
        .date-form label { color: #66736D; font-size: 0.85rem; font-weight: 600; display: flex; flex-direction: column; gap: 0.4rem; }
        .date-inputs { display: flex; align-items: center; gap: 0.5rem; }
        .date-form input { min-height: 42px; border: 1px solid #DDE5E1; border-radius: 10px; padding: 0.7rem; color: #1A2621; font: inherit; }
        .date-picker-wrap { position: relative; width: 42px; height: 42px; }
        .date-picker-trigger { width: 42px; height: 42px; padding: 0; border: 1px solid #DDE5E1; border-radius: 10px; background: #fff; color: #668073; font-size: 1.25rem; cursor: pointer; }
        .date-picker { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; pointer-events: none; }
        .remind-list { display: grid; gap: 0.8rem; }
        .patient-row { background: #fff; border: 1px solid #DDE5E1; border-radius: 12px; padding: 1rem 1.2rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; box-shadow: 0 8px 24px rgba(31, 55, 45, 0.08); }
        .patient-info { min-width: 0; }
        .patient-name { display: block; color: #1A2621; font-size: 1rem; font-weight: 700; margin-bottom: 0.25rem; }
        .patient-time { color: #66736D; font-size: 0.9rem; }
        .btn-whatsapp { flex-shrink: 0; }
        .empty-state { background: #fff; border: 1px dashed #DDE5E1; border-radius: 12px; padding: 2rem; color: #66736D; text-align: center; }
        @media (max-width: 600px) {
            body { padding: 10px; }
            .remind-header { align-items: flex-start; flex-direction: column; }
            .remind-header h1 { font-size: 1.5rem; }
            .date-form { align-items: stretch; flex-direction: column; }
            .date-form .btn { width: 100%; }
            .patient-row { align-items: flex-start; flex-direction: column; }
            .btn-whatsapp { width: 100%; }
        }
    </style>
</head>
<body>
    <?php include 'admin_topbar.php'; ?>

    <main class="remind-container">
        <div class="remind-header">
            <div>
                <h1><i class='bx bxl-whatsapp'></i> Remind appuntamento</h1>
                <p class="remind-intro">Appuntamenti previsti per il <?php echo htmlspecialchars($giorno_selezionato, ENT_QUOTES, 'UTF-8'); ?>.</p>
            </div>
            <a href="dashboard.php" class="back-btn"><i class='bx bx-arrow-back'></i> Dashboard</a>
        </div>

        <form class="date-form" method="get">
            <label for="data">Seleziona il giorno</label>
            <div class="date-inputs">
                <input type="text" id="data" name="data" value="<?php echo htmlspecialchars($giorno_selezionato, ENT_QUOTES, 'UTF-8'); ?>" placeholder="gg/mm/aaaa" pattern="[0-9]{2}/[0-9]{2}/[0-9]{4}" inputmode="numeric" required>
                <div class="date-picker-wrap">
                    <button type="button" class="date-picker-trigger" aria-label="Apri calendario"><i class='bx bx-calendar'></i></button>
                    <input type="date" id="data_picker" class="date-picker" value="<?php echo htmlspecialchars($data_selezionata, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Apri calendario">
                </div>
            </div>
        </form>

        <div class="remind-list">
            <?php if ($appuntamenti->num_rows > 0): ?>
                <?php while ($row = $appuntamenti->fetch_assoc()):
                    $nome = trim((string)$row['nome']);
                    $ora = date('H:i', strtotime($row['data_inizio']));
                    $numero_whatsapp = numeroWhatsApp($row['telefono']);
                    $messaggio = "Ciao $nome, ti ricordo l'appuntamento del $giorno_selezionato alle ore $ora.";
                ?>
                    <div class="patient-row">
                        <div class="patient-info">
                            <span class="patient-name"><?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="patient-time"><i class='bx bx-time-five'></i> Appuntamento alle <?php echo htmlspecialchars($ora, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <?php if ($numero_whatsapp !== ''): ?>
                            <a href="https://wa.me/<?php echo htmlspecialchars($numero_whatsapp, ENT_QUOTES, 'UTF-8'); ?>?text=<?php echo rawurlencode($messaggio); ?>" class="btn-act btn-whatsapp" target="_blank" rel="noopener noreferrer">
                                <i class='bx bxl-whatsapp'></i> Scrivi su WhatsApp
                            </a>
                        <?php else: ?>
                            <span style="color:#999;">Nessun numero disponibile</span>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state"><i class='bx bx-calendar-x' style="font-size:2rem;"></i><br>Nessun appuntamento previsto per domani.</div>
            <?php endif; ?>
        </div>
    </main>
    <script>
        const dataInput = document.getElementById('data');
        const dataPicker = document.getElementById('data_picker');
        const dataPickerTrigger = document.querySelector('.date-picker-trigger');
        const dataForm = dataInput.form;

        dataPickerTrigger.addEventListener('click', function () {
            if (typeof dataPicker.showPicker === 'function') {
                dataPicker.showPicker();
            } else {
                dataPicker.focus();
                dataPicker.click();
            }
        });

        dataPicker.addEventListener('change', function () {
            if (!this.value) return;
            const parti = this.value.split('-');
            dataInput.value = `${parti[2]}/${parti[1]}/${parti[0]}`;
            dataForm.submit();
        });

        dataInput.addEventListener('change', function () {
            if (/^\d{2}\/\d{2}\/\d{4}$/.test(dataInput.value)) {
                dataForm.submit();
            }
        });

        dataForm.addEventListener('submit', function () {
            const parti = dataInput.value.split('/');
            if (parti.length === 3) {
                dataPicker.value = `${parti[2]}-${parti[1]}-${parti[0]}`;
            }
        });
    </script>
</body>
</html>
