<?php
require_once 'security.php';
start_secure_session();

if (isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true) {
    header("Location: dashboard.php");
    exit;
}

require_once 'db_connect.php';
if ($conn->connect_error) { die("Errore connessione DB"); }

$error = "";
$rateLimitStatus = check_login_rate_limit();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!$rateLimitStatus['allowed']) {
        $error = $rateLimitStatus['message'];
    } elseif (!verify_csrf_token()) {
        $error = "Sessione scaduta. Ricarica la pagina e riprova.";
    } else {
        $password_inserita = $_POST['password'] ?? '';

        $stmt = $conn->prepare("SELECT password FROM admin_users LIMIT 1");
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $hash_salvato = $row['password'];

            if (password_verify($password_inserita, $hash_salvato)) {
                reset_login_attempts();
                session_regenerate_id(true);
                $_SESSION['admin_logged'] = true;
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $stmt->close();
                $conn->close();
                header("Location: dashboard.php");
                exit;
            } else {
                record_failed_login();
                usleep(400000); // Ritardo anti-brute-force
                $rateCheckAfter = check_login_rate_limit();
                $error = $rateCheckAfter['allowed'] ? "Password errata." : $rateCheckAfter['message'];
            }
        } else {
            $error = "Nessuna password impostata nel sistema.";
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="icon" type="image/png" href="img/logo.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dott.ssa Violo</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">

    <style>
        body { 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            background-color: #FBF3E4; 
            margin: 0;
        }
        
        .login-box { 
            background: white; 
            padding: 40px; 
            border-radius: 15px; 
            text-align: center; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 90%;          
            max-width: 400px;
        }

        input {
            width: 100%;
            padding: 12px;
            margin: 15px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2 style="color:#668073;">Bentornata</h2>
        <p style="font-size:0.9rem; color:#666;">Inserisci la password per accedere.</p>
        
        <?php if($error): ?>
            <p style='color:red; font-weight:bold; background:#ffe6e6; padding:10px; border-radius:5px;'><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        
        <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="password" name="password" placeholder="••••••••" required autofocus>
            <button type="submit" class="btn" style="width:100%;">Accedi</button>
        </form>
        
        <br>
        <a href="index.html" style="color:#668073; text-decoration:none; font-size:0.9rem;">← Torna al sito</a>
    </div>
</body>
</html>