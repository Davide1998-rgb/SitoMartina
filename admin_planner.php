<?php
require_once 'security.php';
require_admin_login();
setlocale(LC_TIME, 'it_IT.utf8', 'ita');
date_default_timezone_set('Europe/Rome');
require_once 'db_connect.php';

$week_offset = isset($_GET['w']) ? intval($_GET['w']) : 0;
$data_corrente = new DateTime();
$data_corrente->modify('monday this week');
if ($week_offset !== 0) {
    $data_corrente->modify(($week_offset > 0 ? '+' : '') . $week_offset . ' weeks');
}
$sabato_data = clone $data_corrente;
$sabato_data->modify('+5 days');

$nomi_mesi   = ["Gennaio","Febbraio","Marzo","Aprile","Maggio","Giugno","Luglio","Agosto","Settembre","Ottobre","Novembre","Dicembre"];
$nomi_giorni = ['Lunedì','Martedì','Mercoledì','Giovedì','Venerdì','Sabato'];
$mese_txt    = $nomi_mesi[(int)$data_corrente->format('n') - 1];
$anno_txt    = $data_corrente->format('Y');
$intervallo_txt = $data_corrente->format('d') . " - " . $sabato_data->format('d') . " " . $mese_txt;

function getAppuntamenti($conn, $date_string) {
    $stmt = $conn->prepare(
        "SELECT * FROM prenotazioni WHERE DATE(data_inizio) = ?
         AND (status = 'confermata' OR status = 'in_attesa') ORDER BY data_inizio ASC"
    );
    $stmt->bind_param("s", $date_string);
    $stmt->execute();
    return $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="icon" type="image/png" href="img/logo.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Planner Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body{font-family:'Montserrat',sans-serif;background:#F0F2F5;margin:0;padding:10px;color:#333;}
        *{box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
        .modal-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;justify-content:center;align-items:center;padding:20px;}
        .modal-box{background:white;padding:25px;border-radius:15px;width:100%;max-width:400px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.2);}
        .modal-btn{display:block;width:100%;padding:15px;margin:10px 0;border:none;border-radius:8px;cursor:pointer;font-weight:bold;font-size:1rem;text-decoration:none;box-sizing:border-box;font-family:inherit;}
        .btn-yes-mail{background:#668073;color:white;} .btn-no-mail{background:#e0e0e0;color:#333;}
        .btn-reject{background:#fff0f0;color:#d9534f;border:1px solid #d9534f;}
        .btn-close{background:transparent;color:#999;margin-top:15px;font-size:0.9rem;text-decoration:underline;border:none;cursor:pointer;}
        .top-bar{background:#FFFFFF;padding:15px;border-radius:15px;box-shadow:0 4px 15px rgba(0,0,0,0.05);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;margin-bottom:20px;gap:15px;}
        .header-title h1{margin:0;font-size:1.5rem;color:#668073;} .header-title p{margin:5px 0 0;font-size:0.9rem;color:#666;}
        .nav-controls{display:flex;gap:10px;background:#F9F9F9;padding:5px;border-radius:50px;align-items:center;}
        .btn-nav{width:35px;height:35px;display:flex;justify-content:center;align-items:center;background:white;border-radius:50%;text-decoration:none;color:#555;border:1px solid #ddd;}
        .btn-today{font-weight:bold;color:#668073;text-decoration:none;padding:0 10px;font-size:0.9rem;white-space:nowrap;}
        .btn-new{background:#668073;color:white;padding:10px 15px;border-radius:20px;text-decoration:none;font-weight:bold;display:flex;gap:5px;align-items:center;white-space:nowrap;}
        .planner-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:15px;}
        .day-column{background:#FFFFFF;border-radius:12px;padding:10px;min-height:600px;box-shadow:0 2px 5px rgba(0,0,0,0.03);display:flex;flex-direction:column;}
        .day-column.today{border:2px solid #668073;background:#FAFCFB;}
        .day-header{text-align:center;border-bottom:2px solid #F0F2F5;margin-bottom:10px;padding-bottom:10px;position:sticky;top:0;z-index:10;background:inherit;}
        .day-name{font-weight:700;display:block;text-transform:uppercase;font-size:0.8rem;color:#888;}
        .day-date{font-size:1.3rem;color:#668073;font-weight:bold;}
        .app-card{background:white;border-left:4px solid #668073;padding:10px;margin-bottom:10px;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,0.08);position:relative;}
        .app-card.prima_visita{border-left-color:#E6B800;} .app-card.in_attesa{border-left-style:dashed;background:#fff5f5;}
        .card-time{font-weight:800;font-size:1rem;color:#333;} .card-name{font-size:0.9rem;font-weight:600;color:#555;}
        .card-service{font-size:0.7rem;text-transform:uppercase;color:#999;margin-top:3px;}
        .card-tel a{color:#668073;text-decoration:none;font-size:0.8rem;display:block;margin-top:5px;}
        .badge-attesa{background:#d9534f;color:white;font-size:0.6rem;padding:2px 6px;border-radius:4px;position:absolute;top:10px;right:10px;}
        .btn-gestisci{width:100%;margin-top:8px;background:#d9534f;color:white;border:none;padding:10px;border-radius:5px;cursor:pointer;font-weight:bold;font-family:inherit;font-size:0.85rem;display:flex;align-items:center;justify-content:center;gap:5px;}
        @media(max-width:1024px){.planner-grid{grid-template-columns:repeat(3,1fr);}.day-column{min-height:400px;}}
        @media(max-width:768px){
            .top-bar{flex-direction:column;text-align:center;}
            .nav-controls,.action-buttons{width:100%;justify-content:space-between;}
            .action-buttons{border-top:1px solid #eee;padding-top:15px;}
            .btn-new{flex-grow:1;justify-content:center;}
            .planner-grid{grid-template-columns:1fr;}
            .day-column{min-height:auto;padding-bottom:20px;}
            .day-header{display:flex;justify-content:space-between;align-items:center;padding:10px;}
        }
    </style>
</head>
<body>
    <?php include 'admin_topbar.php'; ?>
    <div class="top-bar">
        <div class="header-title">
            <h1><?php echo htmlspecialchars("$mese_txt $anno_txt", ENT_QUOTES, 'UTF-8'); ?></h1>
            <p>Settimana: <?php echo htmlspecialchars($intervallo_txt, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <div class="nav-controls">
            <a href="?w=<?php echo $week_offset - 1; ?>" class="btn-nav"><i class='bx bx-chevron-left'></i></a>
            <?php if ($week_offset != 0): ?>
                <a href="?w=0" class="btn-today">Torna a Oggi</a>
            <?php else: ?>
                <span class="btn-today">Questa settimana</span>
            <?php endif; ?>
            <a href="?w=<?php echo $week_offset + 1; ?>" class="btn-nav"><i class='bx bx-chevron-right'></i></a>
        </div>
        <div class="action-buttons">
            <a href="admin_manuale.php" class="btn-new"><i class='bx bx-plus'></i> Nuovo Appuntamento</a>
        </div>
    </div>

    <div class="planner-grid">
        <?php for ($i = 0; $i < 6; $i++):
            $giorno_temp  = clone $data_corrente;
            $giorno_temp->modify("+$i days");
            $data_stringa  = $giorno_temp->format('Y-m-d');
            $is_today      = ($data_stringa == date('Y-m-d')) ? 'today' : '';
            $appuntamenti  = getAppuntamenti($conn, $data_stringa);
        ?>
            <div class="day-column <?php echo $is_today; ?>">
                <div class="day-header">
                    <span class="day-name"><?php echo $nomi_giorni[$i]; ?></span>
                    <span class="day-date"><?php echo $giorno_temp->format('d'); ?></span>
                </div>
                <?php if ($appuntamenti && $appuntamenti->num_rows > 0): ?>
                    <?php while ($row = $appuntamenti->fetch_assoc()):
                        $ora             = date('H:i', strtotime($row['data_inizio']));
                        $classe_servizio = ($row['servizio'] == 'prima_visita') ? 'prima_visita' : 'controllo';
                        $classe_status   = ($row['status'] == 'in_attesa') ? 'in_attesa' : '';
                    ?>
                        <div class="app-card <?php echo "$classe_servizio $classe_status"; ?>">
                            <div class="card-time"><?php echo htmlspecialchars($ora, ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="card-name"><?php echo htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="card-service"><?php echo htmlspecialchars(str_replace('_', ' ', $row['servizio']), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="card-tel"><a href="tel:<?php echo htmlspecialchars($row['telefono'], ENT_QUOTES, 'UTF-8'); ?>"><i class='bx bxs-phone'></i> <?php echo htmlspecialchars($row['telefono'], ENT_QUOTES, 'UTF-8'); ?></a></div>
                            <?php if ($row['status'] == 'in_attesa'): ?>
                                <span class="badge-attesa">DA CONFERMARE</span>
                                <button type="button" class="btn-gestisci btn-open-gestione" data-id="<?php echo (int)$row['id']; ?>" data-nome="<?php echo htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <i class='bx bx-cog'></i> Gestisci Richiesta
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="flex-grow:1;display:flex;align-items:center;justify-content:center;opacity:0.3;min-height:50px;">
                        <i class='bx bx-coffee' style="font-size:2rem;"></i>
                    </div>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>

    <div id="gestioneModal" class="modal-overlay">
        <div class="modal-box">
            <h3 style="color:#668073;margin-top:0;">Gestisci Prenotazione</h3>
            <p>Cosa vuoi fare con <strong id="pazienteNome">...</strong>?</p>
            <form method="POST" action="admin_gestisci.php" id="formGestione">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" id="gestioneId" value="">
                <button type="submit" name="azione" value="conferma_email" class="modal-btn btn-yes-mail">✅ Conferma e INVIA Email</button>
                <button type="submit" name="azione" value="conferma_no_email" class="modal-btn btn-no-mail">💾 Conferma (Solo DB, No Email)</button>
                <button type="submit" name="azione" value="rifiuta" class="modal-btn btn-reject" onclick="return confirm('Confermi di voler rifiutare la richiesta?');">❌ Rifiuta Richiesta</button>
            </form>
            <button type="button" onclick="chiudiGestione()" class="btn-close">Annulla e chiudi</button>
        </div>
    </div>
    <script>
        document.querySelectorAll('.btn-open-gestione').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.getAttribute('data-id');
                var nome = this.getAttribute('data-nome');
                apriGestione(id, nome);
            });
        });

        function apriGestione(id, nome) {
            var modal = document.getElementById('gestioneModal');
            if (modal) {
                modal.style.display = 'flex';
                document.getElementById('pazienteNome').textContent = nome;
                document.getElementById('gestioneId').value = id;
            }
        }

        function chiudiGestione() {
            var modal = document.getElementById('gestioneModal');
            if (modal) modal.style.display = 'none';
        }
    </script>
</body>
</html>
