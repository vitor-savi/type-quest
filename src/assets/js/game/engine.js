/**
 * TypeQuest — engine.js
 * Motor principal do jogo: loop de renderização Canvas + coordenação geral
 */

// Resultado da última batalha — preenchido pelo onFinish, usado pelos botões
let lastBattleResult = null;

// Estado global da sessão de jogo
let gameState = {
    canvas:   null,
    ctx:      null,
    ui:       null,
    battle:   null,
    player:   null,
    enemy:    null,
    palavras: [],
    nivel:    1,
    animFrame: null,
    inimigo:  null,
    nomeUsuario:    '',
    totalPontuacao: 0,
};

/** Inicializa o canvas e carrega dados da API */
async function initGame() {
    gameState.canvas = document.getElementById('battleCanvas');
    gameState.ctx    = gameState.canvas.getContext('2d');

    // Ajusta tamanho do canvas ao contêiner
    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);

    // Exibe loading
    showGameLoading(true);

    try {
        // Busca nível do jogador via data-attribute do PHP
        const dataEl = document.getElementById('gameData');
        gameState.nivel          = parseInt(dataEl.dataset.nivel)          || 1;
        gameState.nomeUsuario    = dataEl.dataset.nomeUsuario             || 'Herói';
        gameState.totalPontuacao = parseInt(dataEl.dataset.totalPontuacao) || 0;

        const resp = await fetch(`/api/game/get_words.php?nivel=${gameState.nivel}&quantidade=10`);
        const data = await resp.json();

        if (!data.success) {
            showGameError('Erro ao carregar partida: ' + (data.message || 'Tente novamente.'));
            return;
        }

        gameState.palavras = data.palavras;
        gameState.inimigo  = data.inimigo;

        // Cria as entidades
        gameState.player = new Player(gameState.nomeUsuario, gameState.nivel);
        gameState.enemy  = new Enemy(gameState.inimigo);
        gameState.ui     = new GameUI(gameState.ctx, gameState.canvas);

        showGameLoading(false);
        showStartScreen();

    } catch (err) {
        showGameError('Falha de conexão. Verifique sua internet.');
    }
}

function resizeCanvas() {
    const c = gameState.canvas;
    const container = c.parentElement;
    const w = Math.max(800, container.clientWidth);
    c.width  = w;
    c.height = Math.round(w * 0.45);
}

/** Exibe a tela de pré-batalha com o inimigo */
function showStartScreen() {
    const overlay = document.getElementById('startOverlay');
    if (overlay && gameState.inimigo) {
        document.getElementById('startEnemySprite').textContent = gameState.inimigo.sprite;
        document.getElementById('startEnemyName').textContent   = gameState.inimigo.nome;
        document.getElementById('startEnemyHP').textContent     = gameState.inimigo.hp;
        document.getElementById('startNivel').textContent       = gameState.nivel;
        overlay.classList.remove('d-none');
    }
}

/** Começa a batalha real */
function startBattle() {
    const overlay = document.getElementById('startOverlay');
    if (overlay) overlay.classList.add('d-none');

    const input = document.getElementById('typingInput');
    if (input) {
        input.value    = '';
        input.disabled = false;
        input.focus();
    }

    gameState.battle = new Battle(
        gameState.player,
        gameState.enemy,
        gameState.palavras,
        gameState.nivel
    );

    // Callbacks da batalha
    gameState.battle.onWordChange = (word, idx, total) => {
        updateWordDisplay(word);
        updateHUD(idx + 1, total);
        clearTypingInput();
    };

    gameState.battle.onPlayerDamage = (dano) => {
        gameState.ui.flashDamage('rgba(239,68,68,');
        gameState.ui.addFloatingNumber(
            gameState.canvas.width * 0.2 + gameState.player.shakeOffset,
            gameState.canvas.height * 0.35,
            `-${dano}`,
            '#ef4444'
        );
        updateHPBars();
    };

    gameState.battle.onEnemyDamage = (dano) => {
        gameState.ui.flashDamage('rgba(245,158,11,');
        gameState.ui.addFloatingNumber(
            gameState.canvas.width * 0.8 + gameState.enemy.shakeOffset,
            gameState.canvas.height * 0.35,
            `-${dano}`,
            '#10b981'
        );
        updateHPBars();
    };

    gameState.battle.onPontuacao = (pts) => {
        const el = document.getElementById('hudPontuacao');
        if (el) el.textContent = formatNumber(pts);
    };

    gameState.battle.onFinish = (result) => {
        stopGameLoop();
        lastBattleResult = result;
        showResultModal(result);
    };

    gameState.battle.start();
    startGameLoop();
}

/** Loop principal de renderização */
function startGameLoop() {
    function loop() {
        if (!gameState.battle || gameState.battle.state === 'finished') return;

        const { ctx, canvas, ui, player, enemy, battle } = gameState;

        // Atualiza entidades
        player.update();
        enemy.update();
        ui.update();

        // --- RENDER ---
        ui.drawBackground();
        ui.drawDivider();

        const midX = canvas.width / 2;
        const midY = canvas.height / 2;

        // === LADO ESQUERDO — HERÓI ===
        const px = canvas.width * 0.22 + player.shakeOffset;
        const py = midY - 20;

        ui.drawSprite(player.sprite, px, py, 72);
        ui.drawTitle(player.nome, px, py + 52, 13, '#e2e0ff');
        ui.drawText(`Nível ${player.nivel}`, px, py + 68, 11, '#9ca3af');
        ui.drawHPBar(px - 60, py + 82, 120, 10, player.hpPercent, player.hpColor);
        ui.drawText(`${player.hp}/${player.hpMax}`, px, py + 100, 10, '#9ca3af');

        // === LADO DIREITO — INIMIGO ===
        const ex = canvas.width * 0.78 + enemy.shakeOffset;
        const ey = midY - 20 + enemy.floatY;

        ui.drawSprite(enemy.sprite, ex, ey, 72);
        ui.drawTitle(enemy.nome, ex, ey + 52, 13, '#e2e0ff');
        ui.drawText(enemy.tipo, ex, ey + 68, 11, '#9ca3af');
        ui.drawHPBar(ex - 60, ey + 82, 120, 10, enemy.hpPercent,
            { from: '#ef4444', to: '#f87171' });
        ui.drawText(`${enemy.hp}/${enemy.hpMax}`, ex, ey + 100, 10, '#9ca3af');

        // === CENTRO — PALAVRA ATUAL ===
        drawWordCenter(ctx, canvas, battle);

        // === HUD SUPERIOR ===
        drawHUDCanvas(ctx, canvas, battle);

        // === BARRA DE TEMPO ===
        drawTimeBar(ctx, canvas, battle);

        // Flash e números flutuantes
        ui.drawFlash();
        ui.drawFloatingNumbers();

        // WPM em tempo real
        const wpmEl = document.getElementById('hudWPM');
        if (wpmEl) wpmEl.textContent = battle.currentWPM;

        gameState.animFrame = requestAnimationFrame(loop);
    }

    gameState.animFrame = requestAnimationFrame(loop);
}

function stopGameLoop() {
    if (gameState.animFrame) cancelAnimationFrame(gameState.animFrame);
}

/** Desenha a palavra atual no centro com letras coloridas */
function drawWordCenter(ctx, canvas, battle) {
    ctx.save();
    const word    = battle.currentWord;
    const typed   = battle.typedCorrect;
    const cx      = canvas.width / 2;
    const cy      = canvas.height * 0.42;
    const fontSize = Math.max(28, Math.min(42, 400 / word.length));

    ctx.font = `bold ${fontSize}px 'Cinzel', serif`;
    ctx.textAlign    = 'center';
    ctx.textBaseline = 'middle';

    // Mede a largura total da palavra para centralizar letra a letra
    const totalWidth = ctx.measureText(word).width;
    let x = cx - totalWidth / 2;

    for (let i = 0; i < word.length; i++) {
        const char = word[i];
        const w    = ctx.measureText(char).width;

        if (i < typed) {
            // Já digitado corretamente — verde/roxo
            ctx.fillStyle = '#7c3aed';
        } else {
            // Aguardando — branco
            ctx.fillStyle = '#e2e0ff';
        }

        // Sombra para legibilidade
        ctx.shadowColor = 'rgba(0,0,0,0.9)';
        ctx.shadowBlur  = 6;
        ctx.fillText(char, x + w / 2, cy);
        ctx.shadowBlur  = 0;

        x += w;
    }
    ctx.restore();
}

/** Barra de tempo decrescente */
function drawTimeBar(ctx, canvas, battle) {
    ctx.save();
    const bx = canvas.width * 0.25;
    const by = canvas.height * 0.58;
    const bw = canvas.width * 0.5;
    const bh = 6;
    const pct = battle.timePercent;

    // Fundo
    ctx.fillStyle = 'rgba(0,0,0,0.4)';
    ctx.beginPath();
    ctx.roundRect(bx, by, bw, bh, 3);
    ctx.fill();

    // Preenchimento
    let color = '#10b981';
    if (pct < 0.5)  color = '#f59e0b';
    if (pct < 0.25) color = '#ef4444';

    const fillW = bw * pct;
    if (fillW > 0) {
        ctx.fillStyle = color;
        ctx.beginPath();
        ctx.roundRect(bx, by, fillW, bh, 3);
        ctx.fill();
    }
    ctx.restore();
}

/** HUD no topo do canvas */
function drawHUDCanvas(ctx, canvas, battle) {
    ctx.save();
    const pad = 16;
    ctx.font = '13px Inter, sans-serif';
    ctx.textBaseline = 'middle';
    ctx.fillStyle = 'rgba(0,0,0,0.5)';
    ctx.beginPath();
    ctx.roundRect(pad, pad, canvas.width - pad * 2, 32, 6);
    ctx.fill();

    // Pontuação
    ctx.font = 'bold 13px Cinzel, serif';
    ctx.fillStyle = '#f59e0b';
    ctx.textAlign = 'left';
    ctx.fillText(`⚡ ${formatNumber(battle.pontuacao)}`, pad + 12, pad + 16);

    // Palavra atual
    ctx.fillStyle = '#9ca3af';
    ctx.textAlign = 'center';
    ctx.font = '12px Inter, sans-serif';
    ctx.fillText(`Palavra ${battle.wordIndex + 1} / ${battle.palavras.length}`, canvas.width / 2, pad + 16);

    // WPM
    ctx.fillStyle = '#7c3aed';
    ctx.textAlign = 'right';
    ctx.font = 'bold 13px Cinzel, serif';
    ctx.fillText(`${battle.currentWPM} WPM`, canvas.width - pad - 12, pad + 16);
    ctx.restore();
}

/** Atualiza a exibição da palavra atual no input area */
function updateWordDisplay(word) {
    const el = document.getElementById('currentWordDisplay');
    if (el) el.textContent = word;
}

function clearTypingInput() {
    const input = document.getElementById('typingInput');
    if (input) { input.value = ''; input.focus(); }
}

function updateHPBars() {
    const { player, enemy } = gameState;
    const ph = document.getElementById('playerHP');
    const pb = document.getElementById('playerHPBar');
    const eh = document.getElementById('enemyHP');
    const eb = document.getElementById('enemyHPBar');
    if (ph) ph.textContent = `${player.hp}/${player.hpMax}`;
    if (pb) pb.style.width = `${player.hpPercent * 100}%`;
    if (eh) eh.textContent = `${enemy.hp}/${enemy.hpMax}`;
    if (eb) eb.style.width = `${enemy.hpPercent * 100}%`;
}

function updateHUD(wordNum, total) {
    const el = document.getElementById('hudWordCount');
    if (el) el.textContent = `${wordNum}/${total}`;
}

function showGameLoading(show) {
    const el = document.getElementById('gameLoading');
    if (el) el.classList.toggle('d-none', !show);
}

function showGameError(msg) {
    showGameLoading(false);
    const el = document.getElementById('gameError');
    if (el) {
        el.textContent = msg;
        el.classList.remove('d-none');
    }
}

/** Exibe modal de resultado sem salvar — o save ocorre nos botões */
function showResultModal(result) {
    const isVictory = result.winner === 'player';
    const modal     = document.getElementById('resultModal');

    // Reseta estado dos botões (podem estar desabilitados de uma rodada anterior)
    ['btnJogarNovamente', 'btnProximaFase', 'btnDashboard'].forEach(id => {
        const el = document.getElementById(id);
        if (el) setButtonLoading(el, false);
    });

    document.getElementById('resultTitle').textContent    = isVictory ? '⚔️ Vitória!' : '💀 Derrota!';
    document.getElementById('resultTitle').style.color    = isVictory ? '#10b981' : '#ef4444';
    document.getElementById('resultWPM').textContent      = result.wpm;
    document.getElementById('resultPrecisao').textContent = result.precisao.toFixed(1) + '%';
    document.getElementById('resultPontuacao').textContent = formatNumber(result.pontuacao);
    document.getElementById('resultTotal').textContent    = formatNumber(gameState.totalPontuacao + result.pontuacao);

    // "Próxima Fase" só aparece na vitória
    const btnProxima = document.getElementById('btnProximaFase');
    if (btnProxima) btnProxima.classList.toggle('d-none', !isVictory);

    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}

/** Envia o resultado da partida para o servidor */
async function saveMatchAsync(resultado) {
    if (!lastBattleResult) return null;
    const input = document.getElementById('typingInput');
    if (input) input.disabled = true;
    try {
        const data = await apiFetch('/api/game/save_match.php', {
            idInimigo:        gameState.inimigo.idInimigo,
            pontuacao:        lastBattleResult.pontuacao,
            wpm:              lastBattleResult.wpm,
            precisao:         lastBattleResult.precisao,
            resultado,
            duracao_segundos: lastBattleResult.duracaoSegundos,
            palavras:         lastBattleResult.palavras,
        });
        return data;
    } catch (err) {
        return null;
    }
}

/** Reinicia a batalha contra o mesmo inimigo, sem salvar */
function restartBattleInPlace() {
    stopGameLoop();
    lastBattleResult = null;

    gameState.player = new Player(gameState.nomeUsuario, gameState.nivel);
    gameState.enemy  = new Enemy(gameState.inimigo);
    gameState.ui     = new GameUI(gameState.ctx, gameState.canvas);

    const input = document.getElementById('typingInput');
    if (input) { input.disabled = true; input.value = ''; input.placeholder = 'Aguardando início...'; }

    const hudPts = document.getElementById('hudPontuacao');
    if (hudPts) hudPts.textContent = '0';
    const hudWpm = document.getElementById('hudWPM');
    if (hudWpm) hudWpm.textContent = '0';

    updateHPBars();
    showStartScreen();
}

/** Carrega uma nova batalha com novo inimigo do servidor */
async function loadNewBattle() {
    showGameLoading(true);
    try {
        const resp = await fetch(`/api/game/get_words.php?nivel=${gameState.nivel}&quantidade=10`);
        const data = await resp.json();
        if (!data.success) { showGameError('Erro ao carregar nova fase.'); return; }

        lastBattleResult   = null;
        gameState.palavras = data.palavras;
        gameState.inimigo  = data.inimigo;
        gameState.player   = new Player(gameState.nomeUsuario, gameState.nivel);
        gameState.enemy    = new Enemy(gameState.inimigo);
        gameState.ui       = new GameUI(gameState.ctx, gameState.canvas);

        const input = document.getElementById('typingInput');
        if (input) { input.disabled = true; input.value = ''; }

        showGameLoading(false);
        showStartScreen();
    } catch (err) {
        showGameError('Falha ao carregar nova fase. Recarregue a página.');
    }
}

/** Botão Jogar Novamente — reinicia contra o mesmo inimigo, sem salvar */
function btnClickJogarNovamente() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('resultModal'));
    if (modal) modal.hide();
    setTimeout(restartBattleInPlace, 350);
}

/** Botão Próxima Fase — salva vitória e carrega novo inimigo */
async function btnClickProximaFase() {
    const btn = document.getElementById('btnProximaFase');
    setButtonLoading(btn, true);

    const data = await saveMatchAsync('vitoria');

    if (!data || !data.success) {
        showToast('Erro ao salvar partida. Tente novamente.', 'error');
        setButtonLoading(btn, false);
        return;
    }

    gameState.totalPontuacao = data.pontuacao_total || 0;
    document.getElementById('resultTotal').textContent = formatNumber(gameState.totalPontuacao);
    if (data.nivel_novo) gameState.nivel = data.nivel_novo;

    setButtonLoading(btn, false);
    const modal = bootstrap.Modal.getInstance(document.getElementById('resultModal'));
    if (modal) modal.hide();
    setTimeout(loadNewBattle, 350);
}

/** Botão Dashboard — salva (vitória ou derrota) e redireciona */
async function btnClickDashboard() {
    const btn = document.getElementById('btnDashboard');
    setButtonLoading(btn, true);

    const resultado = (lastBattleResult && lastBattleResult.winner === 'player') ? 'vitoria' : 'derrota';
    const data = await saveMatchAsync(resultado);

    if (!data || !data.success) {
        showToast('Erro ao salvar partida. Tente novamente.', 'error');
        setButtonLoading(btn, false);
        return;
    }

    gameState.totalPontuacao = data.pontuacao_total || 0;
    window.location.href = '/pages/dashboard.php';
}

// Listener do input de digitação
document.addEventListener('DOMContentLoaded', () => {
    const typingInput = document.getElementById('typingInput');
    if (typingInput) {
        typingInput.addEventListener('input', (e) => {
            if (!gameState.battle || gameState.battle.state !== 'running') return;
            if (e.isComposing) return; // ignora composição IME em andamento
            const char = e.data;       // apenas o caractere inserido neste evento
            if (char && char.length === 1) {
                gameState.battle.processInput(char.toLowerCase());
            }
            e.target.value = '';
        });

        // Mantém o foco no input durante o jogo
        document.addEventListener('click', () => {
            if (gameState.battle && gameState.battle.state === 'running') {
                typingInput.focus();
            }
        });
    }

    // Inicializa o jogo ao carregar a página
    initGame();
});
