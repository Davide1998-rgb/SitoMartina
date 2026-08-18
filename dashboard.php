<?php
session_start();
// SE NON SEI LOGGATA, VIA AL LOGIN
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Dott.ssa Martina Violo</title>
    
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
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .admin-header h1 { margin: 0; font-family: 'Playfair Display', serif; font-size: 1.5rem; }
        
        .logout-btn {
            background: rgba(255,255,255,0.2); color: white; text-decoration: none;
            padding: 0.5rem 1rem; border-radius: 2rem; font-size: 0.9rem; transition: 0.3s;
        }
        .logout-btn:hover { background: white; color: #668073; }

        .dashboard-container { max-width: 1000px; margin: 3rem auto; padding: 0 1.5rem; }
        
        .welcome-text { text-align: center; margin-bottom: 3rem; }
        .welcome-text h2 { color: #1A2621; font-size: 2.2rem; margin-bottom: 0.5rem; font-family: 'Playfair Display', serif; }

        .admin-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 2rem; }

        .admin-card {
            background: white; padding: 2rem; border-radius: 1.5rem;
            text-align: center; text-decoration: none; color: #4a4a4a;
            transition: all 0.3s ease; box-shadow: 0 10px 20px rgba(102, 128, 115, 0.05);
            display: flex; flex-direction: column; align-items: center;
        }
        .admin-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(102, 128, 115, 0.15); }

        .card-icon {
            font-size: 3rem; color: #668073; margin-bottom: 1rem;
            background: #E8F5E9; width: 70px; height: 70px;
            display: flex; align-items: center; justify-content: center; border-radius: 50%;
        }
        .admin-card h3 { margin: 0 0 0.5rem 0; font-size: 1.2rem; color: #1A2621; font-weight: 600; }
        .admin-card p { margin: 0; font-size: 0.85rem; color: #888; }
    </style>
</head>
<body>

    <div class="admin-header">
        <h1>Admin Control Room</h1>
        <a href="logout.php" class="logout-btn"><i class='bx bx-log-out'></i> Esci</a>
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
                <div class="card-icon"><i class='bx bx-time-five'></i></div>
                <h3>Richieste in Attesa</h3>
                <p>Conferma con 1 Click & Mail</p>
            </a>

            <a href="admin_manuale.php" class="admin-card">
                <div class="card-icon"><i class='bx bx-plus-medical'></i></div>
                <h3>Nuovo Appuntamento</h3>
                <p>Inserimento manuale</p>
            </a>

            <a href="admin_calendario.php" class="admin-card">
                <div class="card-icon"><i class='bx bxs-cog'></i></div>
                <h3>Gestione Appuntamenti</h3>
            </a>

            <a href="admin_recensioni.php" class="admin-card">
                <div class="card-icon"><i class='bx bx-star'></i></div>
                <h3>Recensioni</h3>
                <p>Aggiungi testimonianza</p>
            </a>

            <a href="admin_recensioni_hub.php" class="admin-card">
                <div class="card-icon"><i class='bx bx-paper-plane'></i></div>
                <h3>Gestione Recensioni</h3>
                <p>Invii Auto & Manuali</p>
            </a>

            <a href="admin_invia_dieta.php" class="admin-card">
                <div class="card-icon"><i class='bx bx-file-blank'></i></div>
                <h3>Invia Dieta</h3>
                <p>Allega e spedisci piano</p>
            </a>

            <a href="index.html" class="admin-card" target="_blank">
                <div class="card-icon"><i class='bx bx-world'></i></div>
                <h3>Vedi Sito</h3>
                <p>Vai al sito online</p>
            </a>
        </div>
    </div>
</body>
</html>