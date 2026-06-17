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

$body        = json_decode(file_get_contents('php://input'), true);
$idUsuario   = $_SESSION['usuario']['idUsuario'];
$nome        = trim($body['nome']         ?? '');
$descricao   = trim($body['descricao']    ?? '');
$palavraChave = trim($body['palavra_chave'] ?? '');

if ($nome === '' || $palavraChave === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nome e palavra-chave são obrigatórios.']);
    exit;
}

if (strlen($nome) > 80) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nome muito longo (máx 80 caracteres).']);
    exit;
}

try {
    $pdo = getDB();

    // Verifica se o nome já existe
    $chk = $pdo->prepare('SELECT idLiga FROM LIGA WHERE nome = :nome LIMIT 1');
    $chk->execute([':nome' => $nome]);
    if ($chk->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Já existe uma liga com este nome.']);
        exit;
    }

    $pdo->beginTransaction();

    $stmtLiga = $pdo->prepare(
        'INSERT INTO LIGA (FK_USUARIO_idUsuario, nome, palavra_chave, descricao)
         VALUES (:uid, :nome, :chave, :desc)'
    );
    $stmtLiga->execute([
        ':uid'   => $idUsuario,
        ':nome'  => $nome,
        ':chave' => $palavraChave,
        ':desc'  => $descricao ?: null,
    ]);
    $idLiga = (int)$pdo->lastInsertId();

    // O criador entra automaticamente na liga
    $stmtMembro = $pdo->prepare(
        'INSERT INTO USUARIO_LIGA (FK_USUARIO_idUsuario, FK_LIGA_idLiga)
         VALUES (:uid, :lid)'
    );
    $stmtMembro->execute([':uid' => $idUsuario, ':lid' => $idLiga]);

    $pdo->commit();

    echo json_encode(['success' => true, 'idLiga' => $idLiga]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao criar liga.']);
}
