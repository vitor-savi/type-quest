<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

session_name(SESSION_NAME);
session_start();

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autenticado.']);
    exit;
}

$nivel      = max(1, min(NIVEL_MAXIMO_JOGADOR, (int)($_GET['nivel']     ?? 1)));
$quantidade = max(1, min(20,                  (int)($_GET['quantidade'] ?? PALAVRAS_POR_PARTIDA)));

// A dificuldade das palavras é proporcional ao nível (1-5 distribuídos nos 10 níveis)
$dificuldade = (int)ceil($nivel / 2);

try {
    $pdo = getDB();

    // Palavras aleatórias da dificuldade correspondente
    $stmtPalavras = $pdo->prepare(
        'SELECT idPalavra, texto, dificuldade
         FROM PALAVRA
         WHERE dificuldade = :dif
         ORDER BY RAND()
         LIMIT :qtd'
    );
    $stmtPalavras->bindValue(':dif', $dificuldade, PDO::PARAM_INT);
    $stmtPalavras->bindValue(':qtd', $quantidade,  PDO::PARAM_INT);
    $stmtPalavras->execute();
    $palavras = $stmtPalavras->fetchAll();

    // Se não há palavras suficientes nesta dificuldade, completa com outras
    if (count($palavras) < $quantidade) {
        $faltam = $quantidade - count($palavras);
        $idsJa = array_column($palavras, 'idPalavra') ?: [0];
        $placeholders = implode(',', array_fill(0, count($idsJa), '?'));

        $stmtExtra = $pdo->prepare(
            "SELECT idPalavra, texto, dificuldade
             FROM PALAVRA
             WHERE idPalavra NOT IN ($placeholders)
             ORDER BY RAND()
             LIMIT $faltam"
        );
        $stmtExtra->execute($idsJa);
        $palavras = array_merge($palavras, $stmtExtra->fetchAll());
    }

    // Inimigo baseado no nível (pega o mais forte que o jogador consegue enfrentar)
    $stmtInimigo = $pdo->prepare(
        'SELECT idInimigo, nome, sprite, hp, dano_base, tipo
         FROM INIMIGO
         WHERE nivel_minimo <= :nivel
         ORDER BY nivel_minimo DESC, RAND()
         LIMIT 1'
    );
    $stmtInimigo->execute([':nivel' => $nivel]);
    $inimigo = $stmtInimigo->fetch();

    if (!$inimigo) {
        // Fallback: inimigo mais fraco disponível
        $stmtInimigo2 = $pdo->query('SELECT idInimigo, nome, sprite, hp, dano_base, tipo FROM INIMIGO ORDER BY nivel_minimo ASC LIMIT 1');
        $inimigo = $stmtInimigo2->fetch();
    }

    echo json_encode([
        'success'  => true,
        'palavras' => $palavras,
        'inimigo'  => $inimigo,
        'nivel'    => $nivel,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar dados do jogo.']);
}
