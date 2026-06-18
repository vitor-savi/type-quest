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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$idUsuario = $_SESSION['usuario']['idUsuario'];

// Validação dos campos obrigatórios
$idInimigo       = (int)($body['idInimigo']       ?? 0);
$pontuacao       = max(0, (int)($body['pontuacao']       ?? 0));
$wpm             = max(0, (int)($body['wpm']             ?? 0));
$precisao        = max(0, min(100, (float)($body['precisao']  ?? 0)));
$resultado       = $body['resultado']       ?? '';
$duracaoSegundos = max(0, (int)($body['duracao_segundos'] ?? 0));
$palavras        = $body['palavras']        ?? [];

if (!in_array($resultado, ['vitoria', 'derrota'], true) || $idInimigo <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    exit;
}

try {
    $pdo = getDB();

    // Verifica se o inimigo existe
    $chkInimigo = $pdo->prepare('SELECT idInimigo FROM INIMIGO WHERE idInimigo = :id');
    $chkInimigo->execute([':id' => $idInimigo]);
    if (!$chkInimigo->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Inimigo não encontrado.']);
        exit;
    }

    // Calcula nível atual do jogador
    $stmtTotal = $pdo->prepare('SELECT COUNT(*) AS total FROM PARTIDA WHERE FK_USUARIO_idUsuario = :id');
    $stmtTotal->execute([':id' => $idUsuario]);
    $totalPartidas = (int)$stmtTotal->fetchColumn();
    $nivel = min(NIVEL_MAXIMO_JOGADOR, 1 + (int)floor($totalPartidas / PARTIDAS_POR_NIVEL));

    $pdo->beginTransaction();

    // Insere a partida
    $stmtPartida = $pdo->prepare(
        'INSERT INTO PARTIDA
            (FK_USUARIO_idUsuario, FK_INIMIGO_idInimigo, pontuacao, wpm, precisao,
             nivel_atingido, resultado, duracao_segundos)
         VALUES (:uid, :iid, :pts, :wpm, :pre, :niv, :res, :dur)'
    );
    $stmtPartida->execute([
        ':uid' => $idUsuario,
        ':iid' => $idInimigo,
        ':pts' => $pontuacao,
        ':wpm' => $wpm,
        ':pre' => $precisao,
        ':niv' => $nivel,
        ':res' => $resultado,
        ':dur' => $duracaoSegundos,
    ]);
    $idPartida = (int)$pdo->lastInsertId();

    // Insere relação partida-palavras
    if (!empty($palavras)) {
        $stmtPP = $pdo->prepare(
            'INSERT IGNORE INTO PARTIDA_PALAVRA (FK_PARTIDA_idPartida, FK_PALAVRA_idPalavra, acertou)
             VALUES (:pid, :pwid, :acertou)'
        );
        foreach ($palavras as $pw) {
            $idPalavra = (int)($pw['idPalavra'] ?? 0);
            $acertou   = (int)(!empty($pw['acertou']));
            if ($idPalavra > 0) {
                $stmtPP->execute([':pid' => $idPartida, ':pwid' => $idPalavra, ':acertou' => $acertou]);
            }
        }
    }

    // Atualiza pontuações nas ligas do usuário
    $stmtLigas = $pdo->prepare(
        'UPDATE USUARIO_LIGA
         SET pontuacao_total   = pontuacao_total   + :pts,
             pontuacao_semanal = pontuacao_semanal + :pts
         WHERE FK_USUARIO_idUsuario = :uid'
    );
    $stmtLigas->execute([':pts' => $pontuacao, ':uid' => $idUsuario]);

    // Atualiza ou insere pontuação semanal global
    $semanaInicio = date('Y-m-d', strtotime('monday this week'));
    $semanaFim    = date('Y-m-d', strtotime('sunday this week'));

    $stmtPS = $pdo->prepare(
        'INSERT INTO PONTUACAO_SEMANAL
            (FK_USUARIO_idUsuario, FK_LIGA_idLiga, pontuacao, semana_inicio, semana_fim)
         VALUES (:uid, NULL, :pts, :ini, :fim)
         ON DUPLICATE KEY UPDATE pontuacao = pontuacao + VALUES(pontuacao)'
    );
    $stmtPS->execute([
        ':uid' => $idUsuario,
        ':pts' => $pontuacao,
        ':ini' => $semanaInicio,
        ':fim' => $semanaFim,
    ]);

    $pdo->commit();

    // Pontuação total acumulada do usuário
    $stmtPtTotal = $pdo->prepare('SELECT COALESCE(SUM(pontuacao), 0) FROM PARTIDA WHERE FK_USUARIO_idUsuario = :id');
    $stmtPtTotal->execute([':id' => $idUsuario]);
    $pontuacaoTotal = (int)$stmtPtTotal->fetchColumn();

    echo json_encode(['success' => true, 'pontuacao_total' => $pontuacaoTotal]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar partida.']);
}
