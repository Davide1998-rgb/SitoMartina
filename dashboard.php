<?php
require_once 'security.php';
require_admin_login();


require_once 'db_connect.php';
$richieste_in_attesa = 0;
$conteggio_richieste = $conn->query("SELECT COUNT(*) AS totale FROM prenotazioni WHERE status = 'in_attesa'");
if ($conteggio_richieste) {
    $riga_conteggio = $conteggio_richieste->fetch_assoc();
    $richieste_in_attesa = (int)($riga_conteggio['totale'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Dashboard - Dott.ssa Martina Violo</title>
    <link rel="manifest" href="admin-manifest.json">
    <link rel="icon" type="image/png" href="img/logo.svg">
    <link rel="apple-touch-icon" href="img/logo.svg">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Dashboard">
    
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Pinyon+Script&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
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
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .admin-header h1 { margin: 0; font-family: 'Playfair Display', serif; font-size: 1.5rem; }
        
        .logout-btn {
            background: rgba(255,255,255,0.2); color: white; text-decoration: none;
            padding: 0.5rem 1rem; border-radius: 2rem; font-size: 0.9rem; transition: 0.3s;
        }
        .logout-btn:hover { background: white; color: #668073; }

        .admin-header-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .install-btn {
            background: transparent; color: white; border: 1px solid rgba(255,255,255,0.7);
            padding: 0.5rem 1rem; border-radius: 2rem; font: inherit; font-size: 0.85rem;
            cursor: pointer; transition: 0.3s;
        }
        .install-btn:hover { background: white; color: #668073; }

        .dashboard-container { max-width: 1000px; margin: 3rem auto; padding: 0 1.5rem; }
        
        .welcome-text { text-align: center; margin-bottom: 3rem; }
        .welcome-text h2 { color: #1A2621; font-size: 2.2rem; margin-bottom: 0.5rem; font-family: 'Playfair Display', serif; }

        .admin-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 2rem; }

        .admin-card {
            background: white; padding: 2rem; border-radius: 1.5rem;
            text-align: center; text-decoration: none; color: #4a4a4a;
            transition: all 0.3s ease; box-shadow: 0 10px 20px rgba(102, 128, 115, 0.05);
            display: flex; flex-direction: column; align-items: center;
            font-family: 'Montserrat', sans-serif;
        }
        .admin-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(102, 128, 115, 0.15); }

        .card-icon {
            font-size: 3rem; color: #668073; margin-bottom: 1rem;
            background: #E8F5E9; width: 70px; height: 70px;
            display: flex; align-items: center; justify-content: center; border-radius: 50%;
        }
        .admin-card h3 { margin: 0 0 0.5rem 0; font-size: 1.2rem; color: #1A2621; font-weight: 600; font-family: 'Playfair Display', serif; }
        .admin-card p { margin: 0; font-size: 0.85rem; color: #888; font-family: 'Montserrat', sans-serif; }

        @media (max-width: 480px) {
            .admin-header {
                padding: 1rem;
                gap: 0.75rem;
                flex-wrap: wrap;
            }
            .admin-header h1 { font-size: 1.25rem; }
            .admin-header-actions { width: 100%; justify-content: flex-end; }
            .dashboard-container {
                margin: 2rem auto;
                padding: 0 1rem;
            }
            .welcome-text { margin-bottom: 2rem; }
            .welcome-text h2 { font-size: 1.75rem; }
            .admin-grid { gap: 1rem; }
            .admin-card { padding: 1.5rem 1rem; }
        }
    </style>
    <link rel="stylesheet" href="admin.css">
</head>
<body>

    <div class="admin-header">
        <h1>Dashboard</h1>
        <div class="admin-header-actions">
            <button type="button" class="install-btn" id="install-app"><i class='bx bx-download'></i> Installa app</button>
            <button type="button" class="install-btn" id="enable-notifications"><i class='bx bx-bell'></i> Attiva notifiche</button>
            <a href="logout.php" class="logout-btn"><i class='bx bx-log-out'></i> Esci</a>
        </div>
    </div>

    <div class="dashboard-container">
        <div class="welcome-text">
            <h2>Bentornata, Martina</h2>
            <p>Seleziona un'attività per iniziare.</p>
        </div>

        <div class="admin-grid">
            <a href="admin_planner.php" class="admin-card">
                <div class="card-icon"><i class='bx bx-calendar'></i></div>
                <h3>Agenda</h3>
                <p>Visualizza calendario</p>
            </a>

            <!-- NUOVA CARD PER RICHIESTE IN ATTESA -->
            <a href="admin_richieste.php" class="admin-card">
                <?php if ($richieste_in_attesa > 0): ?>
                    <span class="pending-badge" aria-label="<?php echo $richieste_in_attesa; ?> richieste in attesa">
                        <?php echo $richieste_in_attesa; ?>
                    </span>
                <?php endif; ?>
                <div class="card-icon"><i class='bx bx-time-five'></i></div>
                <h3>Richieste in Attesa</h3>
                <p>Conferma con 1 Click & Mail</p>
            </a>

            <a href="admin_disponibilita.php" class="admin-card">
                <div class="card-icon"><i class='bx bx-calendar-exclamation'></i></div>
                <h3>Disponibilità Calendario</h3>
                <p>Blocca date, periodi e fasce orarie</p>
            </a>

            <a href="admin_gestione.php" class="admin-card">
                <div class="card-icon"><i class='bx bx-group'></i></div>
                <h3>Gestione Clienti</h3>
                <p>Appuntamenti manuali e recensioni</p>
            </a>

            <a href="admin_remind.php" class="admin-card">
                <div class="card-icon"><i class='bx bxl-whatsapp'></i></div>
                <h3>Remind appuntamento</h3>
                <p>Ricorda gli appuntamenti di domani</p>
            </a>

            <a href="admin_calendario.php" class="admin-card">
                <div class="card-icon"><i class='bx bxs-cog'></i></div>
                <h3>Gestione Appuntamenti</h3>
            </a>

            <a href="admin_invia_dieta.php" class="admin-card">
                <div class="card-icon"><i class='bx bx-file-blank'></i></div>
                <h3>Invia Dieta</h3>
                <p>Allega e spedisci piano</p>
            </a>

            <a href="admin_statistiche.php" class="admin-card">
                <div class="card-icon"><i class='bx bx-line-chart'></i></div>
                <h3>Statistiche</h3>
                <p>Visualizza dati e conversioni</p>
            </a>

            <a href="admin_export_prenotazioni.php" class="admin-card">
                <div class="card-icon"><i class='bx bx-download'></i></div>
                <h3>Esporta Prenotazioni</h3>
                <p>Scarica un backup CSV</p>
            </a>

            <a href="index.html" class="admin-card" target="_blank">
                <div class="card-icon"><i class='bx bx-world'></i></div>
                <h3>Vedi Sito</h3>
                <p>Vai al sito online</p>
            </a>
        </div>
    </div>

    <script>
        let installPrompt;
        const installButton = document.getElementById('install-app');

        window.addEventListener('beforeinstallprompt', event => {
            event.preventDefault();
            installPrompt = event;
        });

        installButton.addEventListener('click', async () => {
            if (installPrompt) {
                installPrompt.prompt();
                installPrompt = null;
                return;
            }

            alert('Su iPhone apri il menu Condividi di Safari e scegli "Aggiungi alla schermata Home".');
        });

        const notificationButton = document.getElementById('enable-notifications');
        const lastRequestKey = 'martina-admin-last-request-id';
        let serviceWorkerRegistration;

        async function enableNotifications() {
            const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

            if (!('Notification' in window)) {
                alert(isIos && !isStandalone
                    ? 'Su iPhone devi prima installare questa pagina: Safari > Condividi > Aggiungi alla schermata Home. Poi apri la PWA dalla nuova icona e riprova.'
                    : 'Le notifiche non sono supportate da questo browser o da questa versione di iOS.');
                return;
            }

            if (!('serviceWorker' in navigator)) {
                alert('Il browser non supporta il servizio necessario alle notifiche.');
                return;
            }

            const permission = await Notification.requestPermission();
            if (permission === 'granted') {
                notificationButton.innerHTML = "<i class='bx bx-bell'></i> Notifiche attive";
                notificationButton.disabled = true;
                checkNewRequests();
            } else {
                alert('Per ricevere gli avvisi, consenti le notifiche nelle impostazioni di Safari.');
            }
        }

        async function checkNewRequests() {
            try {
                const response = await fetch('api_check_richieste.php', { cache: 'no-store' });
                if (!response.ok) return;
                const data = await response.json();
                const latestId = Number(data.latest_id || 0);
                const savedId = Number(localStorage.getItem(lastRequestKey) || 0);

                if (!savedId) {
                    localStorage.setItem(lastRequestKey, String(latestId));
                    return;
                }

                if (latestId > savedId && Notification.permission === 'granted' && serviceWorkerRegistration) {
                    serviceWorkerRegistration.active.postMessage({
                        type: 'NUOVA_RICHIESTA',
                        id: latestId,
                        name: data.latest_name
                    });
                }
                if (latestId > savedId) localStorage.setItem(lastRequestKey, String(latestId));
            } catch (error) {}
        }

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('admin-service-worker.js').then(() => navigator.serviceWorker.ready).then(registration => {
                serviceWorkerRegistration = registration;
                checkNewRequests();
                window.setInterval(checkNewRequests, 30000);
            });
        }

        if ('Notification' in window && Notification.permission === 'granted') {
            notificationButton.innerHTML = "<i class='bx bx-bell'></i> Notifiche attive";
            notificationButton.disabled = true;
        }
        notificationButton.addEventListener('click', enableNotifications);
    </script>
</body>
</html>