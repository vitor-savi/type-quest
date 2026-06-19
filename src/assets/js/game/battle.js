/**
 * TypeQuest — battle.js
 * Lógica de batalha por turnos: dano, turno de palavra, timers, pontuação
 */
class Battle {
    constructor(player, enemy, palavras, nivel) {
        this.player  = player;
        this.enemy   = enemy;
        this.palavras = palavras;
        this.nivel   = nivel;

        this.wordIndex       = 0;
        this.currentWord     = '';
        this.typedCorrect    = 0; // letras digitadas corretamente na palavra atual
        this.totalAcertos       = 0; // palavras inteiras acertadas
        this.totalChars         = 0; // total de caracteres digitados
        this.totalCharsCorretos = 0; // caracteres digitados corretamente
        this.startTime       = null;
        this.wordStartTime   = null;
        this.pontuacao       = 0;
        this.combo           = 0;
        this.maxCombo        = 0;

        // Resultado de cada palavra para salvar no banco
        this.resultadoPalavras = [];

        // Timer por palavra (em segundos, dependendo da dificuldade)
        this.timePerWord    = this._calcTimePerWord();
        this.timeRemaining  = this.timePerWord;
        this.timerInterval  = null;

        // Estado da batalha
        this.state = 'idle'; // idle | running | finished
        this.winner = null;  // 'player' | 'enemy'

        // Callbacks para a UI atualizar
        this.onWordChange   = null;
        this.onPlayerDamage = null;
        this.onEnemyDamage  = null;
        this.onFinish       = null;
        this.onPontuacao    = null;
    }

    _calcTimePerWord() {
        const dif = Math.ceil(this.nivel / 2);
        if (dif <= 2) return 8;
        if (dif === 3) return 10;
        return 12;
    }

    start() {
        this.state         = 'running';
        this.startTime     = Date.now();
        this._nextWord();
        this._startWordTimer();
    }

    _nextWord() {
        if (this.wordIndex >= this.palavras.length) {
            this._endBattle();
            return;
        }
        this.currentWord    = this.palavras[this.wordIndex].texto;
        this.typedCorrect   = 0;
        this.timeRemaining  = this.timePerWord;
        this.wordStartTime  = Date.now();
        if (this.onWordChange) this.onWordChange(this.currentWord, this.wordIndex, this.palavras.length);
    }

    _startWordTimer() {
        clearInterval(this.timerInterval);
        this.timerInterval = setInterval(() => {
            if (this.state !== 'running') {
                clearInterval(this.timerInterval);
                return;
            }
            this.timeRemaining -= 0.1;
            if (this.timeRemaining <= 0) {
                this.timeRemaining = 0;
                this._onWordTimeout();
            }
        }, 100);
    }

    _onWordTimeout() {
        // Tempo esgotado: inimigo ataca
        this._enemyAttack();
        this.combo = 0;

        this.resultadoPalavras.push({
            idPalavra: this.palavras[this.wordIndex].idPalavra,
            acertou: 0,
        });
        this.wordIndex++;
        this._nextWord();
    }

    /** Processa cada tecla digitada pelo jogador */
    processInput(char) {
        if (this.state !== 'running') return;

        const expected = this.currentWord[this.typedCorrect];

        if (char === expected) {
            this.typedCorrect++;
            this.totalChars++;
            this.totalCharsCorretos++;

            if (this.typedCorrect === this.currentWord.length) {
                // Palavra completa corretamente
                this._playerAttack();
                this.totalAcertos++;
                this.combo++;
                if (this.combo > this.maxCombo) this.maxCombo = this.combo;

                this.resultadoPalavras.push({
                    idPalavra: this.palavras[this.wordIndex].idPalavra,
                    acertou: 1,
                });
                this.wordIndex++;
                this._nextWord();
            }
        } else {
            // Erro: inimigo ataca imediatamente
            this.totalChars++;
            this._enemyAttack();
            this.combo = 0;
            this.typedCorrect = 0; // reseta a palavra
        }
    }

    _playerAttack() {
        const dano = this.player.dano;
        this.enemy.takeDamage(dano);

        // Pontuação: 100 base + bônus de combo e tempo
        const timeBonus = Math.floor(this.timeRemaining * 5);
        const comboBonus = this.combo * 10;
        this.pontuacao += 100 + timeBonus + comboBonus;

        if (this.onEnemyDamage) this.onEnemyDamage(dano);
        if (this.onPontuacao)   this.onPontuacao(this.pontuacao);

        if (this.enemy.isDead) {
            this._endBattle('player');
        }
    }

    _enemyAttack() {
        const dano = this.enemy.dano;
        this.player.takeDamage(dano);

        if (this.onPlayerDamage) this.onPlayerDamage(dano);

        if (this.player.isDead) {
            this._endBattle('enemy');
        }
    }

    _endBattle(forceWinner = null) {
        if (this.state === 'finished') return;
        clearInterval(this.timerInterval);
        this.state = 'finished';

        if (forceWinner) {
            this.winner = forceWinner;
        } else {
            this.winner = this.enemy.isDead ? 'player' : (this.player.isDead ? 'enemy' : 'player');
        }

        const duracaoSegundos = Math.round((Date.now() - this.startTime) / 1000);
        const precisao = this.totalChars > 0
            ? (this.totalCharsCorretos / this.totalChars) * 100
            : 0;

        const wpm = duracaoSegundos > 0
            ? Math.round((this.totalChars / 5) / (duracaoSegundos / 60))
            : 0;

        // Bônus de vitória
        if (this.winner === 'player') {
            this.pontuacao += 500;
            this.pontuacao += Math.round(wpm * 10);
            this.pontuacao += Math.round(precisao * 5);
        }

        if (this.onFinish) {
            this.onFinish({
                winner:           this.winner,
                pontuacao:        this.pontuacao,
                wpm,
                precisao:         parseFloat(precisao.toFixed(2)),
                duracaoSegundos,
                palavras:         this.resultadoPalavras,
            });
        }
    }

    /** Calcula WPM em tempo real */
    get currentWPM() {
        if (!this.startTime) return 0;
        const seconds = (Date.now() - this.startTime) / 1000;
        if (seconds < 1) return 0;
        return Math.round((this.totalChars / 5) / (seconds / 60));
    }

    /** Retorna a fração de tempo restante (0-1) */
    get timePercent() {
        return this.timeRemaining / this.timePerWord;
    }
}
