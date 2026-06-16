<?php
// Garante que a sessão existe antes de usar $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentUser = $_SESSION['usuario'] ?? null;
$currentPage = $currentPage ?? '';
?>
<nav class="topbar">
    <div class="topbar-brand">
        <a href="/pages/dashboard.php" class="brand-link">
            <span class="brand-icon"><i class="bi bi-shield-fill-exclamation"></i></span>
            <span class="brand-name">TypeQuest</span>
        </a>
    </div>

    <div class="topbar-nav">
        <a href="/pages/dashboard.php" class="topbar-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
            <i class="bi bi-house-fill"></i>
            <span>Início</span>
        </a>
        <a href="/pages/game.php" class="topbar-link topbar-link--play <?= $currentPage === 'game' ? 'active' : '' ?>">
            <i class="bi bi-play-circle-fill"></i>
            <span>Jogar</span>
        </a>
        <a href="/pages/ranking.php" class="topbar-link <?= $currentPage === 'ranking' ? 'active' : '' ?>">
            <i class="bi bi-trophy-fill"></i>
            <span>Ranking</span>
        </a>
        <a href="/pages/leagues.php" class="topbar-link <?= $currentPage === 'leagues' ? 'active' : '' ?>">
            <i class="bi bi-people-fill"></i>
            <span>Ligas</span>
        </a>
        <a href="/pages/history.php" class="topbar-link <?= $currentPage === 'history' ? 'active' : '' ?>">
            <i class="bi bi-clock-history"></i>
            <span>Histórico</span>
        </a>
    </div>

    <div class="topbar-user">
        <?php if ($currentUser): ?>
        <span class="user-greeting">
            <i class="bi bi-person-circle"></i>
            <?= htmlspecialchars($currentUser['nome_usuario']) ?>
        </span>
        <a href="/api/auth/logout.php" class="btn-logout" title="Sair">
            <i class="bi bi-box-arrow-right"></i>
        </a>
        <?php endif; ?>
    </div>
</nav>
