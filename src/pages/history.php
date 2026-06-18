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

// Resumo de estatísticas
$stmtStats = $pdo->prepare(
    'SELECT COUNT(*) AS total,
            SUM(resultado = "vitoria")  AS vitorias,
            SUM(resultado = "derrota")  AS derrotas,
            COALESCE(AVG(wpm), 0)       AS wpm_medio
     FROM PARTIDA WHERE FK_USUARIO_idUsuario = :id'
);
$stmtStats->execute([':id' => $idUsuario]);
$stats = $stmtStats->fetch();

$pageTitle   = 'Histórico';
$bodyClass   = 'layout-inner';
$currentPage = 'history';
$pageCss     = ['/assets/css/dashboard.css'];
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/topbar.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<main class="page-content">
    <div class="page-header">
        <h1 class="page-header__title"><i class="bi bi-clock-history" style="color:var(--color-primary)"></i> Histórico de Batalhas</h1>
        <p class="page-header__sub">Todas as suas batalhas registradas</p>
    </div>

    <!-- Cards de resumo -->
    <div class="grid-stats" style="margin-bottom:2rem;">
        <div class="stat-card">
            <div class="stat-card__icon stat-card__icon--purple"><i class="bi bi-controller"></i></div>
            <div class="stat-card__body">
                <div class="stat-card__value"><?= (int)$stats['total'] ?></div>
                <div class="stat-card__label">Total</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card__icon stat-card__icon--green"><i class="bi bi-trophy-fill"></i></div>
            <div class="stat-card__body">
                <div class="stat-card__value"><?= (int)$stats['vitorias'] ?></div>
                <div class="stat-card__label">Vitórias</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card__icon stat-card__icon--red"><i class="bi bi-x-circle-fill"></i></div>
            <div class="stat-card__body">
                <div class="stat-card__value"><?= (int)$stats['derrotas'] ?></div>
                <div class="stat-card__label">Derrotas</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card__icon stat-card__icon--gold"><i class="bi bi-keyboard"></i></div>
            <div class="stat-card__body">
                <div class="stat-card__value"><?= number_format($stats['wpm_medio'], 0) ?></div>
                <div class="stat-card__label">WPM Médio</div>
            </div>
        </div>
    </div>

    <!-- Tabela de histórico -->
    <div class="section-card">
        <div class="section-card__header">
            <span class="section-card__title"><i class="bi bi-table"></i> Partidas</span>
            <span id="historyPaginacao" style="color:var(--text-muted);font-size:0.8rem;"></span>
        </div>
        <div style="overflow-x:auto;">
            <table class="table-quest" id="historyTable" style="display:none;min-width:600px;">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Inimigo</th>
                        <th>Resultado</th>
                        <th>WPM</th>
                        <th>Precisão</th>
                        <th>Pontuação</th>
                        <th>Nível</th>
                    </tr>
                </thead>
                <tbody id="historyBody"></tbody>
            </table>
        </div>
        <div id="historyLoading" style="padding:2rem;text-align:center;">
            <div class="loading-spinner" style="margin:auto;"></div>
        </div>
        <div id="historyEmpty" class="d-none" style="padding:2rem;text-align:center;color:var(--text-muted);">
            Nenhuma partida jogada ainda. <a href="/pages/game.php">Jogue agora!</a>
        </div>
        <!-- Paginação -->
        <div id="historyPagNav" class="d-none" style="padding:1rem 1.5rem;display:flex;gap:0.5rem;justify-content:center;">
            <button class="btn-quest btn-quest--ghost" id="prevPage" onclick="changePage(-1)">
                <i class="bi bi-chevron-left"></i> Anterior
            </button>
            <button class="btn-quest btn-quest--ghost" id="nextPage" onclick="changePage(1)">
                Próximo <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    </div>
</main>

<script>
let currentPage = 1;
const limite    = 20;
let totalItems  = 0;

async function loadHistory(pagina) {
    const loading = document.getElementById('historyLoading');
    const table   = document.getElementById('historyTable');
    const empty   = document.getElementById('historyEmpty');
    const body    = document.getElementById('historyBody');
    const pag     = document.getElementById('historyPaginacao');
    const navDiv  = document.getElementById('historyPagNav');

    loading.style.display = 'block';
    table.style.display   = 'none';
    empty.classList.add('d-none');

    try {
        const resp = await fetch(`/api/history/list.php?pagina=${pagina}&limite=${limite}`);
        const data = await resp.json();

        loading.style.display = 'none';
        totalItems = data.total || 0;

        if (!data.success || data.partidas.length === 0) {
            empty.classList.remove('d-none');
            return;
        }

        const inicio = (pagina - 1) * limite + 1;
        const fim    = Math.min(pagina * limite, totalItems);
        pag.textContent = `Mostrando ${inicio}–${fim} de ${totalItems}`;

        body.innerHTML = data.partidas.map(p => `
            <tr>
                <td style="color:var(--text-muted);font-size:0.82rem;">${formatDate(p.data_partida)}</td>
                <td>
                    <span style="font-size:1.2rem;">${p.inimigo_sprite}</span>
                    <span style="margin-left:0.4rem;">${escapeHtml(p.inimigo_nome)}</span>
                </td>
                <td>
                    <span class="badge-quest ${p.resultado === 'vitoria' ? 'badge-quest--success' : 'badge-quest--danger'}">
                        ${p.resultado === 'vitoria' ? '✓ Vitória' : '✗ Derrota'}
                    </span>
                </td>
                <td style="font-weight:600;">${p.wpm} WPM</td>
                <td>${parseFloat(p.precisao).toFixed(1)}%</td>
                <td style="color:var(--color-accent);font-weight:700;">${formatNumber(parseInt(p.pontuacao))}</td>
                <td><span class="badge-quest badge-quest--primary">Nv.${p.nivel_atingido}</span></td>
            </tr>
        `).join('');

        table.style.display = 'table';

        // Controle de paginação
        const totalPages = Math.ceil(totalItems / limite);
        if (totalPages > 1) {
            navDiv.classList.remove('d-none');
            document.getElementById('prevPage').disabled = pagina <= 1;
            document.getElementById('nextPage').disabled = pagina >= totalPages;
        } else {
            navDiv.classList.add('d-none');
        }

    } catch (e) {
        loading.style.display = 'none';
        empty.textContent = 'Erro ao carregar histórico.';
        empty.classList.remove('d-none');
    }
}

function changePage(dir) {
    const totalPages = Math.ceil(totalItems / limite);
    const next = currentPage + dir;
    if (next >= 1 && next <= totalPages) {
        currentPage = next;
        loadHistory(currentPage);
    }
}

function escapeHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

loadHistory(1);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
