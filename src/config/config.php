<?php
$isDocker = file_exists('/.dockerenv');

if (!$isDocker) {
    // XAMPP: lê o arquivo .env da raiz do projeto manualmente
    // __DIR__ = src/config → dois níveis acima = raiz do projeto (onde está o .env)
    $envPath = dirname(__DIR__, 2) . '/.env';
    if (file_exists($envPath)) {
        foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
            [$key, $value] = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// No Docker: DB_HOST e DB_PORT são injetados pelo docker-compose (sempre db:3306)
// No XAMPP: lidos do .env acima
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'typequest');

// URL base (para links e caminhos de assets)
// No Docker, DocumentRoot é /var/www/html/src, então '/' aponta direto para src/
if ($isDocker) {
    define('BASE_URL', 'http://localhost:8080');
    define('WEB_BASE', '');
} else {
    // XAMPP: DocumentRoot é htdocs/, src/ fica em htdocs/<pasta>/src/
    // Calcula o caminho relativo da pasta src/ a partir do DOCUMENT_ROOT 
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $srcDir  = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
    $webBase = str_replace($docRoot, '', $srcDir);
    define('WEB_BASE', $webBase);
    define('BASE_URL', 'http://localhost' . $webBase);
}

// Sessão
define('SESSION_NAME', 'typequest_session');
define('SESSION_LIFETIME', 86400);

// Jogo
define('PALAVRAS_POR_PARTIDA', 10);
define('NIVEL_MAXIMO_JOGADOR', 10);
define('PARTIDAS_POR_NIVEL', 5);
