<?php
require_once __DIR__ . '/../../config/config.php';
session_name(SESSION_NAME);
session_start();

// Já está logado → redireciona para dashboard
if (isset($_SESSION['usuario'])) {
    header('Location: /pages/dashboard.php');
    exit;
}

$pageTitle = 'Entrar';
$bodyClass = 'auth-page';
$pageCss   = ['/assets/css/auth.css'];
$pageJs    = ['/assets/js/auth.js'];

// Mensagem de sucesso vinda do registro
$successMsg = $_GET['registered'] ?? null;
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<main class="auth-container">
    <div class="auth-logo">
        <span class="auth-logo__icon"><i class="bi bi-shield-fill-exclamation"></i></span>
        <div class="auth-logo__name">TypeQuest</div>
        <div class="auth-logo__tagline">RPG de Digitação — Prove seu valor com as palavras</div>
    </div>

    <div class="auth-card">
        <h1 class="auth-card__title">Entrar na aventura</h1>
        <p class="auth-card__sub">Acesse sua conta para continuar jogando</p>

        <?php if ($successMsg): ?>
        <div class="alert-quest alert-quest--success" id="successAlert">
            <i class="bi bi-check-circle-fill"></i>
            Conta criada com sucesso! Faça login para entrar.
        </div>
        <?php endif; ?>

        <div class="alert-quest alert-quest--error d-none" id="loginError">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span id="loginErrorMsg">Credenciais inválidas.</span>
        </div>

        <form class="form-quest" id="loginForm" novalidate>
            <div class="form-quest__group">
                <label class="form-quest__label" for="login">
                    <i class="bi bi-person"></i> Usuário ou e-mail
                </label>
                <input
                    type="text"
                    id="login"
                    name="login"
                    class="form-quest__input"
                    placeholder="Digite seu usuário ou e-mail"
                    autocomplete="username"
                    required
                >
                <span class="form-quest__error" id="loginFieldError">Campo obrigatório.</span>
            </div>

            <div class="form-quest__group">
                <label class="form-quest__label" for="senha">
                    <i class="bi bi-lock"></i> Senha
                </label>
                <input
                    type="password"
                    id="senha"
                    name="senha"
                    class="form-quest__input"
                    placeholder="Digite sua senha"
                    autocomplete="current-password"
                    required
                >
                <span class="form-quest__error" id="senhaFieldError">Campo obrigatório.</span>
            </div>

            <button type="submit" class="btn-quest btn-quest--primary btn-quest--lg" id="loginBtn">
                <i class="bi bi-shield-lock-fill"></i>
                <span>Entrar</span>
            </button>
        </form>

        <div class="auth-footer">
            Ainda não tem conta?
            <a href="/pages/auth/register.php">Criar conta grátis</a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
