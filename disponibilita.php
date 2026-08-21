<?php
// Regole centralizzate per bloccare date e fasce orarie del calendario pubblico.

function disponibilita_assicura_tabella(mysqli $conn): void {
    $conn->query(
        "CREATE TABLE IF NOT EXISTS disponibilita_blocchi (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            titolo VARCHAR(150) NOT NULL,
            tipo ENUM('giorno','periodo','fascia','ricorrente') NOT NULL,
            data_inizio DATE NOT NULL,
            data_fine DATE NULL,
            ora_inizio TIME NULL,
            ora_fine TIME NULL,
            giorni_settimana VARCHAR(20) NULL,
            attivo TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_disponibilita_date (data_inizio, data_fine, attivo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function disponibilita_data_applicabile(array $regola, string $data): bool {
    if ((int)$regola['attivo'] !== 1 || $data < $regola['data_inizio']) {
        return false;
    }

    if ($regola['data_fine'] !== null && $regola['data_fine'] !== '' && $data > $regola['data_fine']) {
        return false;
    }

    if ($regola['tipo'] === 'ricorrente') {
        $giorno = (int)date('N', strtotime($data));
        $giorni = array_map('intval', array_filter(explode(',', (string)$regola['giorni_settimana'])));
        return in_array($giorno, $giorni, true);
    }

    return true;
}

function disponibilita_regole_data(mysqli $conn, string $data): array {
    disponibilita_assicura_tabella($conn);
    $stmt = $conn->prepare(
        "SELECT * FROM disponibilita_blocchi
         WHERE attivo = 1 AND data_inizio <= ?
         AND (data_fine IS NULL OR data_fine >= ?)
         ORDER BY id ASC"
    );
    $stmt->bind_param('ss', $data, $data);
    $stmt->execute();
    $result = $stmt->get_result();
    $regole = [];
    while ($row = $result->fetch_assoc()) {
        if (disponibilita_data_applicabile($row, $data)) {
            $regole[] = $row;
        }
    }
    $stmt->close();
    return $regole;
}

function disponibilita_giorno_bloccato(mysqli $conn, string $data): bool {
    foreach (disponibilita_regole_data($conn, $data) as $regola) {
        if (empty($regola['ora_inizio']) || empty($regola['ora_fine'])) {
            return true;
        }
    }
    return false;
}

function disponibilita_fascia_bloccata(mysqli $conn, string $data, string $oraInizio, string $oraFine): bool {
    $inizio = strtotime($data . ' ' . $oraInizio);
    $fine = strtotime($data . ' ' . $oraFine);
    foreach (disponibilita_regole_data($conn, $data) as $regola) {
        if (empty($regola['ora_inizio']) || empty($regola['ora_fine'])) {
            return true;
        }
        $bloccoInizio = strtotime($data . ' ' . $regola['ora_inizio']);
        $bloccoFine = strtotime($data . ' ' . $regola['ora_fine']);
        if ($inizio < $bloccoFine && $fine > $bloccoInizio) {
            return true;
        }
    }
    return false;
}
?>
