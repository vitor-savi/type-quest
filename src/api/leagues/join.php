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

$body         = json_decode(file_get_contents('php://input'), true);
$idUsuario    = $_SESSION['usuario']['idUsuario'];
$nomeLiga     = trim($body['nome_liga']     ?? '');
$palavraChave = trim($body['palavra_chave'] ?? '');

if ($nomeLiga === '' || $palavraChave === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nome da liga e palavra-chave são obrigatórios.']);
    exit;
}

try {
    $pdo = getDB();

    // Busca a liga pelo nome
    $stmtLiga = $pdo->prepare('SELECT idLiga, palavra_chave FROM LIGA WHERE nome = :nome LIMIT 1');
    $stmtLiga->execute([':nome' => $nomeLiga]);
    $liga = $stmtLiga->fetch();

    if (!$liga) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Liga não encontrada.']);
        exit;
    }

    if ($liga['palavra_chave'] !== $palavraChave) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Palavra-chave incorreta.']);
        exit;
    }

    // Verifica se já é membro
    $chk = $pdo->prepare(
        'SELECT 1 FROM USUARIO_LIGA WHERE FK_USUARIO_idUsuario = :uid AND FK_LIGA_idLiga = :lid'
    );
    $chk->execute([':uid' => $idUsuario, ':lid' => $liga['idLiga']]);
    if ($chk->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Você já faz parte desta liga.']);
        exit;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO USUARIO_LIGA (FK_USUARIO_idUsuario, FK_LIGA_idLiga)
         VALUES (:uid, :lid)'
    );
    $stmt->execute([':uid' => $idUsuario, ':lid' => $liga['idLiga']]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao entrar na liga.']);
}
