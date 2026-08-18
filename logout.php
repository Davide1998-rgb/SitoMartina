<?php
session_start();
session_destroy(); // FIX: rimossa session_unset() ridondante
header("Location: login.php");
exit;
?>
