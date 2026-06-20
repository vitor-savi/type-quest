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

$tipo   = in_array($_GET['tipo'] ?? '', ['total', 'semanal']) ? $_GET['tipo'] : 'total';
$limite = max(1, min(100, (int)($_GET['limite'] ?? 50)));
$idUsuario = $_SESSION['usuario']['idUsuario'];

try {
    $pdo = getDB();

    if ($tipo === 'total') {
        // Ranking total: soma de pontuações de todas as partidas
        $stmt = $pdo->prepare(
            'SELECT u.idUsuario, u.nome_usuario,
                    SUM(p.pontuacao)               AS pontuacao,
                    COALESCE(AVG(p.wpm), 0)        AS wpm_medio,
                    COALESCE(AVG(p.precisao), 0)   AS precisao_media,
                    COUNT(p.idPartida)             AS total_partidas
             FROM USUARIO u
             JOIN PARTIDA p ON p.FK_USUARIO_idUsuario = u.idUsuario
             GROUP BY u.idUsuario, u.nome_usuario
             ORDER BY pontuacao DESC
             LIMIT :lim'
        );
        $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        // Ranking semanal: tabela PONTUACAO_SEMANAL, semana atual
        $day = (int)date('N'); // 1=Segunda, 7=Domingo
        $semanaInicio = date('Y-m-d', strtotime('-' . ($day - 1) . ' days'));
        $stmt = $pdo->prepare(
            'SELECT u.idUsuario, u.nome_usuario,
                    SUM(ps.pontuacao) AS pontuacao,
                    COALESCE(
                        (SELECT AVG(p2.wpm) FROM PARTIDA p2
                         WHERE p2.FK_USUARIO_idUsuario = u.idUsuario
                           AND p2.data_partida >= :ini), 0) AS wpm_medio,
                    COALESCE(
                        (SELECT AVG(p2.precisao) FROM PARTIDA p2
                         WHERE p2.FK_USUARIO_idUsuario = u.idUsuario
                           AND p2.data_partida >= :ini2), 0) AS precisao_media,
                    0 AS total_partidas
             FROM PONTUACAO_SEMANAL ps
             JOIN USUARIO u ON u.idUsuario = ps.FK_USUARIO_idUsuario
             WHERE ps.FK_LIGA_idLiga IS NULL
               AND ps.semana_inicio = :ini3
             GROUP BY u.idUsuario, u.nome_usuario
             ORDER BY pontuacao DESC
             LIMIT :lim'
        );
        $stmt->bindValue(':ini',  $semanaInicio);
        $stmt->bindValue(':ini2', $semanaInicio);
        $stmt->bindValue(':ini3', $semanaInicio);
        $stmt->bindValue(':lim',  $limite, PDO::PARAM_INT);
        $stmt->execute();
    }

    $rows = $stmt->fetchAll();

    // Monta o ranking com posições e identifica a posição do usuário logado
    $ranking          = [];
    $posicaoUsuario   = null;

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
        'ranking'         => $ranking,
        'posicao_usuario' => $posicaoUsuario,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar ranking.']);
}
