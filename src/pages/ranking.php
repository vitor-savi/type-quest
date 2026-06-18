<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

session_name(SESSION_NAME);
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: /pages/auth/login.php');
    exit;
}

$usuario = $_SESSION['usuario'];

// Ligas do usuário para o filtro do selector
$pdo = getDB();
$stmtL = $pdo->prepare(
    'SELECT l.idLiga, l.nome FROM USUARIO_LIGA ul
     JOIN LIGA l ON l.idLiga = ul.FK_LIGA_idLiga
     WHERE ul.FK_USUARIO_idUsuario = :id ORDER BY l.nome'
);
$stmtL->execute([':id' => $usuario['idUsuario']]);
$ligas = $stmtL->fetchAll();

$pageTitle   = 'Ranking';
$bodyClass   = 'layout-inner';
$currentPage = 'ranking';
$pageCss     = ['/assets/css/dashboard.css'];
$pageJs      = [];
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/topbar.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<main class="page-content">
    <div class="page-header">
        <h1 class="page-header__title"><i class="bi bi-trophy-fill" style="color:var(--color-accent)"></i> Ranking</h1>
        <p class="page-header__sub">Compare sua performance com outros jogadores</p>
    </div>

    <!-- Tabs de tipo -->
    <div class="tabs-quest" id="rankingTabs">
        <button class="tab-quest active" data-tipo="total" data-liga="">Global — Desde sempre</button>
        <button class="tab-quest" data-tipo="semanal" data-liga="">Global — Semanal</button>
        <?php foreach ($ligas as $liga): ?>
        <button class="tab-quest" data-tipo="total" data-liga="<?= $liga['idLiga'] ?>">
            <?= htmlspecialchars($liga['nome']) ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Tabela de ranking -->
    <div class="section-card">
        <div class="section-card__header">
            <span class="section-card__title" id="rankingTitle">
                <i class="bi bi-list-ol"></i> Ranking Global
            </span>
            <span id="rankingMyPos" style="color:var(--text-secondary);font-size:0.875rem;"></span>
        </div>
        <div class="section-card__body" style="padding:0;">
            <div id="rankingLoading" style="padding:2rem;text-align:center;">
                <div class="loading-spinner" style="margin:auto;"></div>
            </div>
            <table class="table-quest" id="rankingTable" style="display:none;">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Jogador</th>
                        <th>Pontuação</th>
                        <th>WPM Médio</th>
                        <th>Precisão</th>
                    </tr>
                </thead>
                <tbody id="rankingBody"></tbody>
            </table>
            <div id="rankingEmpty" class="d-none" style="padding:2rem;text-align:center;color:var(--text-muted);">
                Nenhum dado disponível ainda.
            </div>
        </div>
    </div>
</main>

<script>
const ME = '<?= htmlspecialchars($usuario['nome_usuario']) ?>';

async function loadRanking(tipo, idLiga) {
    const loading = document.getElementById('rankingLoading');
    const table   = document.getElementById('rankingTable');
    const empty   = document.getElementById('rankingEmpty');
    const title   = document.getElementById('rankingTitle');
    const myPos   = document.getElementById('rankingMyPos');
    const body    = document.getElementById('rankingBody');

    loading.classList.remove('d-none');
    table.style.display = 'none';
    empty.classList.add('d-none');

    try {
        let url;
        if (idLiga) {
            url = `/api/ranking/league.php?idLiga=${idLiga}&tipo=${tipo}`;
            title.innerHTML = `<i class="bi bi-people-fill"></i> Liga`;
        } else {
            url = `/api/ranking/global.php?tipo=${tipo}&limite=50`;
            title.innerHTML = `<i class="bi bi-list-ol"></i> Ranking ${tipo === 'semanal' ? 'Semanal' : 'Global'}`;
        }

        const resp = await fetch(url);
        const data = await resp.json();

        loading.classList.add('d-none');

        if (!data.success || data.ranking.length === 0) {
            empty.classList.remove('d-none');
            return;
        }

        if (idLiga && data.liga) title.innerHTML = `<i class="bi bi-people-fill"></i> ${data.liga.nome}`;
        myPos.textContent = data.posicao_usuario ? `Sua posição: #${data.posicao_usuario}` : '';

        body.innerHTML = data.ranking.map(r => `
            <tr class="${r.eh_usuario ? 'row--highlight' : ''}">
                <td>
                    <span class="rank-pos rank-pos--${r.posicao}">
                        ${r.posicao === 1 ? '🥇' : r.posicao === 2 ? '🥈' : r.posicao === 3 ? '🥉' : '#' + r.posicao}
                    </span>
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:0.6rem;">
                        <span class="avatar-letter">${r.nome_usuario[0].toUpperCase()}</span>
                        <span>${escapeHtml(r.nome_usuario)}</span>
                        ${r.eh_usuario ? '<span class="badge-quest badge-quest--primary" style="font-size:0.65rem;">Você</span>' : ''}
                    </div>
                </td>
                <td style="color:var(--color-accent);font-weight:700;">${formatNumber(r.pontuacao)}</td>
                <td>${r.wpm_medio} WPM</td>
                <td>${r.precisao_media}%</td>
            </tr>
        `).join('');

        table.style.display = 'table';

    } catch (e) {
        loading.classList.add('d-none');
        empty.textContent = 'Erro ao carregar ranking.';
        empty.classList.remove('d-none');
    }
}

function escapeHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// Tab navigation
document.getElementById('rankingTabs').addEventListener('click', (e) => {
    const tab = e.target.closest('.tab-quest');
    if (!tab) return;
    document.querySelectorAll('.tab-quest').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    loadRanking(tab.dataset.tipo, tab.dataset.liga);
});

// Carrega ao iniciar
loadRanking('total', '');
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
