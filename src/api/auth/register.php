<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);

$nomeUsuario = trim($body['nome_usuario'] ?? '');
$email       = trim($body['email']        ?? '');
$senha       = trim($body['senha']        ?? '');

// Validações básicas
if ($nomeUsuario === '' || $email === '' || $senha === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios.']);
    exit;
}

if (strlen($nomeUsuario) < 3 || strlen($nomeUsuario) > 50) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nome de usuário deve ter entre 3 e 50 caracteres.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'E-mail inválido.']);
    exit;
}

if (strlen($senha) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Senha deve ter pelo menos 6 caracteres.']);
    exit;
}

try {
    $pdo = getDB();

    // Verifica duplicidade de e-mail
    $chk = $pdo->prepare('SELECT idUsuario FROM USUARIO WHERE email = :email LIMIT 1');
    $chk->execute([':email' => $email]);
    if ($chk->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Este e-mail já está cadastrado.']);
        exit;
    }

    // Verifica duplicidade de nome
    $chk2 = $pdo->prepare('SELECT idUsuario FROM USUARIO WHERE nome_usuario = :nome LIMIT 1');
    $chk2->execute([':nome' => $nomeUsuario]);
    if ($chk2->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Este nome de usuário já está em uso.']);
        exit;
    }

    $hash = password_hash($senha, PASSWORD_DEFAULT);

    $ins = $pdo->prepare(
        'INSERT INTO USUARIO (nome_usuario, email, senha_hash) VALUES (:nome, :email, :hash)'
    );
    $ins->execute([
        ':nome'  => $nomeUsuario,
        ':email' => $email,
        ':hash'  => $hash,
    ]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno no servidor.']);
}
