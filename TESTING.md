# Instruções de Teste e Implantação no Hostinger

Este guia explica como testar as melhorias feitas no backend PHP para garantir que tudo funcione corretamente no seu servidor Hostinger.

## 1. Configuração do Banco de Dados

### Instalação Nova
Para testar o sistema, você precisará de um banco de dados MySQL configurado.

1.  Acesse o **Painel do Hostinger**.
2.  Vá em **Banco de Dados MySQL** e crie um novo banco (ex: `u123456789_escola`).
3.  Anote o **Nome do Banco**, **Usuário** e **Senha**.
4.  Abra o **phpMyAdmin**.
5.  Importe o arquivo `schema.sql` (localizado na raiz do projeto) para criar as tabelas necessárias.

### Atualização (Se já tiver dados)
Se você já possui o banco de dados instalado, execute o script de migração para adicionar as tabelas de Eventos e LGPD:
1.  Abra o **phpMyAdmin**.
2.  Selecione seu banco de dados.
3.  Vá na aba **SQL**.
4.  Cole o conteúdo do arquivo `api/migrations/v2_lgpd_events.sql` e execute.

## 2. Configuração do Backend (API)

Após fazer o upload dos arquivos para o servidor (pasta `public_html` ou subpasta `sge`):

1.  Abra o arquivo `api/config.php`.
2.  Edite as constantes de conexão com os dados do seu banco Hostinger:

    ```php
    // Exemplo
    define('DB_HOST', 'localhost'); // Geralmente localhost no Hostinger
    define('DB_USER', 'u123456789_usuario');
    define('DB_PASS', 'SuaSenhaSegura123!');
    define('DB_NAME', 'u123456789_escola');
    ```

## 3. Testando a API

### Teste de Conexão e Login
Você pode testar a API diretamente usando ferramentas como **Postman** ou o próprio navegador (para GET).

1.  **URL Base**: `https://seu-dominio.com/api/`
2.  **Login (POST)**:
    *   URL: `https://seu-dominio.com/api/?action=login`
    *   Body (JSON):
        ```json
        {
            "email": "admin@admin",
            "password": "admin"
        }
        ```
    *   **Resultado Esperado**: Um JSON com `success: true` e dados do usuário.

### Teste de Eventos e Termos (Novo)
1.  **Criar Evento (POST)** (Requer Login Admin):
    *   URL: `.../api/?action=createEvent`
    *   Body:
        ```json
        {
            "title": "Apresentação de Final de Ano",
            "eventDate": "2023-12-20 19:00:00",
            "location": "Teatro Municipal",
            "termText": "Eu, responsável, autorizo a participação..."
        }
        ```
2.  **Listar Eventos (GET)**:
    *   URL: `.../api/?action=listEvents`
3.  **Inscrever e Aceitar Termo (POST)** (Requer Login Aluno):
    *   URL: `.../api/?action=enrollEvent`
    *   Body: `{"eventId": 1, "acceptTerms": true}`

### Teste de LGPD (Auditoria)
1.  Realize ações como Login, Busca de Usuários ou Visualização de Perfil.
2.  No banco de dados (phpMyAdmin), verifique a tabela `audit_logs`. Ela deve conter registros dessas ações com o IP e User Agent.

## Resumo das Mudanças Técnicas

*   **Estrutura Modular**: O arquivo `api/index.php` agora gerencia todas as requisições de forma centralizada.
*   **LGPD**:
    *   Tabela `audit_logs` registra quem acessou o quê.
    *   Handlers de Login e Usuário agora registram acessos sensíveis.
*   **Novas Funcionalidades**:
    *   Módulo de **Eventos** (`api/handlers/event_handlers.php`) para gerenciar apresentações e turnês.
    *   Controle de **Termos de Responsabilidade** digitais para eventos.
*   **Conexão Segura**: A conexão com o banco de dados foi movida para `api/utils/db.php`.
*   **Segurança**: Arquivos `.htaccess` protegem o código fonte.
