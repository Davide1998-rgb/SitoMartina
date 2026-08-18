<?php
// FILE: config_mail.php
// Unico punto di configurazione per mail e URL base.
// Modifica BASE_URL quando vai online.

define('MAIL_HOST',      'smtp.gmail.com');
define('MAIL_USER',      'tuamail@gmail.com');   // <-- sostituisci con la tua mail
define('MAIL_PASS',      'xxxx xxxx xxxx xxxx'); // <-- sostituisci con la tua App Password
define('MAIL_PORT',      587);
define('MAIL_FROM_NAME', 'Dott.ssa Martina Violo');

// URL base del sito — usato in tutti i link delle email
// In locale:  'http://localhost/Sito_Martina'
// Online:     'https://www.tuosito.it'
define('BASE_URL', 'http://localhost/Sito_Martina');
?>
