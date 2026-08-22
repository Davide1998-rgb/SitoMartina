<?php
$servername = "localhost";
$username = "root";      // Cambia con il tuo username del server
$password = "";          // Cambia con la tua password del server
$dbname = "nutrizionista_db"; // Cambia con il nome del tuo database

// Creazione connessione
$conn = new mysqli($servername, $username, $password, $dbname, 3307);

// Controllo connessione
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Aggiornamento schema: aggiunge la modalita visita se manca.
$check_col = $conn->query("SHOW COLUMNS FROM prenotazioni LIKE 'modalita_visita'");
if ($check_col && $check_col->num_rows === 0) {
    $conn->query("ALTER TABLE prenotazioni ADD COLUMN modalita_visita ENUM('studio','online') NOT NULL DEFAULT 'studio' AFTER telefono");
}
?>