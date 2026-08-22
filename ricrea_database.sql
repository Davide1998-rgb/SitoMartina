-- ============================================================
-- Ricostruzione struttura database "nutrizionista_db"
-- Ricreata analizzando tutte le query presenti nel codice PHP
-- (il file nutrizionista_db_backup.sql originale era troncato/corrotto
--  e conteneva solo la tabella admin_users).
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Tabella: admin_users
-- Usata da login.php, setup_password.php
-- --------------------------------------------------------
DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabella: prenotazioni
-- Usata da prenota.php, admin_manuale.php, admin_planner.php,
-- admin_calendario.php, admin_richieste.php, admin_gestisci.php,
-- admin_azioni_calendario.php, admin_remind.php, admin_invia_dieta.php,
-- admin_recensioni_hub.php, cron_promemoria.php, conferma.php,
-- conferma_cliente.php, annulla_cliente.php, rifiuta.php,
-- api_check_orari.php, api_check_richieste.php, dashboard.php
-- --------------------------------------------------------
DROP TABLE IF EXISTS `prenotazioni`;
CREATE TABLE `prenotazioni` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL DEFAULT '',
  `telefono` varchar(30) NOT NULL,
  `modalita_visita` enum('studio','online') NOT NULL DEFAULT 'studio',
  `servizio` enum('prima_visita','controllo') NOT NULL DEFAULT 'controllo',
  `data_inizio` datetime NOT NULL,
  `data_fine` datetime NOT NULL,
  `status` enum('in_attesa','confermata','rifiutata','annullata') NOT NULL DEFAULT 'in_attesa',
  `conferma_cliente` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_prenotazioni_data` (`data_inizio`, `data_fine`),
  KEY `idx_prenotazioni_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabella: recensioni
-- Usata da salva_recensione.php, salva_recensione_pubblica.php,
-- admin_recensioni_hub.php, azione_recensione.php,
-- get_recensioni.php, aggiorna_index_recensioni.php
-- --------------------------------------------------------
DROP TABLE IF EXISTS `recensioni`;
CREATE TABLE `recensioni` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `testo` text NOT NULL,
  `voto` tinyint(1) NOT NULL,
  `fonte` enum('sito','google') NOT NULL DEFAULT 'sito',
  `approvata` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_recensioni_approvata` (`approvata`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabella: disponibilita_blocchi
-- Creata automaticamente da disponibilita.php (CREATE TABLE IF NOT EXISTS)
-- Usata anche da admin_disponibilita.php, api_check_orari.php, prenota.php
-- --------------------------------------------------------
DROP TABLE IF EXISTS `disponibilita_blocchi`;
CREATE TABLE `disponibilita_blocchi` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `titolo` VARCHAR(150) NOT NULL,
  `tipo` ENUM('giorno','periodo','fascia','ricorrente') NOT NULL,
  `data_inizio` DATE NOT NULL,
  `data_fine` DATE NULL,
  `ora_inizio` TIME NULL,
  `ora_fine` TIME NULL,
  `giorni_settimana` VARCHAR(20) NULL,
  `attivo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_disponibilita_date (data_inizio, data_fine, attivo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Utente admin di default (password temporanea)
-- IMPORTANTE: dopo l'import, apri setup_password.php per generare
-- una password sicura con hash (poi cancella quel file).
-- --------------------------------------------------------
INSERT INTO `admin_users` (`username`, `password`) VALUES ('admin', '');
