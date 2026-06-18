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

$idLiga    = (int)($_GET['idLiga'] ?? 0);
$tipo      = in_array($_GET['tipo'] ?? '', ['total', 'semanal']) ? $_GET['tipo'] : 'total';
$idUsuario = $_SESSION['usuario']['idUsuario'];

if ($idLiga <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Liga inválida.']);
    exit;
}

try {
    $pdo = getDB();

    // Verifica se a liga existe e o usuário tem acesso
    $stmtLiga = $pdo->prepare('SELECT idLiga, nome FROM LIGA WHERE idLiga = :id');
    $stmtLiga->execute([':id' => $idLiga]);
    $liga = $stmtLiga->fetch();

    if (!$liga) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Liga não encontrada.']);
        exit;
    }

    $campoScore = $tipo === 'semanal' ? 'ul.pontuacao_semanal' : 'ul.pontuacao_total';

    $stmt = $pdo->prepare(
        "SELECT u.idUsuario, u.nome_usuario,
                $campoScore AS pontuacao,
                COALESCE(AVG(p.wpm), 0)      AS wpm_medio,
                COALESCE(AVG(p.precisao), 0) AS precisao_media
         FROM USUARIO_LIGA ul
         JOIN USUARIO u ON u.idUsuario = ul.FK_USUARIO_idUsuario
         LEFT JOIN PARTIDA p ON p.FK_USUARIO_idUsuario = u.idUsuario
         WHERE ul.FK_LIGA_idLiga = :lid
         GROUP BY u.idUsuario, u.nome_usuario, ul.pontuacao_total, ul.pontuacao_semanal
         ORDER BY pontuacao DESC"
    );
    $stmt->execute([':lid' => $idLiga]);
    $rows = $stmt->fetchAll();

    $ranking        = [];
    $posicaoUsuario = null;

    foreach ($rows as $pos => $row) {
        $entry = [
            'posicao'        => $pos + 1,
            'nome_usuario'   => $row['nome_usuario'],
            'pontuacao'      => (int)$row['pontuacao'],
            'wpm_medio'      => round((float)$row['wpm_medio']),
            'precisao_media' => round((float)$row['precisao_media'], 1),
            'eh_usuario'     => $row['idUsuario'] == $idUsuario,
        ];

        if ($row['idUsuario'] == $idUsuario) {
            $posicaoUsuario = $pos + 1;
        }

        $ranking[] = $entry;
    }

    echo json_encode([
        'success'         => true,
        'liga'            => ['nome' => $liga['nome']],
        'ranking'         => $ranking,
        'posicao_usuario' => $posicaoUsuario,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar ranking da liga.']);
}
