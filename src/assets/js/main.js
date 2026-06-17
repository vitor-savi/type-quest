/**
 * TypeQuest — main.js
 * Utilitários globais, helpers de fetch e feedback visual
 */

/**
 * Faz uma requisição fetch com JSON e retorna o objeto resposta.
 * @param {string} url
 * @param {object} body - Dados a enviar (POST)
 * @param {string} method - Método HTTP (padrão: POST)
 */
async function apiFetch(url, body = null, method = 'POST') {
    const options = {
        method,
        headers: { 'Content-Type': 'application/json' },
    };

    if (body !== null) {
        options.body = JSON.stringify(body);
    }

    const response = await fetch(url, options);
    const data = await response.json();
    return data;
}

/**
 * Mostra um estado de carregamento no botão enquanto a ação é executada.
 * @param {HTMLButtonElement} btn
 * @param {boolean} loading
 */
function setButtonLoading(btn, loading) {
    if (loading) {
        btn.dataset.originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="loading-spinner" style="width:18px;height:18px;border-width:2px;display:inline-block;vertical-align:middle;"></span>';
    } else {
        btn.disabled = false;
        btn.innerHTML = btn.dataset.originalText || btn.innerHTML;
    }
}

/**
 * Exibe uma mensagem de erro inline num elemento de alerta.
 * @param {string} elementId - ID do elemento de alerta
 * @param {string} message
 */
function showError(elementId, message) {
    const el = document.getElementById(elementId);
    if (!el) return;
    const msgEl = document.getElementById(elementId + 'Msg') || el.querySelector('span:last-child');
    if (msgEl) msgEl.textContent = message;
    el.classList.remove('d-none');
}

/**
 * Esconde um elemento de alerta.
 * @param {string} elementId
 */
function hideAlert(elementId) {
    const el = document.getElementById(elementId);
    if (el) el.classList.add('d-none');
}

/**
 * Mostra uma mensagem de alerta genérica (substituição ao alert()).
 * @param {string} message
 * @param {'success'|'error'|'info'} type
 */
function showToast(message, type = 'info') {
    const existing = document.getElementById('toast-quest');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.id = 'toast-quest';
    toast.style.cssText = `
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        background: var(--bg-card);
        border: 1px solid var(--border-card);
        border-radius: var(--radius-sm);
        padding: 0.85rem 1.25rem;
        color: var(--text-primary);
        font-family: var(--font-body);
        font-size: 0.875rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: var(--shadow);
        z-index: 9999;
        animation: slideInRight 0.3s ease;
        max-width: 320px;
    `;

    const icons = { success: 'bi-check-circle-fill', error: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill' };
    const colors = { success: 'var(--color-success)', error: 'var(--color-danger)', info: 'var(--color-primary)' };

    toast.innerHTML = `<i class="bi ${icons[type]}" style="color:${colors[type]};font-size:1rem;"></i><span>${message}</span>`;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// Animações do toast
const styleToast = document.createElement('style');
styleToast.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(120%); opacity: 0; }
        to   { transform: translateX(0);   opacity: 1; }
    }
    @keyframes slideOutRight {
        from { transform: translateX(0);   opacity: 1; }
        to   { transform: translateX(120%); opacity: 0; }
    }
`;
document.head.appendChild(styleToast);

/**
 * Formata um número com separador de milhar.
 * @param {number} n
 * @returns {string}
 */
function formatNumber(n) {
    return new Intl.NumberFormat('pt-BR').format(n);
}

/**
 * Formata uma data ISO para formato brasileiro.
 * @param {string} dateStr
 * @returns {string}
 */
function formatDate(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
