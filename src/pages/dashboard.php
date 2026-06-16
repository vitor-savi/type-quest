<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

session_name(SESSION_NAME);
session_start();

// Proteção de rota — redireciona para login se não autenticado
if (!isset($_SESSION['usuario'])) {
    header('Location: /pages/auth/login.php');
    exit;
}

$usuario = $_SESSION['usuario'];
$idUsuario = $usuario['idUsuario'];

$pdo = getDB();

// Estatísticas gerais do usuário
$stmtStats = $pdo->prepare(
    'SELECT
        COUNT(*)                                        AS total_partidas,
        SUM(resultado = "vitoria")                      AS vitorias,
        SUM(resultado = "derrota")                      AS derrotas,
        COALESCE(AVG(wpm), 0)                           AS wpm_medio,
        COALESCE(AVG(precisao), 0)                      AS precisao_media,
        COALESCE(SUM(pontuacao), 0)                     AS pontuacao_total
     FROM PARTIDA
     WHERE FK_USUARIO_idUsuario = :id'
);
$stmtStats->execute([':id' => $idUsuario]);
$stats = $stmtStats->fetch();

// Nível do jogador
$nivel = min(NIVEL_MAXIMO_JOGADOR, 1 + (int)floor($stats['total_partidas'] / PARTIDAS_POR_NIVEL));

// Mini-ranking global (top 5 + posição do usuário)
$stmtRank = $pdo->prepare(
    'SELECT u.nome_usuario, SUM(p.pontuacao) AS pontuacao_total
     FROM PARTIDA p
     JOIN USUARIO u ON u.idUsuario = p.FK_USUARIO_idUsuario
     GROUP BY p.FK_USUARIO_idUsuario
     ORDER BY pontuacao_total DESC
     LIMIT 5'
);
$stmtRank->execute();
$ranking = $stmtRank->fetchAll();

// Últimas 3 partidas
$stmtHistory = $pdo->prepare(
    'SELECT p.*, i.nome AS inimigo_nome, i.sprite AS inimigo_sprite
     FROM PARTIDA p
     JOIN INIMIGO i ON i.idInimigo = p.FK_INIMIGO_idInimigo
     WHERE p.FK_USUARIO_idUsuario = :id
     ORDER BY p.data_partida DESC
     LIMIT 3'
);
$stmtHistory->execute([':id' => $idUsuario]);
$ultimasPartidas = $stmtHistory->fetchAll();

// Ligas do usuário (máx 3)
$stmtLeagues = $pdo->prepare(
    'SELECT l.nome, ul.pontuacao_total, ul.pontuacao_semanal,
            (SELECT COUNT(*) FROM USUARIO_LIGA WHERE FK_LIGA_idLiga = l.idLiga) AS membros
     FROM USUARIO_LIGA ul
     JOIN LIGA l ON l.idLiga = ul.FK_LIGA_idLiga
     WHERE ul.FK_USUARIO_idUsuario = :id
     LIMIT 3'
);
$stmtLeagues->execute([':id' => $idUsuario]);
$ligas = $stmtLeagues->fetchAll();

$pageTitle  = 'Dashboard';
$bodyClass  = 'layout-inner';
$currentPage = 'dashboard';
$pageCss    = ['/assets/css/dashboard.css'];
$pageJs     = [];
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/topbar.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<main class="page-content">

    <!-- Banner de boas-vindas -->
    <div class="welcome-banner">
        <div class="welcome-banner__text">
            <h2>Bem-vindo de volta, <?= htmlspecialchars($usuario['nome_usuario']) ?>! ⚔️</h2>
            <p>Pronto para mais uma batalha? Cada palavra é um golpe no inimigo.</p>
        </div>
        <div class="welcome-banner__level">
            <span class="level-num"><?= $nivel ?></span>
            <span class="level-label">Nível</span>
        </div>
    </div>

    <!-- Botão de batalha -->
    <div style="margin-bottom:2rem; text-align:center;">
        <a href="/pages/game.php" class="battle-cta">
            <i class="bi bi-play-circle-fill"></i>
            Iniciar Batalha
        </a>
    </div>

    <!-- Grade de estatísticas -->
    <div class="grid-stats" style="margin-bottom:2rem;">
        <div class="stat-card">
            <div class="stat-card__icon stat-card__icon--purple">
                <i class="bi bi-controller"></i>
            </div>
            <div class="stat-card__body">
                <div class="stat-card__value"><?= (int)$stats['total_partidas'] ?></div>
                <div class="stat-card__label">Partidas Jogadas</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card__icon stat-card__icon--green">
                <i class="bi bi-trophy-fill"></i>
            </div>
            <div class="stat-card__body">
                <div class="stat-card__value"><?= (int)$stats['vitorias'] ?></div>
                <div class="stat-card__label">Vitórias</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card__icon stat-card__icon--gold">
                <i class="bi bi-keyboard"></i>
            </div>
            <div class="stat-card__body">
                <div class="stat-card__value"><?= number_format($stats['wpm_medio'], 0) ?></div>
                <div class="stat-card__label">WPM Médio</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card__icon stat-card__icon--green">
                <i class="bi bi-bullseye"></i>
            </div>
            <div class="stat-card__body">
                <div class="stat-card__value"><?= number_format($stats['precisao_media'], 1) ?>%</div>
                <div class="stat-card__label">Precisão Média</div>
            </div>
        </div>
    </div>

    <!-- Conteúdo em grade 3 colunas -->
    <div class="row g-4">
        <!-- Mini-ranking global -->
        <div class="col-md-4">
            <div class="section-card">
                <div class="section-card__header">
                    <span class="section-card__title">
                        <i class="bi bi-trophy-fill" style="color:var(--color-accent)"></i>
                        Top 5 Global
                    </span>
                    <a href="/pages/ranking.php" class="btn-quest btn-quest--ghost" style="padding:0.3rem 0.75rem;font-size:0.8rem;">Ver tudo</a>
                </div>
                <div class="section-card__body">
                    <?php if (empty($ranking)): ?>
                    <p style="color:var(--text-muted);font-size:0.875rem;text-align:center;">Nenhum dado ainda.</p>
                    <?php else: ?>
                    <div class="mini-ranking">
                        <?php foreach ($ranking as $pos => $row): ?>
                        <div class="mini-ranking__item <?= $row['nome_usuario'] === $usuario['nome_usuario'] ? 'mini-ranking__item--me' : '' ?>">
                            <span class="mini-ranking__pos rank-pos--<?= $pos + 1 ?>">
                                <?php if ($pos === 0): ?>🥇<?php elseif ($pos === 1): ?>🥈<?php elseif ($pos === 2): ?>🥉<?php else: ?>#<?= $pos + 1 ?><?php endif; ?>
                            </span>
                            <span class="avatar-letter" style="width:28px;height:28px;font-size:0.75rem;"><?= strtoupper(substr($row['nome_usuario'], 0, 1)) ?></span>
                            <span class="mini-ranking__name"><?= htmlspecialchars($row['nome_usuario']) ?></span>
                            <span class="mini-ranking__score"><?= number_format($row['pontuacao_total']) ?>pts</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Últimas partidas -->
        <div class="col-md-4">
            <div class="section-card">
                <div class="section-card__header">
                    <span class="section-card__title">
                        <i class="bi bi-clock-history" style="color:var(--color-primary)"></i>
                        Últimas Batalhas
                    </span>
                    <a href="/pages/history.php" class="btn-quest btn-quest--ghost" style="padding:0.3rem 0.75rem;font-size:0.8rem;">Ver tudo</a>
                </div>
                <div class="section-card__body" style="display:flex;flex-direction:column;gap:0.5rem;">
                    <?php if (empty($ultimasPartidas)): ?>
                    <p style="color:var(--text-muted);font-size:0.875rem;text-align:center;">Nenhuma partida ainda.<br>Comece agora!</p>
                    <?php else: ?>
                    <?php foreach ($ultimasPartidas as $partida): ?>
                    <div class="recent-match">
                        <div class="recent-match__enemy"><?= $partida['inimigo_sprite'] ?></div>
                        <div class="recent-match__info">
                            <div class="recent-match__enemy-name"><?= htmlspecialchars($partida['inimigo_nome']) ?></div>
                            <div class="recent-match__date"><?= date('d/m/Y H:i', strtotime($partida['data_partida'])) ?></div>
                        </div>
                        <div class="recent-match__stats">
                            <span class="badge-quest <?= $partida['resultado'] === 'vitoria' ? 'badge-quest--success' : 'badge-quest--danger' ?>">
                                <?= $partida['resultado'] === 'vitoria' ? '✓ Vitória' : '✗ Derrota' ?>
                            </span>
                            <div style="margin-top:0.3rem;color:var(--text-muted)"><?= $partida['wpm'] ?>wpm · <?= number_format($partida['precisao'], 0) ?>%</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Ligas -->
        <div class="col-md-4">
            <div class="section-card">
                <div class="section-card__header">
                    <span class="section-card__title">
                        <i class="bi bi-people-fill" style="color:var(--color-success)"></i>
                        Minhas Ligas
                    </span>
                    <a href="/pages/leagues.php" class="btn-quest btn-quest--ghost" style="padding:0.3rem 0.75rem;font-size:0.8rem;">Gerenciar</a>
                </div>
                <div class="section-card__body" style="display:flex;flex-direction:column;gap:0.5rem;">
                    <?php if (empty($ligas)): ?>
                    <p style="color:var(--text-muted);font-size:0.875rem;text-align:center;">Você não está em nenhuma liga.<br>
                    <a href="/pages/leagues.php">Criar ou entrar em uma!</a></p>
                    <?php else: ?>
                    <?php foreach ($ligas as $liga): ?>
                    <div class="league-item">
                        <div>
                            <div class="league-item__name"><?= htmlspecialchars($liga['nome']) ?></div>
                            <div class="league-item__members"><i class="bi bi-people"></i> <?= (int)$liga['membros'] ?> membros</div>
                        </div>
                        <div class="league-item__score"><?= number_format($liga['pontuacao_total']) ?>pts</div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
