<?php
$currentPage = $currentPage ?? '';
?>
<aside class="sidebar">
    <nav class="sidebar-nav">
        <a href="/pages/dashboard.php" class="sidebar-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
            <i class="bi bi-house-fill"></i>
            <span>Dashboard</span>
        </a>

        <a href="/pages/game.php" class="sidebar-link sidebar-link--highlight <?= $currentPage === 'game' ? 'active' : '' ?>">
            <i class="bi bi-play-circle-fill"></i>
            <span>Batalhar</span>
        </a>

        <div class="sidebar-divider"></div>

        <a href="/pages/ranking.php" class="sidebar-link <?= $currentPage === 'ranking' ? 'active' : '' ?>">
            <i class="bi bi-trophy-fill"></i>
            <span>Ranking</span>
        </a>

        <a href="/pages/leagues.php" class="sidebar-link <?= $currentPage === 'leagues' ? 'active' : '' ?>">
            <i class="bi bi-people-fill"></i>
            <span>Ligas</span>
        </a>

        <a href="/pages/history.php" class="sidebar-link <?= $currentPage === 'history' ? 'active' : '' ?>">
            <i class="bi bi-clock-history"></i>
            <span>Histórico</span>
        </a>

        <div class="sidebar-divider"></div>

        <a href="/api/auth/logout.php" class="sidebar-link sidebar-link--danger">
            <i class="bi bi-box-arrow-right"></i>
            <span>Sair</span>
        </a>
    </nav>
</aside>
