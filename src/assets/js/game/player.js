/**
 * TypeQuest — player.js
 * Classe que representa o herói do jogador
 */
class Player {
    constructor(nome, nivel) {
        this.nome   = nome;
        this.nivel  = nivel;
        this.hpMax  = 100;
        this.hp     = 100;
        this.sprite = '🧙';
        this.dano   = 10 + nivel * 5; // dano por palavra acertada

        // Animação de shake ao tomar dano
        this.shakeOffset = 0;
        this.shakeTimer  = 0;
    }

    /** Aplica dano recebido e ativa animação */
    takeDamage(amount) {
        this.hp = Math.max(0, this.hp - amount);
        this.shakeTimer = 12;
    }

    /** Retorna percentual de HP (0-1) */
    get hpPercent() {
        return this.hp / this.hpMax;
    }

    /** Determina a cor da barra de HP conforme o percentual */
    get hpColor() {
        if (this.hpPercent > 0.5) return { from: '#10b981', to: '#34d399' };
        if (this.hpPercent > 0.25) return { from: '#d97706', to: '#f59e0b' };
        return { from: '#ef4444', to: '#f87171' };
    }

    /** Atualiza animações internas (chamado a cada frame) */
    update() {
        if (this.shakeTimer > 0) {
            this.shakeOffset = (this.shakeTimer % 4 < 2) ? -4 : 4;
            this.shakeTimer--;
        } else {
            this.shakeOffset = 0;
        }
    }

    /** Verifica se está morto */
    get isDead() {
        return this.hp <= 0;
    }
}
