# ⚔️ TypeQuest — RPG de Digitação

> Jogo de digitação no estilo RPG por turnos desenvolvido como trabalho prático
> da disciplina DS122 — Desenvolvimento Web — UFPR (TADS) 2026/1.

---

## 📋 Sobre o Projeto

TypeQuest é um jogo web onde o jogador enfrenta inimigos de fantasia digitando palavras corretamente. Cada palavra digitada certa causa dano ao inimigo; erros ou timeouts fazem o inimigo contra-atacar. O objetivo é derrotar o inimigo antes que seu HP chegue a zero.

O projeto inclui sistema de ligas, ranking global/semanal, histórico de partidas, sistema de níveis e uma arena de batalha completamente animada com Canvas HTML5.

---

## 🎮 Como Jogar

1. Crie uma conta e faça login
2. No dashboard, clique em **"Iniciar Batalha"**
3. Leia o inimigo que vai enfrentar e clique em **"Começar Batalha!"**
4. Digite as palavras que aparecem no centro da tela **o mais rápido possível**
5. Cada palavra certa → você ataca o inimigo
6. Cada erro ou timeout → o inimigo ataca você
7. Derrote o inimigo antes que seu HP chegue a zero!
8. Veja sua pontuação, WPM e precisão no resultado final

**Mecânica de nível:** a cada 5 partidas jogadas você sobe de nível (máx. 10), enfrentando inimigos mais difíceis e palavras mais longas.

---

## 🛠️ Tecnologias Utilizadas

| Camada | Tecnologia |
|--------|------------|
| Back-end | PHP 8.2 (vanilla, sem frameworks) |
| Banco de dados | MySQL 8.0 com PDO |
| Front-end | HTML5 semântico, CSS3 puro, Bootstrap 5 |
| JavaScript | ES6+ puro (sem jQuery) |
| Animações | Canvas HTML5 + requestAnimationFrame |
| Tipografia | Google Fonts (Cinzel + Inter) |
| Ícones | Bootstrap Icons |
| Ambiente | Docker (PHP 8.2-apache + MySQL 8.0) |

---

## 🚀 Como Executar

### Com Docker (recomendado)

**Pré-requisitos:** Docker Desktop instalado e em execução.

```bash
# 1. Clone o repositório
git clone https://github.com/vitor-savi/type-quest.git
cd type-quest

# 2. Rode o comando abaixo e configure o arquivo .env
copy .env.example .env

# 3. Suba os containers (na primeira vez baixa as imagens e inicializa o banco)
docker-compose up --build

# 4. Acesse no navegador
# http://localhost:8080
```

O banco de dados é inicializado automaticamente com o schema e os seeds.
Para parar: `docker-compose down`

### Com XAMPP (Windows)

**Pré-requisitos:** XAMPP com PHP 8.2+ e MySQL instalado.

1. Copie a pasta `src/` para dentro de `C:\xampp\htdocs\type-quest\`
2. Inicie o Apache e o MySQL no XAMPP Control Panel
3. Abra o MySQL e execute os scripts:
   ```sql
   source C:/xampp/htdocs/type-quest/database/schema.sql
   source C:/xampp/htdocs/type-quest/database/seeds.sql
   ```
4. Acesse: `http://localhost/type-quest/`

> O arquivo `src/config/config.php` detecta automaticamente o ambiente (Docker vs XAMPP) e ajusta as credenciais.

---

## 📁 Estrutura do Projeto

```
type-quest/
├── docker-compose.yml          # Orquestra os dois containers
├── Dockerfile                  # Imagem PHP 8.2-apache customizada
├── .env.example                # Variáveis de ambiente (copie para .env)
├── .gitignore
├── README.md
│
└── src/
    ├── index.php               # Roteador: redireciona para login ou dashboard
    ├── config/
    │   ├── config.php          # Constantes globais, detecção de ambiente
    │   └── database.php        # Conexão PDO singleton
    ├── api/
    │   ├── auth/               # login, register, logout
    │   ├── game/               # get_words, save_match
    │   ├── ranking/            # global, league
    │   ├── leagues/            # list, create, join
    │   └── history/            # list (paginado)
    ├── pages/
    │   ├── auth/               # login.php, register.php
    │   ├── dashboard.php       # Tela inicial pós-login
    │   ├── game.php            # Arena de batalha
    │   ├── ranking.php         # Ranking global e por ligas
    │   ├── leagues.php         # Gerenciar ligas
    │   └── history.php         # Histórico de partidas
    ├── includes/               # header, topbar, sidebar, footer (PHP partials)
    ├── assets/
    │   ├── css/                # main.css, auth.css, game.css, dashboard.css
    │   └── js/
    │       ├── main.js         # Utilitários globais
    │       ├── auth.js         # Validação dos forms de auth
    │       └── game/           # engine.js, battle.js, player.js, enemy.js, ui.js
    └── database/
        ├── schema.sql          # CREATE TABLEs
        └── seeds.sql           # Palavras e inimigos iniciais
```

---

## 🗃️ Banco de Dados

| Tabela | Descrição |
|--------|-----------|
| `USUARIO` | Contas dos jogadores |
| `PALAVRA` | Banco de palavras por dificuldade (1-5) |
| `INIMIGO` | Inimigos com HP, dano e sprite |
| `PARTIDA` | Resultado de cada batalha |
| `PARTIDA_PALAVRA` | Quais palavras apareceram em cada partida |
| `LIGA` | Grupos competitivos criados pelos usuários |
| `USUARIO_LIGA` | Membros e pontuações por liga |
| `PONTUACAO_SEMANAL` | Rankings semanais (global e por liga) |

---

## 📄 Licença

MIT
