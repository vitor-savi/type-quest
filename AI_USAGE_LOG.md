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