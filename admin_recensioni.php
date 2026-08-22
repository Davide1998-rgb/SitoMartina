<?php
require_once 'security.php';
require_admin_login();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="icon" type="image/png" href="img/logo.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Aggiungi Recensione</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }

        body { 
            font-family: 'Montserrat', sans-serif; 
            background: #F0F2F5; 
            margin: 0;
            padding: 15px;
            color: #333;
        }

        .container { 
            max-width: 500px; 
            width: 100%; 
            margin: 0 auto; 
            background: white; 
            padding: 25px; 
            border-radius: 15px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: 600;
            font-size: 0.95rem;
            color: #444;
        }

        input, textarea, select { 
            width: 100%; 
            padding: 12px; 
            margin: 8px 0; 
            border: 1px solid #ccc; 
            border-radius: 8px; 
            font-size: 16px; 
            background: #fafafa;
            box-sizing: border-box;
            font-family: inherit;
        }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #668073;
            background: #fff;
        }

        .checkbox-row { display:flex; align-items:center; gap:8px; margin-top:15px; color:#444; font-size:0.9rem; }
        .checkbox-row input { width:auto; margin:0; }

        .btn { 
            background: #668073; 
            color: white; 
            padding: 15px; 
            border: none; 
            width: 100%; 
            cursor: pointer; 
            font-weight: bold; 
            border-radius: 8px; 
            font-size: 1rem; 
            margin-top: 20px; 
            transition: background 0.2s;
            font-family: inherit;
        }

        .btn:hover {
            background: #556b60;
        }
    </style>
</head>
<body>
<?php include 'admin_topbar.php'; ?>
    <div class="container">
       <h2 style="margin-top:0; color:#1A2621;">Nuova Recensione</h2>
        <p style="color:#666; font-size:0.9rem; line-height:1.5;">
            La recensione verrà pubblicata subito e inserita direttamente nella sezione recensioni della home.
        </p>
        <form action="salva_recensione.php" method="POST">
            <?php echo csrf_field(); ?>
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

            <label class="checkbox-row">
                <input type="checkbox" name="fonte" value="google">
                Recensione da Google
            </label>
            
            <button type="submit" class="btn">Salva Recensione</button>
        </form>
    </div>
</body>
</html>