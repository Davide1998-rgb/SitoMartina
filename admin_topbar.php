<?php // admin_topbar.php — include Boxicons autonomamente, non dipende dalla pagina padre ?>
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<style>
    .admin-topbar { background:#ffffff; padding:15px 30px; margin:-20px 0 30px; border-bottom:1px solid #e0e0e0; display:flex; justify-content:space-between; align-items:center; box-shadow:0 2px 5px rgba(0,0,0,0.03); }
    .btn-dash-back { text-decoration:none; color:#668073; font-weight:bold; font-size:0.95rem; display:flex; align-items:center; gap:8px; transition:color 0.3s; }
    .btn-dash-back:hover { color:#4a5e54; }
    .btn-dash-back i { font-size:1.2rem; }
    .btn-logout-mini { text-decoration:none; color:#d9534f; font-size:0.85rem; border:1px solid #d9534f; padding:5px 12px; border-radius:20px; transition:all 0.3s; }
    .btn-logout-mini:hover { background-color:#d9534f; color:white; }
</style>
<link rel="stylesheet" href="admin.css">
<div class="admin-topbar">
    <a href="dashboard.php" class="btn-dash-back"><i class='bx bxs-dashboard'></i> Torna alla Dashboard</a>
    <a href="logout.php" class="btn-logout-mini"><i class='bx bx-log-out'></i> Esci</a>
</div>
