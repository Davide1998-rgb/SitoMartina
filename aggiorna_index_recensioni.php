<?php
function aggiornaIndexRecensioni(mysqli $conn): bool
{
    $indexPath = __DIR__ . DIRECTORY_SEPARATOR . 'index.html';
    $index = file_get_contents($indexPath);
    if ($index === false) {
        return false;
    }

    $result = $conn->query(
        "SELECT nome, testo, voto, fonte FROM recensioni WHERE approvata = 1 ORDER BY id DESC"
    );
    if ($result === false) {
        return false;
    }

    $cards = '';
    while ($review = $result->fetch_assoc()) {
        $nome = htmlspecialchars($review['nome'], ENT_QUOTES, 'UTF-8');
        $testo = nl2br(htmlspecialchars($review['testo'], ENT_QUOTES, 'UTF-8'));
        $voto = max(1, min(5, (int)$review['voto']));
        $daGoogle = $review['fonte'] === 'google';
        $iniziale = htmlspecialchars(
            function_exists('mb_substr') ? mb_substr($review['nome'], 0, 1, 'UTF-8') : substr($review['nome'], 0, 1),
            ENT_QUOTES,
            'UTF-8'
        );
        $stelle = str_repeat('&#9733;', $voto) . str_repeat('<span style="color:#ddd">&#9734;</span>', 5 - $voto);

        $cards .= "            <article class=\"review-card\">\n"
            . "                <div class=\"review-quote\">&quot;</div>\n"
            . "                <p class=\"review-text\">{$testo}</p>\n"
            . "                <div class=\"review-author\">\n"
            . "                    <div class=\"author-avatar\">{$iniziale}</div>\n"
            . "                    <div class=\"author-info\">\n"
            . "                        <h4>{$nome}</h4>\n"
            . "                        <div class=\"stars\" aria-label=\"{$voto} stelle su 5\">{$stelle}</div>\n"
            . ($daGoogle
                ? "                        <div class=\"review-source review-source-google\"><img src=\"https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg\" alt=\"Google\"> Recensione da Google</div>\n"
                : "                        <div class=\"review-source\">Recensione verificata</div>\n")
            . "                    </div>\n"
            . "                </div>\n"
            . "            </article>\n";
    }

    if ($cards === '') {
        $cards = "            <p style=\"text-align:center;width:100%;color:#888;\">Nessuna recensione ancora.</p>\n";
    }

    $startMarker = '<!-- RECENSIONI_STATICHE_INIZIO -->';
    $endMarker = '<!-- RECENSIONI_STATICHE_FINE -->';
    $pattern = '/' . preg_quote($startMarker, '/') . '.*?' . preg_quote($endMarker, '/') . '/s';
    $replacement = $startMarker . "\n" . $cards . '        ' . $endMarker;
    $updatedIndex = preg_replace($pattern, $replacement, $index, 1, $count);

    return $count === 1 && $updatedIndex !== null
        && file_put_contents($indexPath, $updatedIndex, LOCK_EX) !== false;
}