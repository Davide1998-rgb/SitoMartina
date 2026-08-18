<?php
session_start();

if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'db_connect.php';

if (isset($_GET['id']) && isset($_GET['action'])) {
    $id     = intval($_GET['id']);
    $action = $_GET['action'];

    if (!in_array($action, ['approve', 'delete'])) {
        die("Azione non valida.");
    }

    if ($action == 'approve') {
        $sql  = "UPDATE recensioni SET approvata = 1 WHERE id = $id";
        $msg  = "Recensione pubblicata con successo! Ora è visibile sul sito.";
        $color = "green";
    } else {
        $sql  = "DELETE FROM recensioni WHERE id = $id";
        $msg  = "Recensione eliminata definitivamente.";
        $color = "red";
    }

    if ($conn->query($sql) === TRUE) {
        echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>
                <h1 style='color:$color;'>Eseguito!</h1>
                <p>$msg</p>
                <a href='dashboard.php'>Vai alla Dashboard</a>
              </div>";
    } else {
        echo "Errore: " . $conn->error;
    }
} else {
    die("Parametri mancanti.");
}

$conn->close();
?>
