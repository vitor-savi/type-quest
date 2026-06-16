/**
 * TypeQuest — auth.js
 * Validação e submit dos formulários de login e registro
 */

/* ========== LOGIN ========== */
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const loginInput = document.getElementById('login');
        const senhaInput = document.getElementById('senha');
        let valid = true;

        if (!loginInput.value.trim()) {
            document.getElementById('loginFieldError').classList.add('visible');
            loginInput.style.borderColor = 'var(--color-danger)';
            valid = false;
        } else {
            document.getElementById('loginFieldError').classList.remove('visible');
            loginInput.style.borderColor = '';
        }

        if (!senhaInput.value.trim()) {
            document.getElementById('senhaFieldError').classList.add('visible');
            senhaInput.style.borderColor = 'var(--color-danger)';
            valid = false;
        } else {
            document.getElementById('senhaFieldError').classList.remove('visible');
            senhaInput.style.borderColor = '';
        }

        if (!valid) return;

        const btn = document.getElementById('loginBtn');
        setButtonLoading(btn, true);
        hideAlert('loginError');

        try {
            const data = await apiFetch('/api/auth/login.php', {
                login: loginInput.value.trim(),
                senha: senhaInput.value,
            });

            if (data.success) {
                // Redireciona para dashboard após login
                window.location.href = '/pages/dashboard.php';
            } else {
                showError('loginError', data.message || 'Credenciais inválidas.');
            }
        } catch (err) {
            showError('loginError', 'Erro de conexão. Tente novamente.');
        } finally {
            setButtonLoading(btn, false);
        }
    });
}

/* ========== REGISTRO ========== */
const registerForm = document.getElementById('registerForm');
if (registerForm) {
    // Medidor de força de senha
    const senhaInput = document.getElementById('senha');
    if (senhaInput) {
        senhaInput.addEventListener('input', () => {
            updatePasswordStrength(senhaInput.value);
        });
    }

    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const nome    = document.getElementById('nome_usuario').value.trim();
        const email   = document.getElementById('email').value.trim();
        const senha   = document.getElementById('senha').value;
        const confirm = document.getElementById('confirmar_senha').value;

        let valid = true;

        // Validação do nome
        if (nome.length < 3) {
            showFieldError('nomeError', 'Mínimo 3 caracteres.');
            valid = false;
        } else {
            clearFieldError('nomeError');
        }

        // Validação do e-mail
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showFieldError('emailError', 'E-mail inválido.');
            valid = false;
        } else {
            clearFieldError('emailError');
        }

        // Validação da senha
        if (senha.length < 6) {
            showFieldError('senhaError', 'Mínimo 6 caracteres.');
            valid = false;
        } else {
            clearFieldError('senhaError');
        }

        // Confirmação de senha
        if (senha !== confirm) {
            showFieldError('confirmarError', 'As senhas não coincidem.');
            valid = false;
        } else {
            clearFieldError('confirmarError');
        }

        if (!valid) return;

        const btn = document.getElementById('registerBtn');
        setButtonLoading(btn, true);
        hideAlert('registerError');

        try {
            const data = await apiFetch('/api/auth/register.php', { nome_usuario: nome, email, senha });

            if (data.success) {
                window.location.href = '/pages/auth/login.php?registered=1';
            } else {
                showError('registerError', data.message || 'Erro ao criar conta.');
            }
        } catch (err) {
            showError('registerError', 'Erro de conexão. Tente novamente.');
        } finally {
            setButtonLoading(btn, false);
        }
    });
}

/* ========== Utilitários de formulário ========== */
function showFieldError(id, msg) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = msg;
    el.classList.add('visible');
}

function clearFieldError(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = '';
    el.classList.remove('visible');
}

function updatePasswordStrength(senha) {
    let score = 0;
    if (senha.length >= 6)  score++;
    if (senha.length >= 10) score++;
    if (/[A-Z]/.test(senha) && /[0-9]/.test(senha)) score++;

    const segments = ['str1', 'str2', 'str3'];
    const labels   = ['', 'Fraca', 'Média', 'Forte'];
    const classes  = ['', 'filled-weak', 'filled-medium', 'filled-strong'];

    segments.forEach((id, idx) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.className = 'strength-bar__segment';
        if (idx < score) el.classList.add(classes[score]);
    });

    const lbl = document.getElementById('strengthLabel');
    if (lbl) lbl.textContent = labels[score] || '';
}
