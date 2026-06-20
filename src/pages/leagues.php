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

$pageTitle   = 'Ligas';
$bodyClass   = 'layout-inner';
$currentPage = 'leagues';
$pageCss     = ['/assets/css/dashboard.css'];
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/topbar.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<main class="page-content">
    <div class="page-header">
        <h1 class="page-header__title"><i class="bi bi-people-fill" style="color:var(--color-success)"></i> Ligas</h1>
        <p class="page-header__sub">Crie ou entre em ligas e compita com amigos</p>
    </div>

    <!-- Ações -->
    <div style="display:flex;gap:0.75rem;margin-bottom:2rem;flex-wrap:wrap;">
        <button class="btn-quest btn-quest--primary" data-bs-toggle="modal" data-bs-target="#createLeagueModal">
            <i class="bi bi-plus-circle-fill"></i> Criar Liga
        </button>
        <button class="btn-quest btn-quest--ghost" data-bs-toggle="modal" data-bs-target="#joinLeagueModal">
            <i class="bi bi-door-open-fill"></i> Entrar em Liga
        </button>
    </div>

    <!-- Lista de ligas -->
    <div id="leaguesLoading" style="text-align:center;padding:2rem;">
        <div class="loading-spinner" style="margin:auto;"></div>
    </div>
    <div id="leaguesList" class="d-none"></div>
    <div id="leaguesEmpty" class="d-none" style="text-align:center;padding:3rem;color:var(--text-muted);">
        <i class="bi bi-people" style="font-size:3rem;display:block;margin-bottom:1rem;"></i>
        <p>Você ainda não participa de nenhuma liga.</p>
    </div>
</main>

<!-- Modal: Criar Liga -->
<div class="modal fade modal-quest" id="createLeagueModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-shield-fill-plus"></i> Criar Nova Liga</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert-quest alert-quest--error d-none" id="createError">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span id="createErrorMsg"></span>
                </div>
                <form class="form-quest" id="createLeagueForm">
                    <div class="form-quest__group">
                        <label class="form-quest__label">Nome da Liga</label>
                        <input type="text" id="createNome" class="form-quest__input" placeholder="Guilda dos Magos Supremos" required maxlength="80">
                    </div>
                    <div class="form-quest__group">
                        <label class="form-quest__label">Descrição (opcional)</label>
                        <input type="text" id="createDesc" class="form-quest__input" placeholder="Uma breve descrição...">
                    </div>
                    <div class="form-quest__group">
                        <label class="form-quest__label">Palavra-chave secreta</label>
                        <input type="text" id="createChave" class="form-quest__input" placeholder="A senha para entrar na liga" required>
                        <span class="form-quest__error visible" style="color:var(--text-muted);">Compartilhe esta senha apenas com quem quiser na liga.</span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-quest btn-quest--ghost" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn-quest btn-quest--primary" id="createLeagueBtn" onclick="createLeague()">
                    <i class="bi bi-plus-circle-fill"></i> Criar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Entrar em Liga -->
<div class="modal fade modal-quest" id="joinLeagueModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-door-open-fill"></i> Entrar em Liga</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert-quest alert-quest--error d-none" id="joinError">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span id="joinErrorMsg"></span>
                </div>
                <form class="form-quest" id="joinLeagueForm">
                    <div class="form-quest__group">
                        <label class="form-quest__label">Nome da Liga</label>
                        <input type="text" id="joinNome" class="form-quest__input" placeholder="Nome exato da liga" required>
                    </div>
                    <div class="form-quest__group">
                        <label class="form-quest__label">Palavra-chave</label>
                        <input type="text" id="joinChave" class="form-quest__input" placeholder="Senha da liga" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-quest btn-quest--ghost" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn-quest btn-quest--primary" id="joinLeagueBtn" onclick="joinLeague()">
                    <i class="bi bi-door-open-fill"></i> Entrar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
async function loadLeagues() {
    const loading = document.getElementById('leaguesLoading');
    const list    = document.getElementById('leaguesList');
    const empty   = document.getElementById('leaguesEmpty');

    try {
        const data = await apiFetch('/api/leagues/list.php', null, 'GET');
        loading.classList.add('d-none');

        if (!data.success) {
            empty.textContent = data.message || 'Erro ao carregar ligas.';
            empty.classList.remove('d-none');
            return;
        }

        if (data.ligas.length === 0) {
            empty.classList.remove('d-none');
            return;
        }

        list.innerHTML = data.ligas.map(liga => `
            <div class="section-card" style="margin-bottom:1rem;">
                <div class="section-card__header">
                    <span class="section-card__title">
                        <i class="bi bi-shield-fill" style="color:var(--color-primary)"></i>
                        ${escapeHtml(liga.nome)}
                        ${liga.descricao ? `<span style="font-size:0.75rem;color:var(--text-muted);font-family:var(--font-body);font-weight:400;">${escapeHtml(liga.descricao)}</span>` : ''}
                    </span>
                    <div style="display:flex;gap:0.5rem;align-items:center;">
                        <span style="color:var(--text-muted);font-size:0.8rem;"><i class="bi bi-people"></i> ${liga.membros} membros</span>
                    </div>
                </div>
                <div class="section-card__body">
                    <div style="display:flex;gap:1rem;margin-bottom:1rem;flex-wrap:wrap;">
                        <div class="stat-card" style="flex:1;min-width:120px;">
                            <div class="stat-card__icon stat-card__icon--gold"><i class="bi bi-star-fill"></i></div>
                            <div class="stat-card__body">
                                <div class="stat-card__value">${formatNumber(liga.pontuacao_total)}</div>
                                <div class="stat-card__label">Pts Total</div>
                            </div>
                        </div>
                        <div class="stat-card" style="flex:1;min-width:120px;">
                            <div class="stat-card__icon stat-card__icon--purple"><i class="bi bi-calendar-week"></i></div>
                            <div class="stat-card__body">
                                <div class="stat-card__value">${formatNumber(liga.pontuacao_semanal)}</div>
                                <div class="stat-card__label">Pts Semana</div>
                            </div>
                        </div>
                        ${liga.posicao ? `
                        <div class="stat-card" style="flex:1;min-width:120px;">
                            <div class="stat-card__icon stat-card__icon--green"><i class="bi bi-trophy-fill"></i></div>
                            <div class="stat-card__body">
                                <div class="stat-card__value">#${liga.posicao}</div>
                                <div class="stat-card__label">Sua Posição</div>
                            </div>
                        </div>` : ''}
                    </div>
                    ${liga.top_membros && liga.top_membros.length > 0 ? `
                    <div>
                        <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.5rem;font-family:var(--font-title);">Top Membros</div>
                        ${liga.top_membros.map((m, i) => `
                        <div class="mini-ranking__item">
                            <span class="mini-ranking__pos">${i === 0 ? '🥇' : i === 1 ? '🥈' : '🥉'}</span>
                            <span class="avatar-letter" style="width:24px;height:24px;font-size:0.7rem;">${m.nome_usuario[0].toUpperCase()}</span>
                            <span class="mini-ranking__name">${escapeHtml(m.nome_usuario)}</span>
                            <span class="mini-ranking__score">${formatNumber(m.pontuacao_total)}pts</span>
                        </div>`).join('')}
                    </div>` : ''}
                </div>
            </div>
        `).join('');

        list.classList.remove('d-none');
    } catch (e) {
        loading.classList.add('d-none');
        empty.textContent = 'Erro ao carregar ligas.';
        empty.classList.remove('d-none');
    }
}

async function createLeague() {
    const nome  = document.getElementById('createNome').value.trim();
    const desc  = document.getElementById('createDesc').value.trim();
    const chave = document.getElementById('createChave').value.trim();

    if (!nome || !chave) { showError('createError', 'Preencha nome e palavra-chave.'); return; }

    const btn = document.getElementById('createLeagueBtn');
    setButtonLoading(btn, true);
    hideAlert('createError');

    const data = await apiFetch('/api/leagues/create.php', { nome, descricao: desc, palavra_chave: chave });

    if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('createLeagueModal')).hide();
        showToast('Liga criada com sucesso!', 'success');
        loadLeagues();
    } else {
        showError('createError', data.message);
    }
    setButtonLoading(btn, false);
}

async function joinLeague() {
    const nome  = document.getElementById('joinNome').value.trim();
    const chave = document.getElementById('joinChave').value.trim();

    if (!nome || !chave) { showError('joinError', 'Preencha nome e palavra-chave.'); return; }

    const btn = document.getElementById('joinLeagueBtn');
    setButtonLoading(btn, true);
    hideAlert('joinError');

    const data = await apiFetch('/api/leagues/join.php', { nome_liga: nome, palavra_chave: chave });

    if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('joinLeagueModal')).hide();
        showToast('Você entrou na liga!', 'success');
        loadLeagues();
    } else {
        showError('joinError', data.message);
    }
    setButtonLoading(btn, false);
}

function escapeHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

loadLeagues();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
