<?php
// Configurazione (Porta 3307)
require_once 'db_connect.php';

// --- SCRIVI QUI LA PASSWORD CHE VUOI USARE ---
$password_da_usare = "martina123"; 
// ---------------------------------------------

// Creiamo l'hash sicuro (irreversibile)
$hash = password_hash($password_da_usare, PASSWORD_DEFAULT);

// Cancelliamo eventuali vecchie password e ne inseriamo una nuova pulita
$conn->query("TRUNCATE TABLE admin_users"); 
$sql = "INSERT INTO admin_users (username, password) VALUES ('admin', '$hash')";

if ($conn->query($sql) === TRUE) {
    echo "<h1>Password salvata e criptata!</h1>";
    echo "<p>Hash generato: $hash</p>";
    echo "<p>Ora puoi cancellare questo file e andare al login.</p>";
} else {
    echo "Errore: " . $conn->error;
}
?>