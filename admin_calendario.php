<?php
require_once 'security.php';
require_admin_login();
require_once 'db_connect.php';

// CONFIGURAZIONE
setlocale(LC_TIME, 'it_IT.utf8', 'ita');
date_default_timezone_set('Europe/Rome');

// LOGICA NAVIGAZIONE MESE
$mese = isset($_GET['m']) ? intval($_GET['m']) : (int)date('n');
$anno = isset($_GET['a']) ? intval($_GET['a']) : (int)date('Y');

if ($mese < 1) { $mese = 12; $anno--; }
if ($mese > 12) { $mese = 1; $anno++; }

$giorni_nel_mese = cal_days_in_month(CAL_GREGORIAN, $mese, $anno);
$primo_giorno_timestamp = strtotime(sprintf("%04d-%02d-01", $anno, $mese));
$indice_primo_giorno = (int)date('w', $primo_giorno_timestamp); 
$indice_primo_giorno = ($indice_primo_giorno == 0) ? 6 : $indice_primo_giorno - 1;

$nomi_mesi = ["", "Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno", "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre"];
$titolo_mese = ($nomi_mesi[$mese] ?? '') . " " . $anno;

// SCARICHIAMO APPUNTAMENTI CON PREPARED STATEMENT
$stmt_mese = $conn->prepare("SELECT * FROM prenotazioni WHERE MONTH(data_inizio) = ? AND YEAR(data_inizio) = ? ORDER BY data_inizio ASC");
$stmt_mese->bind_param("ii", $mese, $anno);
$stmt_mese->execute();
$res = $stmt_mese->get_result();

$appuntamenti_per_giorno = [];
while ($row = $res->fetch_assoc()) {
    $g = intval(date('d', strtotime($row['data_inizio'])));
    $appuntamenti_per_giorno[$g][] = $row;
}
$stmt_mese->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="icon" type="image/png" href="img/logo.svg">
    <meta charset="UTF-8">
    <title>Calendario Mensile - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body { font-family: 'Montserrat', sans-serif; background: #F0F2F5; margin: 0; padding: 20px; }
        
        /* HEADER E NAVIGAZIONE */
        .cal-header {
            display: flex; justify-content: space-between; align-items: center;
            background: white; padding: 20px; border-radius: 15px; margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .cal-title h1 { margin: 0; color: #668073; font-size: 1.8rem; }
        .nav-btn {
            background: #f4f4f4; border: none; width: 40px; height: 40px; border-radius: 50%;
            cursor: pointer; font-size: 1.2rem; color: #555; display: flex; align-items: center; justify-content: center;
            text-decoration: none; transition: 0.3s;
        }
        .nav-btn:hover { background: #668073; color: white; }

        /* GRIGLIA CALENDARIO */
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
        }
        
        .weekday-label {
            text-align: center; font-weight: bold; color: #888; padding: 10px;
            text-transform: uppercase; font-size: 0.85rem;
        }

        .day-cell {
            background: white; min-height: 100px; border-radius: 10px; padding: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02); transition: 0.2s; position: relative;
            cursor: pointer; display: flex; flex-direction: column; justify-content: space-between;
        }
        .day-cell:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); border: 1px solid #668073; }
        .day-cell.empty { background: transparent; box-shadow: none; pointer-events: none; }
        .day-number { font-weight: bold; color: #668073; font-size: 1.1rem; }
        .day-cell.today { border: 2px solid #668073; background: #fafcfb; }

        /* INDICATORI VISITE (Pallini) */
        .dots-container { display: flex; gap: 4px; flex-wrap: wrap; margin-top: 10px; }
        .dot { width: 8px; height: 8px; border-radius: 50%; background: #ccc; }
        .dot.confermata { background: #668073; }
        .dot.in_attesa { background: #E6B800; }
        .dot.cancellata { background: #d9534f; }
        .dot.online { box-shadow: 0 0 0 2px #1f5fa5 inset; }

        .count-text { font-size: 0.8rem; color: #888; margin-top: 5px; font-weight: 600; }
        .online-count { font-size: 0.72rem; color: #1f5fa5; margin-top: 3px; font-weight: 700; text-transform: uppercase; }

        /* MODALE GENERALE */
        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center;
        }
        .modal-box {
            background: white; padding: 30px; border-radius: 15px; width: 90%; max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); position: relative; max-height: 85vh; overflow-y: auto;
        }
        .modal-header { 
            font-size: 1.2rem; font-weight: bold; color: #668073; 
            margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; 
            display: flex; justify-content: space-between; align-items: center;
        }

        /* LISTA APPUNTAMENTI NEL MODALE */
        .list-item {
            background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 10px;
            display: flex; justify-content: space-between; align-items: center;
            border-left: 4px solid #ccc; transition: 0.2s;
        }
        .list-item:hover { background: #f0f0f0; }
        .list-item.confermata { border-left-color: #668073; }
        .list-item.in_attesa { border-left-color: #E6B800; background: #fffdf0; }
        .list-item.cancellata { border-left-color: #d9534f; opacity: 0.6; }

        .item-info strong { display: block; color: #333; font-size: 1rem; }
        .item-info span { font-size: 0.85rem; color: #666; }
        .btn-gestisci {
            background: #668073; color: white; padding: 8px 15px; border-radius: 20px;
            text-decoration: none; font-size: 0.8rem; font-weight: bold; border: none; cursor: pointer;
        }
        .btn-gestisci:hover { background: #4a5e54; }

        /* FORM EDIT */
        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; font-size: 0.85rem; color: #666; margin-bottom: 5px; }
        .form-group input, .form-group select {
            width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-family: inherit; box-sizing: border-box;
        }
        .btn-row { display: flex; gap: 10px; margin-top: 20px; }
        .btn { flex: 1; padding: 10px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; color: white; }
        .btn-save { background: #668073; }
        .btn-delete { background: #d9534f; }
        .btn-close { background: #ccc; color: #333; }
        
        .close-x { cursor: pointer; font-size: 1.5rem; color: #999; }
        .close-x:hover { color: #333; }

        @media(max-width: 768px) {
            .calendar-grid { grid-template-columns: repeat(1, 1fr); gap: 15px; }
            .day-cell { min-height: auto; flex-direction: row; align-items: center; }
            .day-cell.empty { display: none; }
            .weekday-label { display: none; }
        }
    </style>
</head>
<body>

    <?php include 'admin_topbar.php'; ?>

    <div class="cal-header">
        <a href="?m=<?php echo $mese-1; ?>&a=<?php echo $anno; ?>" class="nav-btn"><i class='bx bx-chevron-left'></i></a>
        <div class="cal-title">
            <h1><?php echo htmlspecialchars($titolo_mese, ENT_QUOTES, 'UTF-8'); ?></h1>
        </div>
        <a href="?m=<?php echo $mese+1; ?>&a=<?php echo $anno; ?>" class="nav-btn"><i class='bx bx-chevron-right'></i></a>
    </div>

    <div class="calendar-grid">
        <div class="weekday-label">Lun</div>
        <div class="weekday-label">Mar</div>
        <div class="weekday-label">Mer</div>
        <div class="weekday-label">Gio</div>
        <div class="weekday-label">Ven</div>
        <div class="weekday-label">Sab</div>
        <div class="weekday-label">Dom</div>

        <?php for($i=0; $i<$indice_primo_giorno; $i++): ?>
            <div class="day-cell empty"></div>
        <?php endfor; ?>

        <?php for($g=1; $g<=$giorni_nel_mese; $g++): 
            $data_corrente = sprintf("%04d-%02d-%02d", $anno, $mese, $g);
            $is_today = ($data_corrente == date('Y-m-d')) ? 'today' : '';
            
            $visite_giorno = isset($appuntamenti_per_giorno[$g]) ? $appuntamenti_per_giorno[$g] : [];
            $numero_visite = count($visite_giorno);
            $numero_online = 0;
            foreach ($visite_giorno as $visit_tmp) {
                if (($visit_tmp['modalita_visita'] ?? 'studio') === 'online') {
                    $numero_online++;
                }
            }

            // Prepariamo i dati JSON sicuri per il JS
            $json_giorno = htmlspecialchars(json_encode($visite_giorno, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
        ?>
            <div class="day-cell <?php echo $is_today; ?>" data-date="<?php echo htmlspecialchars($data_corrente, ENT_QUOTES, 'UTF-8'); ?>" data-visite="<?php echo $json_giorno; ?>">
                <span class="day-number"><?php echo $g; ?></span>
                
                <?php if($numero_visite > 0): ?>
                    <div class="dots-container">
                        <?php foreach($visite_giorno as $v): ?>
                            <?php $classe_online = (($v['modalita_visita'] ?? 'studio') === 'online') ? 'online' : ''; ?>
                            <div class="dot <?php echo htmlspecialchars($v['status'], ENT_QUOTES, 'UTF-8'); ?> <?php echo $classe_online; ?>"></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="count-text"><?php echo $numero_visite; ?> Visite</div>
                    <?php if ($numero_online > 0): ?>
                        <div class="online-count"><?php echo $numero_online; ?> online</div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="count-text" style="opacity:0.3;">Libero</div>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>

    <div id="modalLista" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <span id="titoloLista">Visite del Giorno</span>
                <span class="close-x" onclick="chiudiModale('modalLista')">&times;</span>
            </div>
            <div id="contenutoLista"></div>
            <div style="margin-top:20px; text-align:center;">
                <button onclick="chiudiModale('modalLista')" class="btn btn-close">Chiudi</button>
            </div>
        </div>
    </div>

    <div id="modalModifica" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <span>Gestisci Appuntamento</span>
                <span class="close-x" onclick="chiudiModale('modalModifica')">&times;</span>
            </div>
            
            <form action="admin_azioni_calendario.php" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label>Paziente</label>
                    <input type="text" id="edit_nome" readonly style="background:#f0f0f0; font-weight:bold;">
                </div>

                <div class="form-group">
                    <label>Data</label>
                    <input type="date" name="nuova_data" id="edit_data" required>
                </div>

                <div class="form-group">
                    <label>Ora</label>
                    <input type="time" name="nuova_ora" id="edit_ora" required>
                </div>

                <div class="form-group">
                    <label>Stato</label>
                    <select name="nuovo_status" id="edit_status">
                        <option value="in_attesa">In Attesa (Giallo)</option>
                        <option value="confermata">Confermata (Verde)</option>
                        <option value="cancellata">Cancellata (Rosso)</option>
                    </select>
                </div>

                <div class="btn-row">
                    <button type="submit" name="azione" value="salva" class="btn btn-save">💾 Salva</button>
                    <button type="submit" name="azione" value="elimina" class="btn btn-delete" onclick="return confirm('Sei sicuro di eliminare definitivamente?')">🗑 Elimina</button>
                </div>
                <div class="btn-row">
                    <button type="button" onclick="tornaAllaLista()" class="btn btn-close">← Indietro</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        let currentVisiteGiorno = [];

        document.querySelectorAll('.day-cell:not(.empty)').forEach(cell => {
            cell.addEventListener('click', function() {
                const date = this.getAttribute('data-date');
                const rawVisite = this.getAttribute('data-visite');
                let visite = [];
                try {
                    visite = JSON.parse(rawVisite) || [];
                } catch(e) {
                    visite = [];
                }
                apriListaGiorno(date, visite);
            });
        });

        // 1. APRI LISTA GIORNO
        function apriListaGiorno(data, visite) {
            currentVisiteGiorno = visite;
            let dataArr = data.split('-');
            document.getElementById('titoloLista').innerText = "Visite del " + dataArr[2] + "/" + dataArr[1] + "/" + dataArr[0];
            
            let container = document.getElementById('contenutoLista');
            container.innerHTML = '';

            if (!visite || visite.length === 0) {
                container.innerHTML = '<p style="text-align:center; color:#888;">Nessun appuntamento in questa data.</p>';
            } else {
                visite.sort((a, b) => (a.data_inizio > b.data_inizio) ? 1 : -1);

                visite.forEach((v, index) => {
                    let ora = (v.data_inizio && v.data_inizio.split(' ')[1]) ? v.data_inizio.split(' ')[1].substring(0, 5) : '';
                    let statusSafe = escapeHtml(v.status || '');
                    let nomeSafe = escapeHtml(v.nome || '');
                    let servizioSafe = escapeHtml((v.servizio || '').replace(/_/g, ' '));
                    let telefonoSafe = escapeHtml(v.telefono || '');
                    let modalitaVisita = (v.modalita_visita === 'online') ? 'Online' : 'In studio';
                    let modalitaIcon = (v.modalita_visita === 'online') ? 'bx-laptop' : 'bx-building-house';

                    let itemDiv = document.createElement('div');
                    itemDiv.className = 'list-item ' + statusSafe;
                    itemDiv.innerHTML = `
                        <div class="item-info">
                            <strong>${ora} - ${nomeSafe}</strong>
                            <span>${servizioSafe} • ${telefonoSafe} • <i class='bx ${modalitaIcon}'></i> ${modalitaVisita}</span>
                        </div>
                        <button type="button" class="btn-gestisci" data-index="${index}">Gestisci</button>
                    `;

                    itemDiv.querySelector('.btn-gestisci').addEventListener('click', function() {
                        apriModifica(v);
                    });

                    container.appendChild(itemDiv);
                });
            }

            document.getElementById('modalLista').style.display = 'flex';
        }

        // 2. APRI MODIFICA
        function apriModifica(dati) {
            document.getElementById('modalLista').style.display = 'none';
            document.getElementById('modalModifica').style.display = 'flex';

            document.getElementById('edit_id').value = dati.id || '';
            document.getElementById('edit_nome').value = dati.nome || '';
            
            let split = (dati.data_inizio || '').split(' ');
            document.getElementById('edit_data').value = split[0] || '';
            document.getElementById('edit_ora').value = split[1] ? split[1].substring(0, 5) : '';
            document.getElementById('edit_status').value = dati.status || 'in_attesa';
        }

        // 3. TORNA INDIETRO
        function tornaAllaLista() {
            document.getElementById('modalModifica').style.display = 'none';
            document.getElementById('modalLista').style.display = 'flex';
        }

        function chiudiModale(id) {
            document.getElementById(id).style.display = 'none';
        }
    </script>
</body>
</html>