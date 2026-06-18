/**
 * TypeQuest — ui.js
 * HUD, animações de dano flutuante e feedback visual no Canvas
 */
class GameUI {
    constructor(ctx, canvas) {
        this.ctx    = ctx;
        this.canvas = canvas;

        // Flash de tela (dano recebido = vermelho, acerto = dourado)
        this.flashAlpha = 0;
        this.flashColor = 'rgba(239,68,68,';

        // Números de dano flutuantes
        this.floatingNumbers = [];

        // Partículas de estrelas no fundo
        this.stars = this._generateStars(80);
    }

    _generateStars(count) {
        const stars = [];
        for (let i = 0; i < count; i++) {
            stars.push({
                x:    Math.random() * this.canvas.width,
                y:    Math.random() * this.canvas.height,
                r:    Math.random() * 1.5 + 0.3,
                a:    Math.random(),
                da:   (Math.random() * 0.01 + 0.003) * (Math.random() < 0.5 ? 1 : -1),
            });
        }
        return stars;
    }

    /** Dispara flash de dano na tela */
    flashDamage(color = 'rgba(239,68,68,') {
        this.flashAlpha = 0.45;
        this.flashColor = color;
    }

    /** Adiciona um número flutuante (dano ou cura) */
    addFloatingNumber(x, y, text, color = '#ef4444') {
        this.floatingNumbers.push({ x, y, text, color, alpha: 1, vy: -1.8, life: 60 });
    }

    /** Atualiza os números flutuantes */
    update() {
        if (this.flashAlpha > 0) this.flashAlpha -= 0.03;

        this.floatingNumbers = this.floatingNumbers.filter(n => n.life > 0);
        for (const n of this.floatingNumbers) {
            n.y    += n.vy;
            n.vy   *= 0.96;
            n.alpha = n.life / 60;
            n.life--;
        }

        for (const s of this.stars) {
            s.a += s.da;
            if (s.a > 1 || s.a < 0) s.da *= -1;
        }
    }

    /** Desenha o fundo estrelado com gradiente */
    drawBackground() {
        const { ctx, canvas } = this;
        const grad = ctx.createLinearGradient(0, 0, 0, canvas.height);
        grad.addColorStop(0, '#0f0e17');
        grad.addColorStop(1, '#1a1a2e');
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        for (const s of this.stars) {
            ctx.beginPath();
            ctx.arc(s.x, s.y, s.r, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(226,224,255,${s.a * 0.6})`;
            ctx.fill();
        }
    }

    /** Desenha a barra de HP de um combatente */
    drawHPBar(x, y, width, height, percent, colors) {
        const { ctx } = this;

        ctx.fillStyle = 'rgba(0,0,0,0.5)';
        ctx.roundRect(x, y, width, height, height / 2);
        ctx.fill();

        const fillWidth = Math.max(0, width * percent);
        if (fillWidth > 0) {
            const grad = ctx.createLinearGradient(x, 0, x + width, 0);
            grad.addColorStop(0, colors.from);
            grad.addColorStop(1, colors.to);
            ctx.fillStyle = grad;
            ctx.beginPath();
            ctx.roundRect(x, y, fillWidth, height, height / 2);
            ctx.fill();
        }
    }

    /** Desenha o sprite (emoji) de um combatente no canvas */
    drawSprite(emoji, x, y, size = 64) {
        const { ctx } = this;
        ctx.font = `${size}px serif`;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(emoji, x, y);
    }

    /** Texto com fonte Cinzel */
    drawTitle(text, x, y, size, color, align = 'center') {
        const { ctx } = this;
        ctx.font = `bold ${size}px 'Cinzel', serif`;
        ctx.fillStyle = color;
        ctx.textAlign = align;
        ctx.textBaseline = 'middle';
        ctx.fillText(text, x, y);
    }

    /** Texto com fonte Inter */
    drawText(text, x, y, size, color, align = 'center') {
        const { ctx } = this;
        ctx.font = `${size}px 'Inter', sans-serif`;
        ctx.fillStyle = color;
        ctx.textAlign = align;
        ctx.textBaseline = 'middle';
        ctx.fillText(text, x, y);
    }

    /** Desenha o flash de dano/acerto na tela inteira */
    drawFlash() {
        if (this.flashAlpha <= 0) return;
        this.ctx.fillStyle = `${this.flashColor}${this.flashAlpha.toFixed(2)})`;
        this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
    }

    /** Desenha os números flutuantes de dano */
    drawFloatingNumbers() {
        const { ctx } = this;
        for (const n of this.floatingNumbers) {
            ctx.globalAlpha = n.alpha;
            ctx.font = 'bold 22px Inter, sans-serif';
            ctx.fillStyle = n.color;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';

            // Sombra para legibilidade
            ctx.shadowColor = 'rgba(0,0,0,0.8)';
            ctx.shadowBlur  = 4;
            ctx.fillText(n.text, n.x, n.y);
            ctx.shadowBlur  = 0;
        }
        ctx.globalAlpha = 1;
    }

    /** Linha divisória central (decorativa) */
    drawDivider() {
        const { ctx, canvas } = this;
        const cx = canvas.width / 2;
        ctx.strokeStyle = 'rgba(124,58,237,0.3)';
        ctx.lineWidth   = 1;
        ctx.setLineDash([6, 4]);
        ctx.beginPath();
        ctx.moveTo(cx, 20);
        ctx.lineTo(cx, canvas.height - 20);
        ctx.stroke();
        ctx.setLineDash([]);
    }
}
