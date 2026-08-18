<?php
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) { header("Location: login.php"); exit; }

// Percorso coerente con api_statistiche.php (fuori dalla webroot)
define('STATS_DIR', dirname(__DIR__) . '/private_data/');

$mese_selezionato   = isset($_GET['mese']) ? preg_replace('/[^0-9_]/', '', $_GET['mese']) : date('Y_m');
$file_stats         = STATS_DIR . "stats_" . $mese_selezionato . ".json";
$anno_corrente      = (int)substr($mese_selezionato, 0, 4);
$num_mese_corrente  = (int)substr($mese_selezionato, 5, 2);

// FIX: navigazione mesi precedente/successivo (prima mancava)
$dt_corrente = new DateTime("$anno_corrente-$num_mese_corrente-01");
$dt_prec     = clone $dt_corrente; $dt_prec->modify('-1 month');
$dt_succ     = clone $dt_corrente; $dt_succ->modify('+1 month');
$slug_prec   = $dt_prec->format('Y_m');
$slug_succ   = $dt_succ->format('Y_m');
$slug_oggi   = date('Y_m');

$mesi_ita = ["01"=>"Gennaio","02"=>"Febbraio","03"=>"Marzo","04"=>"Aprile","05"=>"Maggio","06"=>"Giugno","07"=>"Luglio","08"=>"Agosto","09"=>"Settembre","10"=>"Ottobre","11"=>"Novembre","12"=>"Dicembre"];
$num_mese_pad       = str_pad($num_mese_corrente, 2, '0', STR_PAD_LEFT);
$nome_mese_corrente = $mesi_ita[$num_mese_pad] . " " . $anno_corrente;

$dati = (file_exists($file_stats)) ? json_decode(file_get_contents($file_stats), true) : [];
if (!is_array($dati)) $dati = [];

$tot_visite = $tot_bot = $tot_tempo = $tot_prenotazioni = 0;
$chart_labels = $chart_visite = $chart_prenotazioni = [];
$giorni_nel_mese = cal_days_in_month(CAL_GREGORIAN, $num_mese_corrente, $anno_corrente);

for ($i = 1; $i <= $giorni_nel_mese; $i++) {
    $gk = str_pad($i, 2, '0', STR_PAD_LEFT);
    $chart_labels[] = $gk;
    if (isset($dati[$gk])) {
        $g = $dati[$gk];
        $tot_visite += $g['visite_umane']; $tot_bot += $g['visite_bot'];
        $tot_tempo += $g['tempo_totale_secondi']; $tot_prenotazioni += $g['prenotazioni'] ?? 0;
        $chart_visite[] = $g['visite_umane']; $chart_prenotazioni[] = $g['prenotazioni'] ?? 0;
    } else { $chart_visite[] = 0; $chart_prenotazioni[] = 0; }
}

$tempo_medio_sec   = $tot_visite > 0 ? round($tot_tempo / $tot_visite) : 0;
$tempo_formattato  = floor($tempo_medio_sec / 60) . "m " . ($tempo_medio_sec % 60) . "s";
$tasso_conversione = $tot_visite > 0 ? round(($tot_prenotazioni / $tot_visite) * 100, 1) : 0;
?>
<!DOCTYPE html><html lang="it"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Statistiche Sito</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body{font-family:'Montserrat',sans-serif;background:#F0F2F5;margin:0;padding:20px;color:#333;}
.container{max-width:1000px;margin:0 auto;}
.month-nav{display:flex;justify-content:space-between;align-items:center;background:white;padding:15px 20px;border-radius:15px;box-shadow:0 4px 15px rgba(0,0,0,0.05);margin-bottom:20px;}
.month-nav h1{margin:0;color:#668073;font-size:1.5rem;flex:1;text-align:center;}
.btn-nav{background:#f4f4f4;border:none;width:40px;height:40px;border-radius:50%;cursor:pointer;font-size:1.2rem;color:#555;display:flex;align-items:center;justify-content:center;text-decoration:none;transition:0.3s;}
.btn-nav:hover{background:#668073;color:white;}
.btn-oggi{background:#668073;color:white;padding:6px 14px;border-radius:20px;text-decoration:none;font-size:0.85rem;font-weight:bold;}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;margin-bottom:30px;}
.stat-card{background:white;padding:25px;border-radius:15px;box-shadow:0 5px 15px rgba(0,0,0,0.05);position:relative;overflow:hidden;}
.stat-card::before{content:'';position:absolute;left:0;top:0;bottom:0;width:5px;background:#668073;}
.stat-card.orange::before{background:#E67E22;} .stat-card.blue::before{background:#3498DB;} .stat-card.green::before{background:#27AE60;}
.stat-title{font-size:0.9rem;color:#666;font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:10px;}
.stat-value{font-size:2.2rem;font-weight:700;color:#1A2621;}
.stat-icon{position:absolute;right:20px;top:25px;font-size:3rem;color:rgba(102,128,115,0.1);}
.chart-container{background:white;padding:25px;border-radius:15px;box-shadow:0 5px 15px rgba(0,0,0,0.05);margin-bottom:30px;}
.table-container{background:white;padding:25px;border-radius:15px;box-shadow:0 5px 15px rgba(0,0,0,0.05);overflow-x:auto;}
table{width:100%;border-collapse:collapse;text-align:left;}
th,td{padding:12px 15px;border-bottom:1px solid #eee;}
th{background:#f9f9f9;color:#668073;font-weight:600;}
tr:hover{background:#fafafa;}
.badge-bot{font-size:0.75rem;background:#eee;padding:3px 8px;border-radius:20px;color:#888;}
.btn-back{display:inline-block;color:#668073;text-decoration:none;font-weight:600;margin-bottom:20px;}
</style></head><body>
<div class="container">
    <a href="dashboard.php" class="btn-back">← Torna alla Dashboard</a>

    <div class="month-nav">
        <a href="?mese=<?php echo $slug_prec; ?>" class="btn-nav"><i class='bx bx-chevron-left'></i></a>
        <h1><i class='bx bx-line-chart'></i> <?php echo $nome_mese_corrente; ?></h1>
        <a href="?mese=<?php echo $slug_succ; ?>" class="btn-nav"><i class='bx bx-chevron-right'></i></a>
    </div>
    <?php if ($mese_selezionato !== $slug_oggi): ?>
        <div style="text-align:center;margin-bottom:20px;">
            <a href="?mese=<?php echo $slug_oggi; ?>" class="btn-oggi">Torna al mese corrente</a>
        </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card"><i class='bx bx-user stat-icon'></i><div class="stat-title">Visite Umane</div><div class="stat-value"><?php echo $tot_visite; ?></div></div>
        <div class="stat-card blue"><i class='bx bx-time-five stat-icon'></i><div class="stat-title">Tempo Medio</div><div class="stat-value" style="font-size:1.8rem;"><?php echo $tempo_formattato; ?></div></div>
        <div class="stat-card orange"><i class='bx bx-calendar-check stat-icon'></i><div class="stat-title">Prenotazioni</div><div class="stat-value"><?php echo $tot_prenotazioni; ?></div></div>
        <div class="stat-card green"><i class='bx bx-target-lock stat-icon'></i><div class="stat-title">Tasso Conversione</div><div class="stat-value"><?php echo $tasso_conversione; ?>%</div></div>
    </div>

    <?php if (empty($dati)): ?>
        <div style="background:#fff3cd;color:#856404;padding:15px;border-radius:8px;border-left:5px solid #ffeeba;">
            <strong>Nessun dato!</strong> Non ci sono ancora visite registrate per questo mese.
        </div>
    <?php else: ?>
        <div class="chart-container">
            <h3 style="margin-top:0;color:#668073;">Andamento Visite Mensili</h3>
            <canvas id="graficoVisite" height="100"></canvas>
        </div>
        <div class="table-container">
            <h3 style="margin-top:0;color:#668073;">Dettaglio Giornaliero</h3>
            <table><thead><tr><th>Giorno</th><th>Visite Umane</th><th>Prenotazioni</th><th>Tempo Medio</th><th>Traffico Bot</th></tr></thead>
            <tbody>
            <?php for ($i = $giorni_nel_mese; $i >= 1; $i--):
                $gk = str_pad($i, 2, '0', STR_PAD_LEFT);
                if (!isset($dati[$gk])) continue;
                $g = $dati[$gk]; $vu = $g['visite_umane'];
                $tm = $vu > 0 ? round($g['tempo_totale_secondi'] / $vu) : 0;
                $tm_str = floor($tm/60) . "m " . ($tm%60) . "s";
            ?>
                <tr>
                    <td><strong><?php echo $gk . " " . $mesi_ita[$num_mese_pad]; ?></strong></td>
                    <td><?php echo $vu; ?></td>
                    <td><strong style="color:#E67E22;"><?php echo $g['prenotazioni'] ?? 0; ?></strong></td>
                    <td><?php echo $tm_str; ?></td>
                    <td><span class="badge-bot"><?php echo $g['visite_bot']; ?> Bot bloccati</span></td>
                </tr>
            <?php endfor; ?>
            </tbody></table>
        </div>
    <?php endif; ?>
</div>
<script>
const ctx = document.getElementById('graficoVisite');
if (ctx) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [
                {label:'Visite Umane', data:<?php echo json_encode($chart_visite); ?>, backgroundColor:'#668073', borderRadius:5},
                {label:'Prenotazioni', data:<?php echo json_encode($chart_prenotazioni); ?>, backgroundColor:'#E67E22', borderRadius:5}
            ]
        },
        options: {responsive:true, scales:{y:{beginAtZero:true,ticks:{stepSize:1}},x:{grid:{display:false}}}, plugins:{legend:{position:'top'}}}
    });
}
</script>
</body></html>
