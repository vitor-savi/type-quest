<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// Lê o corpo JSON da requisição
$body = json_decode(file_get_contents('php://input'), true);

$login = trim($body['login'] ?? '');
$senha = trim($body['senha'] ?? '');

if ($login === '' || $senha === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Usuário/e-mail e senha são obrigatórios.']);
    exit;
}

try {
    $pdo = getDB();

    // Busca por nome_usuario OU email
    $stmt = $pdo->prepare(
        'SELECT idUsuario, nome_usuario, email, senha_hash
         FROM USUARIO
         WHERE nome_usuario = :login1 OR email = :login2
         LIMIT 1'
    );
    $stmt->execute([':login1' => $login, ':login2' => $login]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Usuário ou senha incorretos.']);
        exit;
    }

    // Atualiza último login
    $upd = $pdo->prepare('UPDATE USUARIO SET ultimo_login = NOW() WHERE idUsuario = :id');
    $upd->execute([':id' => $usuario['idUsuario']]);

    // Inicia sessão
    session_name(SESSION_NAME);
    session_start();
    session_regenerate_id(true);

    $_SESSION['usuario'] = [
        'idUsuario'   => $usuario['idUsuario'],
        'nome_usuario' => $usuario['nome_usuario'],
        'email'        => $usuario['email'],
    ];

    echo json_encode([
        'success' => true,
        'usuario' => [
            'idUsuario'    => $usuario['idUsuario'],
            'nome_usuario' => $usuario['nome_usuario'],
        ],
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno no servidor.']);
}
