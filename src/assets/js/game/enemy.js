/**
 * TypeQuest — enemy.js
 * Classe que representa um inimigo da batalha
 */
class Enemy {
    constructor(data) {
        this.id      = data.idInimigo;
        this.nome    = data.nome;
        this.sprite  = data.sprite;
        this.hpMax   = data.hp;
        this.hp      = data.hp;
        this.dano    = data.dano_base;
        this.tipo    = data.tipo;

        // Animação
        this.shakeOffset = 0;
        this.shakeTimer  = 0;
        this.floatY      = 0; // flutuação suave vertical
        this.floatDir    = 1;
    }

    /** Aplica dano e ativa shake */
    takeDamage(amount) {
        this.hp = Math.max(0, this.hp - amount);
        this.shakeTimer = 14;
    }

    get hpPercent() {
        return this.hp / this.hpMax;
    }

    get isDead() {
        return this.hp <= 0;
    }

    update() {
        if (this.shakeTimer > 0) {
            this.shakeOffset = (this.shakeTimer % 4 < 2) ? -6 : 6;
            this.shakeTimer--;
        } else {
            this.shakeOffset = 0;
        }

        // Flutuação suave
        this.floatY += 0.04 * this.floatDir;
        if (Math.abs(this.floatY) > 4) this.floatDir *= -1;
    }
}
