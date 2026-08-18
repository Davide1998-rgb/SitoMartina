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
?>