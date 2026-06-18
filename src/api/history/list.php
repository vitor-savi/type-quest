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

$idUsuario = $_SESSION['usuario']['idUsuario'];
$pagina    = max(1, (int)($_GET['pagina'] ?? 1));
$limite    = max(1, min(50, (int)($_GET['limite'] ?? 20)));
$offset    = ($pagina - 1) * $limite;

try {
    $pdo = getDB();

    // Total de partidas para paginação
    $stmtTotal = $pdo->prepare('SELECT COUNT(*) FROM PARTIDA WHERE FK_USUARIO_idUsuario = :id');
    $stmtTotal->execute([':id' => $idUsuario]);
    $total = (int)$stmtTotal->fetchColumn();

    // Partidas paginadas
    $stmt = $pdo->prepare(
        'SELECT p.idPartida, p.pontuacao, p.wpm, p.precisao,
                p.nivel_atingido, p.resultado, p.data_partida, p.duracao_segundos,
                i.nome AS inimigo_nome, i.sprite AS inimigo_sprite, i.tipo AS inimigo_tipo
         FROM PARTIDA p
         JOIN INIMIGO i ON i.idInimigo = p.FK_INIMIGO_idInimigo
         WHERE p.FK_USUARIO_idUsuario = :id
         ORDER BY p.data_partida DESC
         LIMIT :lim OFFSET :off'
    );
    $stmt->bindValue(':id',  $idUsuario, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limite,    PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset,    PDO::PARAM_INT);
    $stmt->execute();
    $partidas = $stmt->fetchAll();

    echo json_encode([
        'success'  => true,
        'partidas' => $partidas,
        'total'    => $total,
        'pagina'   => $pagina,
        'limite'   => $limite,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar histórico.']);
}
