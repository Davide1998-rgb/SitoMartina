<?php
require_once 'security.php';
require_admin_login();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione clienti - Dashboard</title>
    <link rel="stylesheet" href="admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        body { padding:20px; }
        .management-page { max-width:900px; margin:0 auto; }
        .management-intro { margin-bottom:1.5rem; }
        .management-intro h1 { margin:0 0 .4rem; }
        .management-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:1rem; }
        .management-card { display:flex; flex-direction:column; gap:.65rem; padding:1.5rem; border:1px solid var(--admin-border); border-radius:var(--admin-radius); background:#fff; box-shadow:var(--admin-shadow); text-decoration:none; color:var(--admin-ink); font-family:'Montserrat', sans-serif; }
        .management-card i { font-size:2.2rem; color:var(--admin-green); }
        .management-card h2 { margin:0; font-size:1.15rem; font-family:'Playfair Display', Georgia, serif; }
        .management-card p { margin:0; color:var(--admin-muted); font-size:.9rem; flex:1; font-family:'Montserrat', sans-serif; }
        .management-card span { color:var(--admin-green); font-weight:700; }
        @media (max-width:650px) { .management-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<?php include 'admin_topbar.php'; ?>
<main class="management-page">
    <div class="management-intro">
        <h1>Gestione clienti</h1>
        <p>Inserisci appuntamenti, modera le recensioni e invia richieste ai pazienti dalla stessa area.</p>
    </div>
    <div class="management-grid">
        <a class="management-card" href="admin_manuale.php"><i class="bx bx-plus-medical"></i><h2>Nuovo appuntamento</h2><p>Inserisci manualmente un appuntamento preso al telefono o in studio.</p></a>
        <a class="management-card" href="admin_recensioni.php"><i class="bx bx-star"></i><h2>Inserisci recensione</h2><p>Aggiungi una testimonianza già autorizzata.</p></a>
        <a class="management-card" href="admin_recensioni_hub.php"><i class="bx bx-paper-plane"></i><h2>Gestisci recensioni</h2><p>Approva, elimina e invia richieste di recensione automatiche o manuali.</p></a>
    </div>
</main>
</body>
</html>
