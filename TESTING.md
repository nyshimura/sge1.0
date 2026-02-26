# Instruções de Teste e Implantação no Hostinger

Este guia explica como testar as melhorias feitas no backend PHP para garantir que tudo funcione corretamente no seu servidor Hostinger.

## 1. Configuração do Banco de Dados

Para testar o sistema, você precisará de um banco de dados MySQL configurado.

1.  Acesse o **Painel do Hostinger**.
2.  Vá em **Banco de Dados MySQL** e crie um novo banco (ex: `u123456789_escola`).
3.  Anote o **Nome do Banco**, **Usuário** e **Senha**.
4.  Abra o **phpMyAdmin**.
5.  Importe o arquivo `schema.sql` (localizado na raiz do projeto) para criar as tabelas necessárias.

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

### Teste de Segurança (Arquivos Protegidos)
Tente acessar arquivos sensíveis diretamente pelo navegador:
*   `https://seu-dominio.com/api/config.php` -> Deve retornar **403 Forbidden** (graças ao `.htaccess`).
*   `https://seu-dominio.com/api/handlers/auth_handlers.php` -> Deve retornar **403 Forbidden**.

## 4. Testando o Frontend

1.  Acesse a URL principal do sistema: `https://seu-dominio.com/` (ou `https://seu-dominio.com/sge/`).
2.  Faça login com o usuário admin.
3.  Navegue pelo Dashboard e verifique se os dados (alunos, cursos) estão carregando.
4.  Acesse a aba **Perfil** e tente atualizar seus dados.

## Resumo das Mudanças Técnicas

*   **Estrutura Modular**: O arquivo `api/index.php` agora gerencia todas as requisições de forma centralizada, melhorando a segurança e organização.
*   **Conexão Segura**: A conexão com o banco de dados foi movida para `api/utils/db.php` e usa PDO para prevenir injeção de SQL.
*   **Proteção de Arquivos**: Arquivos `.htaccess` foram adicionados para impedir acesso direto a scripts PHP sensíveis e arquivos de configuração.
*   **Respostas Padronizadas**: Todas as respostas da API agora seguem o formato JSON padrão `{ "success": true/false, "data": { ... } }`.
