<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

session_name(SESSION_NAME);
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: /pages/auth/login.php');
    exit;
}

$usuario   = $_SESSION['usuario'];
$idUsuario = $usuario['idUsuario'];

$pdo = getDB();

// Calcula nível do jogador
$stmtNivel = $pdo->prepare('SELECT COUNT(*) FROM PARTIDA WHERE FK_USUARIO_idUsuario = :id');
$stmtNivel->execute([':id' => $idUsuario]);
$totalPartidas = (int)$stmtNivel->fetchColumn();
$nivel = min(NIVEL_MAXIMO_JOGADOR, 1 + (int)floor($totalPartidas / PARTIDAS_POR_NIVEL));

$pageTitle   = 'Batalha';
$bodyClass   = 'game-page';
$currentPage = 'game';
$pageCss     = ['/assets/css/game.css'];
$pageJs      = [
    '/assets/js/game/player.js',
    '/assets/js/game/enemy.js',
    '/assets/js/game/ui.js',
    '/assets/js/game/battle.js',
    '/assets/js/game/engine.js',
];
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/topbar.php'; ?>

<!-- Dados do jogo para o JS -->
<div id="gameData"
     data-nivel="<?= $nivel ?>"
     data-nome-usuario="<?= htmlspecialchars($usuario['nome_usuario']) ?>"
     style="display:none;"></div>

<div class="game-arena">

    <!-- Canvas + controles -->
    <div>
        <div class="canvas-container">
            <!-- Loading -->
            <div class="game-message" id="gameLoading">
                <div class="loading-spinner"></div>
                <span style="color:var(--text-secondary)">Carregando batalha...</span>
            </div>

            <!-- Erro -->
            <div class="game-message d-none" id="gameError" style="color:var(--color-danger)"></div>

            <!-- Overlay de início -->
            <div class="start-overlay d-none" id="startOverlay">
                <div class="start-overlay__enemy-sprite" id="startEnemySprite">👹</div>
                <h2 class="start-overlay__title">Você vai enfrentar:<br><span id="startEnemyName" style="color:var(--color-accent)"></span></h2>
                <div class="start-overlay__info">
                    <span><i class="bi bi-heart-fill" style="color:#ef4444"></i> HP: <strong id="startEnemyHP"></strong></span>
                    <span><i class="bi bi-lightning-fill" style="color:#f59e0b"></i> Seu nível: <strong id="startNivel"></strong></span>
                </div>
                <button class="btn-quest btn-quest--primary btn-quest--lg" onclick="startBattle()">
                    <i class="bi bi-play-circle-fill"></i>
                    Começar Batalha!
                </button>
            </div>

            <!-- Canvas da batalha -->
            <canvas id="battleCanvas"></canvas>
        </div>

        <!-- Área de digitação -->
        <div class="typing-area">
            <input
                type="text"
                id="typingInput"
                class="form-quest__input"
                placeholder="Aguardando início..."
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off"
                spellcheck="false"
                disabled
            >
            <div class="word-display">Digite a palavra que aparece no canvas acima</div>
        </div>
    </div>

    <!-- Painel lateral -->
    <div class="game-sidebar">

        <!-- HP do Herói -->
        <div class="hp-panel">
            <div class="hp-panel__title"><i class="bi bi-heart-fill" style="color:#10b981"></i> Seu HP</div>
            <div class="hp-panel__value" id="playerHP">100/100</div>
            <div class="hp-bar">
                <div class="hp-bar__fill" id="playerHPBar" style="width:100%"></div>
            </div>
        </div>

        <!-- HP do Inimigo -->
        <div class="hp-panel">
            <div class="hp-panel__title"><i class="bi bi-heart-fill" style="color:#ef4444"></i> HP do Inimigo</div>
            <div class="hp-panel__value" id="enemyHP">—</div>
            <div class="hp-bar">
                <div class="hp-bar__fill hp-bar__fill--danger" id="enemyHPBar" style="width:100%"></div>
            </div>
        </div>

        <!-- Estatísticas em tempo real -->
        <div class="game-stats-panel">
            <div class="game-stat">
                <span class="game-stat__label"><i class="bi bi-lightning-charge-fill" style="color:#f59e0b"></i> Pontuação</span>
                <span class="game-stat__value" id="hudPontuacao">0</span>
            </div>
            <div class="game-stat">
                <span class="game-stat__label"><i class="bi bi-keyboard"></i> WPM</span>
                <span class="game-stat__value" id="hudWPM">0</span>
            </div>
            <div class="game-stat">
                <span class="game-stat__label"><i class="bi bi-list-ol"></i> Palavra</span>
                <span class="game-stat__value" id="hudWordCount">0/10</span>
            </div>
        </div>

        <!-- Abandonar -->
        <button class="abandon-btn" onclick="confirmAbandon()">
            <i class="bi bi-x-circle"></i>
            Abandonar Partida
        </button>
    </div>

</div>

<!-- Modal de resultado -->
<div class="modal fade modal-quest result-modal" id="resultModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body" style="padding:2rem;">
                <h2 class="result-title" id="resultTitle">Resultado</h2>

                <div class="result-grid">
                    <div class="result-stat">
                        <div class="result-stat__value" id="resultWPM">0</div>
                        <div class="result-stat__label">WPM</div>
                    </div>
                    <div class="result-stat">
                        <div class="result-stat__value" id="resultPrecisao">0%</div>
                        <div class="result-stat__label">Precisão</div>
                    </div>
                    <div class="result-stat">
                        <div class="result-stat__value" id="resultPontuacao">0</div>
                        <div class="result-stat__label">Pontuação</div>
                    </div>
                    <div class="result-stat">
                        <div class="result-stat__value" id="resultTotal">0</div>
                        <div class="result-stat__label">Total Acumulado</div>
                    </div>
                </div>

                <div style="display:flex;gap:0.75rem;justify-content:center;flex-wrap:wrap;">
                    <button class="btn-quest btn-quest--primary" id="btnJogarNovamente" onclick="btnClickJogarNovamente()">
                        <i class="bi bi-arrow-repeat"></i> Jogar Novamente
                    </button>
                    <button class="btn-quest btn-quest--accent d-none" id="btnProximaFase" onclick="btnClickProximaFase()">
                        <i class="bi bi-arrow-right-circle-fill"></i> Próxima Fase
                    </button>
                    <button class="btn-quest btn-quest--ghost" id="btnDashboard" onclick="btnClickDashboard()">
                        <i class="bi bi-house-fill"></i> Dashboard
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmação de abandono -->
<div class="modal fade modal-quest" id="abandonModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body" style="padding:1.75rem;text-align:center;">
                <p style="color:var(--color-danger);font-size:2rem;">⚠️</p>
                <h5 style="font-family:var(--font-title);margin-bottom:0.5rem;">Abandonar batalha?</h5>
                <p style="color:var(--text-secondary);font-size:0.875rem;">Você perderá o progresso desta partida.</p>
                <div style="display:flex;gap:0.5rem;justify-content:center;margin-top:1rem;">
                    <a href="/pages/dashboard.php" class="btn-quest btn-quest--danger">Abandonar</a>
                    <button class="btn-quest btn-quest--ghost" data-bs-dismiss="modal">Continuar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmAbandon() {
    const modal = new bootstrap.Modal(document.getElementById('abandonModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
