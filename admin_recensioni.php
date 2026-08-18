<?php
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Aggiungi Recensione</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <style>
        /* Reset e Box Sizing per layout fluido */
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }

        body { 
            font-family: 'Montserrat', sans-serif; 
            background: #F0F2F5; 
            margin: 0;
            padding: 15px; /* Padding ridotto per schermi piccoli */
            color: #333;
        }

        .container { 
            max-width: 500px; 
            width: 100%; /* Si adatta alla larghezza dello schermo */
            margin: 0 auto; 
            background: white; 
            padding: 25px; 
            border-radius: 15px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
        }

        /* Label più leggibili */
        label {
            display: block;
            margin-top: 15px;
            font-weight: 600;
            font-size: 0.95rem;
            color: #444;
        }

        /* Input ottimizzati per il touch */
        input, textarea, select { 
            width: 100%; 
            padding: 12px; /* Area di tocco più ampia */
            margin: 8px 0; 
            border: 1px solid #ccc; 
            border-radius: 8px; 
            font-size: 16px; /* Previene lo zoom automatico su iOS */
            background: #fafafa;
        }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #668073;
            background: #fff;
        }

        .btn { 
            background: #668073; 
            color: white; 
            padding: 15px; /* Pulsante più alto per il dito */
            border: none; 
            width: 100%; 
            cursor: pointer; 
            font-weight: bold; 
            border-radius: 8px; 
            font-size: 1rem;
            margin-top: 20px;
            transition: background 0.2s;
        }

        .btn:hover {
            background: #556b60;
        }

        .top-link { 
            display:block; 
            margin-bottom:20px; 
            color:#668073; 
            text-decoration:none; 
            font-weight:bold; 
        }
    </style>
</head>
<body>
<?php include 'admin_topbar.php'; ?>
    <div class="container">
       <h2 style="margin-top:0; color:#1A2621;">Nuova Recensione</h2>
        <form action="salva_recensione.php" method="POST">
            <label>Nome Cliente</label>
            <input type="text" name="nome" required placeholder="Es. Laura B.">
            
            <label>Testo Recensione</label>
            <textarea name="testo" rows="5" required placeholder="Scrivi qui..."></textarea>
            
            <label>Voto</label>
            <select name="voto">
                <option value="5">5 Stelle</option>
                <option value="4">4 Stelle</option>
                <option value="3">3 Stelle</option>
		<option value="2">2 Stelle</option>
                <option value="1">1 Stelle</option>
            </select>
            
            <button type="submit" class="btn">Salva Recensione</button>
        </form>
    </div>
</body>
</html>