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

try {
    $pdo = getDB();

    $stmt = $pdo->prepare(
        'SELECT l.idLiga, l.nome, l.descricao, l.data_criacao,
                ul.pontuacao_total, ul.pontuacao_semanal,
                (SELECT COUNT(*) FROM USUARIO_LIGA WHERE FK_LIGA_idLiga = l.idLiga) AS membros,
                (l.FK_USUARIO_idUsuario = :uid2) AS eh_criador,
                (SELECT COUNT(*) + 1
                 FROM USUARIO_LIGA ul2
                 WHERE ul2.FK_LIGA_idLiga = l.idLiga
                   AND ul2.pontuacao_total > ul.pontuacao_total) AS posicao
         FROM USUARIO_LIGA ul
         JOIN LIGA l ON l.idLiga = ul.FK_LIGA_idLiga
         WHERE ul.FK_USUARIO_idUsuario = :uid
         ORDER BY l.nome'
    );
    $stmt->execute([':uid' => $idUsuario, ':uid2' => $idUsuario]);
    $ligas = $stmt->fetchAll();

    // Para cada liga, busca top 3 membros
    foreach ($ligas as &$liga) {
        $stmtTop = $pdo->prepare(
            'SELECT u.nome_usuario, ul.pontuacao_total
             FROM USUARIO_LIGA ul
             JOIN USUARIO u ON u.idUsuario = ul.FK_USUARIO_idUsuario
             WHERE ul.FK_LIGA_idLiga = :lid
             ORDER BY ul.pontuacao_total DESC
             LIMIT 3'
        );
        $stmtTop->execute([':lid' => $liga['idLiga']]);
        $liga['top_membros'] = $stmtTop->fetchAll();
    }
    unset($liga);

    echo json_encode(['success' => true, 'ligas' => $ligas]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao listar ligas.']);
}
