<?php
require_once __DIR__ . '/../../config/config.php';
session_name(SESSION_NAME);
session_start();

// Já logado → dashboard
if (isset($_SESSION['usuario'])) {
    header('Location: /pages/dashboard.php');
    exit;
}

$pageTitle = 'Criar Conta';
$bodyClass = 'auth-page';
$pageCss   = ['/assets/css/auth.css'];
$pageJs    = ['/assets/js/auth.js'];
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<main class="auth-container">
    <div class="auth-logo">
        <span class="auth-logo__icon"><i class="bi bi-shield-fill-exclamation"></i></span>
        <div class="auth-logo__name">TypeQuest</div>
        <div class="auth-logo__tagline">Crie sua conta e comece a batalhar</div>
    </div>

    <div class="auth-card">
        <h1 class="auth-card__title">Criar conta de herói</h1>
        <p class="auth-card__sub">Preencha os campos para iniciar sua jornada</p>

        <div class="alert-quest alert-quest--error d-none" id="registerError">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span id="registerErrorMsg">Erro ao criar conta.</span>
        </div>

        <form class="form-quest" id="registerForm" novalidate>
            <div class="form-quest__group">
                <label class="form-quest__label" for="nome_usuario">
                    <i class="bi bi-person-badge"></i> Nome de usuário
                </label>
                <input
                    type="text"
                    id="nome_usuario"
                    name="nome_usuario"
                    class="form-quest__input"
                    placeholder="ex: MageSupremo42"
                    autocomplete="username"
                    required
                >
                <span class="form-quest__error" id="nomeError"></span>
            </div>

            <div class="form-quest__group">
                <label class="form-quest__label" for="email">
                    <i class="bi bi-envelope"></i> E-mail
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-quest__input"
                    placeholder="seu@email.com"
                    autocomplete="email"
                    required
                >
                <span class="form-quest__error" id="emailError"></span>
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
                    placeholder="Mínimo 6 caracteres"
                    autocomplete="new-password"
                    required
                >
                <div class="password-strength">
                    <div class="strength-bar">
                        <div class="strength-bar__segment" id="str1"></div>
                        <div class="strength-bar__segment" id="str2"></div>
                        <div class="strength-bar__segment" id="str3"></div>
                    </div>
                    <span class="strength-label" id="strengthLabel"></span>
                </div>
                <span class="form-quest__error" id="senhaError"></span>
            </div>

            <div class="form-quest__group">
                <label class="form-quest__label" for="confirmar_senha">
                    <i class="bi bi-lock-fill"></i> Confirmar senha
                </label>
                <input
                    type="password"
                    id="confirmar_senha"
                    name="confirmar_senha"
                    class="form-quest__input"
                    placeholder="Repita a senha"
                    autocomplete="new-password"
                    required
                >
                <span class="form-quest__error" id="confirmarError"></span>
            </div>

            <button type="submit" class="btn-quest btn-quest--primary btn-quest--lg" id="registerBtn">
                <i class="bi bi-person-plus-fill"></i>
                <span>Criar conta</span>
            </button>
        </form>

        <div class="auth-footer">
            Já tem conta?
            <a href="/pages/auth/login.php">Entrar agora</a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
