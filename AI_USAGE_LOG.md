# Relatório de Uso de Inteligência Artificial Generativa

Este documento registra todas as interações significativas com ferramentas de IA generativa (como Gemini, ChatGPT, Copilot, etc.) durante o desenvolvimento deste projeto. O objetivo é promover o uso ético e transparente da IA como ferramenta de apoio, e não como substituta para a compreensão dos conceitos fundamentais.

## Política de Uso
O uso de IA foi permitido para as seguintes finalidades:
- Geração de ideias e brainstorming de algoritmos.
- Explicação de conceitos complexos.
- Geração de código boilerplate (ex: estrutura de classes, leitura de arquivos).
- Sugestões de refatoração e otimização de código.
- Debugging e identificação de causas de erros.
- Geração de casos de teste.

É proibido submeter código gerado por IA sem compreendê-lo completamente e sem adaptá-lo ao projeto. Todo trecho de código influenciado pela IA deve ser referenciado neste log.

---

## Registro de Interações

*Copie e preencha o template abaixo para cada interação relevante.*

### Interação 1

- **Data:** 16/06/2026
- **Etapa do Projeto:** Configuração das conexões com o banco de dados
- **Ferramenta de IA Utilizada:** Claude
- **Objetivo da Consulta:** Eu estava com dificuldade para entender como manter as credenciais apenas no arquivo .env sem precisar deixar isso em hardcode no arquivo config.php. O maior problema/dificuldade é o fato de eu querer que o programa funcione tanto com Docker quanto usando XAMPP no Windows, por isso acabei solicitando ajuda para a IA.

- **Prompt(s) Utilizado(s):**
  Preciso de ajustes nas configurações de conexão com o banco de dados! A porta do banco na minha máquina é 3307. Preciso entender e garantir que eu só precise colocar as credenciais no arquivo .env. Atualmente o arquivo config.php está com as credenciais em hardcode e preciso ajustar isso para que seja possível capturar os dados do .env quando usado Docker ou XAMPP. Eu já ajustei no docker-compose.yml para utilizar o MYSQL_ROOT_PASSWORD do env.

- **Resumo da Resposta da IA:**
  A IA explicou o funcionamento do .env para os dois métodos (Docker e XAMPP), além disso explicou o fluxo unico de credenciais atual e o que cada arquivo representa (.env, docker-compose.yml, config.php)

- **Análise e Aplicação:**
  A resposta da IA foi muito boa e optei por implementar a ideia dela no arquivo config.php.

- **Referência no Código:**
  Arquivo src/config/config.php, da linha 7 até a linha 24.

---

### Interação 2

- **Data:** 16/06/2026
- **Etapa do Projeto:** Containerização da aplicação com Docker
- **Ferramenta de IA Utilizada:** Claude
- **Objetivo da Consulta:** Eu precisava configurar um ambiente Docker para rodar minha aplicação PHP com Apache e um banco MySQL. Minha principal dificuldade era entender como estruturar corretamente o Dockerfile e o docker-compose.yml e garantir que o projeto rodasse corretamente com o DocumentRoot apontando para a pasta /src. Também tive dúvidas sobre como organizar variáveis de ambiente e volumes para facilitar o desenvolvimento.

- **Prompt(s) Utilizado(s):**
  Preciso montar um Dockerfile e um docker-compose para uma aplicação PHP com Apache e MySQL.
  Quero que o Apache use a pasta /src como raiz do projeto.
  Também preciso configurar dois serviços (app e bd), usar variáveis de ambiente para o banco e mapear a porta do MySQL para 3307 na minha máquina.
  Quero usar volume para não precisar rebuildar tudo a cada alteração.

- **Resumo da Resposta da IA:**
  A IA me mostrou um exemplo de Dockerfile usando php:8.2-apache, instalando o PDO MySQL e ativando o mod_rewrite. Também explicou como mudar o DocumentRoot do Apache.
  No docker-compose, ela sugeriu separar em dois serviços (app e db), usar variáveis de ambiente e volumes, além de configurar o banco para iniciar já com scripts SQL.

- **Análise e Aplicação:**
  Eu usei a estrutura que a IA sugeriu como base, mas fui ajustando algumas coisas conforme testava, como portas, nomes de variáveis e caminhos.

- **Referência no Código:**
  Arquivos Dockerfile e docker-compose.yml (configuração dos containers e integração com o banco).

---

### Interação 3

- **Data:** 20/06/2026
- **Etapa do Projeto:** Correção na exibição das ligas
- **Ferramenta de IA Utilizada:** Claude
- **Objetivo da Consulta:** Toda vez que eu acessava a tela de Liguas, ele sempre caia na exceção e trazia a mensagem "empty.textContent = 'Erro ao carregar ligas.';"". Eu tentei localizar o erro melhorando a captura de logs, porém mesmo assim ele não retornava nada, então pedi ajuda para o Claude.

- **Prompt(s) Utilizado(s):**
  Preciso de ajuda para corrigir a exibição na tela de Ligas.

  Independente de eu estar em uma liga ou não, ele sempre retorna o erro "Erro ao carregar ligas", conforme anexo. Eu já melhorei o tratamento de erros na API de ligas, usando o catch \Throuwable em vez de apenas PDOExcepcion para tentar exibir a mensagem real do erro, mas mesmo assim continua com o mesmo problema. O que pode ser?

  Além disso, eu capturei alguns logs no docker rodando o comando docker compose logs app --tail=30 e trouxe todos os resultados abaixo, aparentemente não está nem fazendo o fetch(), mas me corrija se eu estiver errado.

- **Resumo da Resposta da IA:**
  A IA confirmou a minha suspeita e disse o seguinte: Olhando os logs, a request para /api/leagues/list.php nunca chegou ao servidor. Isso significa que o fetch() nunca foi chamado — o JavaScript lançou uma exceção antes disso.

  A causa: o loadLeagues() está no HTML antes de main.js ser carregado (que é incluído pelo footer.php). Quando loadLeagues() chama apiFetch(...), essa função ainda não existe, gerando ReferenceError: apiFetch is not defined — capturado pelo catch, que exibe "Erro ao carregar ligas."

  O fix é carregar main.js no <head> para que apiFetch exista antes de qualquer script inline da página:

- **Análise e Aplicação:**
  Fiz as alterações sugeridas pela IA, adicionei o main.js no head para ficar disponível nos scripts inline e removi a chamada ao main.js que estava no footer.php, pois se não chamaria duas vezes.

- **Referência no Código:**
  Arquivos header.php e footer.php.

---