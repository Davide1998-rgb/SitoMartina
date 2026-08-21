<?php
require_once 'security.php';
require_admin_login();
include 'db_connect.php';

$sql = "SELECT DISTINCT email, nome FROM prenotazioni WHERE email != '' ORDER BY nome ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="icon" type="image/png" href="img/logo.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Invia Dieta</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <style>
        /* Reset e base per mobile */
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        
        body { 
            font-family: 'Montserrat', sans-serif; 
            background: #F0F2F5; 
            margin: 0;
            padding: 15px; /* Padding ridotto per schermi piccoli */
            color: #333;
        }

        .container { 
            max-width: 600px; 
            width: 100%; /* Fluido */
            margin: 0 auto; 
            background: white; 
            padding: 25px; 
            border-radius: 15px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
        }

        .top-link { 
            display: block; 
            margin-bottom: 20px; 
            color: #668073; 
            text-decoration: none; 
            font-weight: bold; 
        }

        h2 { 
            color: #668073; 
            margin-top: 0; 
            font-size: 1.5rem; /* Dimensione controllata */
        }
        
        label { 
            font-weight: bold; 
            display: block; 
            margin-top: 20px; 
            margin-bottom: 8px; 
            color: #333; 
            font-size: 0.95rem;
        }

        /* Input ottimizzati per touch */
        input[type="text"], 
        input[type="email"], 
        select, 
        textarea { 
            width: 100%; 
            padding: 14px; /* Area di tocco più ampia */
            border: 1px solid #ccc; 
            border-radius: 8px; 
            font-family: inherit; 
            font-size: 16px; /* Previene lo zoom su iOS */
            background: #fafafa;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #668073;
            background: #fff;
        }
        
        input[type="file"] { 
            background: #f9f9f9; 
            padding: 15px; 
            border: 2px dashed #668073; 
            border-radius: 8px; 
            width: 100%; 
            font-size: 0.9rem;
        }

        .btn { 
            background: #668073; 
            color: white; 
            padding: 16px; 
            border: none; 
            width: 100%; 
            cursor: pointer; 
            font-weight: bold; 
            border-radius: 8px; 
            margin-top: 25px; 
            font-size: 1.1rem; 
            transition: background 0.2s;
        }
        .btn:hover { background: #556b60; }

        /* Selettore modalità responsive */
        .mode-selector { 
            display: flex; 
            flex-direction: column; /* Impilato su mobile di default o breakpoint */
            gap: 10px; 
            margin-bottom: 20px; 
            background: #e8f5e9; 
            padding: 15px; 
            border-radius: 10px; 
        }

        /* Su schermi più larghi (tablet in su) li rimettiamo affiancati */
        @media (min-width: 480px) {
            .mode-selector {
                flex-direction: row;
                gap: 20px;
            }
        }

        .mode-selector label { 
            margin: 0; 
            font-weight: normal; 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            padding: 5px;
        }

        /* Radio button più grande */
        .mode-selector input[type="radio"] {
            transform: scale(1.3);
            margin: 0;
        }
        
        .hidden { display: none; }
        
        .error-msg { 
            color: #d32f2f; 
            background: #ffebee; 
            padding: 10px; 
            border-radius: 5px; 
            font-size: 0.9rem; 
            display: none; 
            margin-top: 10px; 
            font-weight: bold; 
            border-left: 4px solid #d32f2f;
        }
    </style>
</head>
<body>
<?php include 'admin_topbar.php'; ?>
<div class="container">
   
    <h2>Invia Piano Nutrizionale</h2>
    <p style="color:#666; font-size:0.95rem; line-height: 1.5;">È obbligatorio allegare il file <strong>PDF</strong> della dieta.</p>

    <form action="processa_invia_dieta.php" method="POST" enctype="multipart/form-data" onsubmit="return gestisciInvio(event)">
        <?php echo csrf_field(); ?>

        <div class="mode-selector">
            <label>
                <input type="radio" name="modalita" value="elenco" checked onclick="toggleMode('elenco')">
                Seleziona da Elenco
            </label>
            <label>
                <input type="radio" name="modalita" value="manuale" onclick="toggleMode('manuale')">
                Inserisci Manuale
            </label>
        </div>

        <div id="box-elenco">
            <label>Scegli Paziente:</label>
            <select name="email_elenco">
                <option value="">-- Seleziona un paziente --</option>
                <?php 
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $email_option = htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8');
                        $nome_option = htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8');
                        echo "<option value='{$email_option}|{$nome_option}'>{$nome_option} ({$email_option})</option>";
                    }
                }
                ?>
            </select>
        </div>

        <div id="box-manuale" class="hidden">
            <label>Nome Paziente:</label>
            <input type="text" name="nome_manuale" placeholder="Es. Marco Rossi">
            <label>Email Destinatario:</label>
            <input type="email" name="email_manuale" placeholder="email@esempio.com">
        </div>

        <label>Allega File (Obbligatorio PDF):</label>
        <input type="file" name="file_dieta" id="fileInput" required accept="application/pdf, .pdf">
        <div id="fileError" class="error-msg">⚠️ Errore: Devi caricare un file PDF!</div>

        <label>Oggetto della mail:</label>
        <input type="text" name="oggetto" placeholder="Il tuo Piano Nutrizionale - Dott.ssa Violo">

        <label>Messaggio (Opzionale):</label>
        <textarea name="messaggio" rows="4" placeholder="Ciao, ecco il tuo piano nutrizionale..."></textarea>

        <button type="submit" class="btn">Invia Dieta 📤</button>
    </form>
</div>

<div class="email-preview-overlay" id="email-preview" hidden>
    <div class="email-preview" role="dialog" aria-modal="true" aria-labelledby="email-preview-title">
        <div class="email-preview-header">
            <div>
                <span class="email-preview-eyebrow">Anteprima email</span>
                <h2 id="email-preview-title">Il tuo Piano Nutrizionale</h2>
            </div>
            <button type="button" class="email-preview-close" onclick="chiudiAnteprima()" aria-label="Chiudi anteprima">&times;</button>
        </div>

        <div class="email-preview-meta">
            <div><strong>Destinatario</strong><span id="preview-recipient">-</span></div>
            <div><strong>Allegato</strong><span id="preview-file">-</span></div>
            <div><strong>Oggetto</strong><span id="preview-subject">-</span></div>
        </div>

        <div class="email-preview-body">
            <p class="email-preview-brand">Dott.ssa Martina Violo</p>
            <h3 id="preview-greeting">Ciao!</h3>
            <p class="email-preview-lead">Ecco il tuo piano nutrizionale.</p>
            <div class="email-preview-message" id="preview-message" hidden></div>
            <div class="email-preview-attachment">
                <strong>Allegato presente</strong>
                <span>La dieta verrà inviata in formato PDF.</span>
            </div>
            <p class="email-preview-signature">Con cura,<br>Dott.ssa Martina Violo</p>
        </div>

        <div class="email-preview-actions">
            <button type="button" class="btn-no-mail" onclick="chiudiAnteprima()">Modifica</button>
            <button type="button" class="btn" onclick="confermaInvio()">Conferma e invia</button>
        </div>
    </div>
</div>

<script>
    function toggleMode(mode) {
        if(mode === 'elenco') {
            document.getElementById('box-elenco').classList.remove('hidden');
            document.getElementById('box-manuale').classList.add('hidden');
        } else {
            document.getElementById('box-elenco').classList.add('hidden');
            document.getElementById('box-manuale').classList.remove('hidden');
        }
    }

    function validaForm() {
        var fileInput = document.getElementById('fileInput');
        var filePath = fileInput.value;
        var allowedExtensions = /(\.pdf)$/i;
        var errorDiv = document.getElementById('fileError');

        // 1. Controllo se il campo è vuoto (sicurezza extra oltre al 'required')
        if (fileInput.files.length === 0) {
            errorDiv.innerHTML = "⚠️ Errore: Nessun file selezionato.";
            errorDiv.style.display = 'block';
            return false;
        }

        // 2. Controllo estensione
        if (!allowedExtensions.exec(filePath)) {
            errorDiv.innerHTML = "⚠️ Errore: Il file deve essere un PDF.";
            errorDiv.style.display = 'block';
            fileInput.value = '';
            return false;
        } 
        
        errorDiv.style.display = 'none';
        return true;
    }

    function gestisciInvio(event) {
        var form = event.target;
        if (form.dataset.confermato === '1') return true;
        event.preventDefault();

        if (!validaForm()) return false;
        mostraAnteprima();
        return false;
    }

    function mostraAnteprima() {
        var form = document.querySelector('form');
        var modalita = form.querySelector('input[name="modalita"]:checked').value;
        var nome = '';
        var email = '';

        if (modalita === 'elenco') {
            var selezione = form.querySelector('select[name="email_elenco"]');
            if (selezione.value) {
                var dati = selezione.value.split('|');
                email = dati.shift() || '';
                nome = dati.join('|');
            }
        } else {
            nome = form.querySelector('[name="nome_manuale"]').value.trim();
            email = form.querySelector('[name="email_manuale"]').value.trim();
        }

        if (!nome || !email) {
            alert('Inserisci nome ed email del destinatario prima di continuare.');
            return;
        }

        var messaggio = form.querySelector('[name="messaggio"]').value.trim();
        var oggetto = form.querySelector('[name="oggetto"]').value.trim();
        var file = document.getElementById('fileInput').files[0];
        document.getElementById('preview-recipient').textContent = nome + ' <' + email + '>';
        document.getElementById('preview-file').textContent = file ? file.name : '-';
        document.getElementById('preview-subject').textContent = oggetto || 'Il tuo Piano Nutrizionale - Dott.ssa Violo';
        document.getElementById('preview-greeting').textContent = 'Ciao ' + nome + '!';

        var messageBox = document.getElementById('preview-message');
        messageBox.textContent = messaggio;
        messageBox.hidden = !messaggio;
        document.getElementById('email-preview').hidden = false;
    }

    function chiudiAnteprima() {
        document.getElementById('email-preview').hidden = true;
    }

    function confermaInvio() {
        var form = document.querySelector('form');
        form.dataset.confermato = '1';
        form.submit();
    }
</script>

</body>
</html>