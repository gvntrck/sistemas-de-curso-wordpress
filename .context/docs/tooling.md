# Ferramentas de Desenvolvimento

## Ambiente de Desenvolvimento

### Requisitos

- **PHP:** 7.4 ou superior
- **WordPress:** 5.8 ou superior
- **MySQL:** 5.7+ ou MariaDB 10.3+
- **Servidor Web:** Apache ou Nginx

### Ambientes Locais Recomendados

#### 1. Local by Flywheel
```
Vantagens:
- Interface visual amigável
- One-click WordPress install
- SSL local automático
- Gerenciamento fácil de sites

Download: https://localwp.com/
```

#### 2. XAMPP
```
Vantagens:
- Gratuito e open-source
- Controle total do ambiente
- phpMyAdmin incluído

Download: https://www.apachefriends.org/
```

#### 3. Laragon
```
Vantagens:
- Leve e rápido
- Suporte a múltiplas versões PHP
- Fácil troca de versões

Download: https://laragon.org/
```

#### 4. Docker (Avançado)
```yaml
# docker-compose.yml
version: '3.8'
services:
  wordpress:
    image: wordpress:latest
    ports:
      - "8080:80"
    environment:
      WORDPRESS_DB_HOST: db
      WORDPRESS_DB_USER: wp_user
      WORDPRESS_DB_PASSWORD: wp_pass
      WORDPRESS_DB_NAME: wp_db
    volumes:
      - ./:/var/www/html/wp-content/plugins/sistema-cursos-plugin
  
  db:
    image: mysql:5.7
    environment:
      MYSQL_DATABASE: wp_db
      MYSQL_USER: wp_user
      MYSQL_PASSWORD: wp_pass
      MYSQL_ROOT_PASSWORD: root_pass
```

## Editor de Código

### Visual Studio Code (Recomendado)

#### Extensões Essenciais
```
1. PHP Intelephense (bmewburn.vscode-intelephense-client)
   - Autocomplete PHP
   - Análise de código

2. WordPress Snippets (wordpresstoolbox.wordpress-toolbox)
   - Snippets para WordPress

3. ESLint (dbaeumer.vscode-eslint)
   - Linting JavaScript

4. Prettier (esbenp.prettier-vscode)
   - Formatação de código

5. GitLens (eamodio.gitlens)
   - Integração Git avançada

6. PHP Debug (xdebug.php-debug)
   - Debugging com Xdebug
```

#### Configuração (settings.json)
```json
{
    "php.suggest.basic": false,
    "intelephense.files.associations": ["*.php", "*.module"],
    "editor.formatOnSave": true,
    "files.associations": {
        "*.php": "php"
    },
    "[php]": {
        "editor.defaultFormatter": "bmewburn.vscode-intelephense-client"
    }
}
```

## Ferramentas de Debug

### WordPress Debug Mode

#### wp-config.php
```php
// Ativar debug mode
define('WP_DEBUG', true);

// Salvar logs em wp-content/debug.log
define('WP_DEBUG_LOG', true);

// Não exibir erros no frontend (segurança)
define('WP_DEBUG_DISPLAY', false);
@ini_set('display_errors', 0);

// Debug de scripts/styles
define('SCRIPT_DEBUG', true);

// Salvar queries para análise
define('SAVEQUERIES', true);
```

### Query Monitor (Plugin)
```
Nome: Query Monitor
Função: Analisa queries, hooks, e performance
Instalação: WordPress.org → Plugins → "Query Monitor"

Recursos:
- Lista todas as queries SQL
- Identifica queries lentas
- Mostra hooks disparados
- Analisa uso de memória
```

### Debug Bar (Plugin)
```
Nome: Debug Bar
Função: Barra de debug no admin
Instalação: WordPress.org → Plugins → "Debug Bar"

Add-ons úteis:
- Debug Bar Console
- Debug Bar Cron
- Debug Bar Actions & Filters
```

### Xdebug (Avançado)

#### Instalação (XAMPP Windows)
```ini
; php.ini
[xdebug]
zend_extension=xdebug
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
```

#### VS Code launch.json
```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/var/www/html": "${workspaceFolder}"
            }
        }
    ]
}
```

## Controle de Versão

### Git

#### Instalação
```bash
# Windows
# Baixar de: https://git-scm.com/download/win

# Verificar instalação
git --version
```

#### Configuração Inicial
```bash
git config --global user.name "Seu Nome"
git config --global user.email "seu@email.com"

# Editor padrão
git config --global core.editor "code --wait"
```

#### .gitignore (Já configurado)
```gitignore
# Credenciais
sync_ftp.py
.env

# WordPress
wp-config-local.php
*.log

# IDE
.vscode/
.idea/

# OS
.DS_Store
Thumbs.db
```

### GitHub Desktop (Opcional)
```
Interface visual para Git
Download: https://desktop.github.com/
```

## Banco de Dados

### phpMyAdmin
```
Incluído em: XAMPP, MAMP
Acesso: http://localhost/phpmyadmin

Funcionalidades:
- Visualizar tabelas
- Executar queries
- Importar/Exportar
- Editar dados
```

### Adminer (Alternativa Leve)
```
Download: https://www.adminer.org/
Upload: Colocar adminer.php na pasta do projeto
Acesso: http://localhost/adminer.php
```

### TablePlus (Premium)
```
Interface moderna para MySQL
Download: https://tableplus.com/
Preço: Gratuito (limitado) ou $59 (licença)
```

## Deploy e Sincronização

### FTP/SFTP - FileZilla
```
Download: https://filezilla-project.org/

Configuração:
Host: seu-servidor.com
Port: 21 (FTP) ou 22 (SFTP)
User: seu_usuario
Password: sua_senha
```

### Script Python (sync_ftp.py)
```python
# Já configurado no projeto
# Uso:
python sync_ftp.py

# Sincroniza arquivos locais com servidor FTP
# IMPORTANTE: Arquivo está em .gitignore (credenciais sensíveis)
```

### WP-CLI (Linha de Comando WordPress)

#### Instalação Windows (PowerShell)
```powershell
# Baixar wp-cli.phar
Invoke-WebRequest -Uri https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -OutFile wp-cli.phar

# Criar batch file
@echo off
php wp-cli.phar %*
# Salvar como wp.bat na pasta do WordPress
```

#### Comandos Úteis
```bash
# Atualizar WordPress
wp core update

# Ativar plugin
wp plugin activate sistema-cursos-plugin

# Buscar/substituir no banco
wp search-replace 'localhost' 'producao.com'

# Exportar banco
wp db export backup.sql

# Flush rewrite rules
wp rewrite flush
```

## Ferramentas de Teste

### Browser DevTools
```
Chrome DevTools:
- Console (erros JavaScript)
- Network (requisições AJAX)
- Elements (inspecionar HTML/CSS)
- Application (localStorage, cookies)

Atalho: F12 ou Ctrl+Shift+I
```

### Postman (Testar APIs/AJAX)
```
Download: https://www.postman.com/

Uso para testar AJAX:
POST http://localhost/wp-admin/admin-ajax.php
Body (form-data):
  action: marcar_aula_completa
  aula_id: 123
  nonce: xyz...
```

## Produtividade

### Snippets

#### VS Code - PHP Snippets
```json
// File: .vscode/snippets.code-snippets
{
    "WordPress AJAX Handler": {
        "prefix": "wpajax",
        "body": [
            "add_action('wp_ajax_${1:action_name}', '${2:function_name}');",
            "add_action('wp_ajax_nopriv_${1:action_name}', '${2:function_name}');",
            "",
            "function ${2:function_name}() {",
            "\tif (!wp_verify_nonce($_POST['nonce'], '${3:nonce_action}')) {",
            "\t\twp_send_json_error('Nonce inválido');",
            "\t}",
            "\t",
            "\t$0",
            "\t",
            "\twp_send_json_success([]);",
            "}"
        ]
    }
}
```

### Atalhos VS Code Úteis
```
Ctrl+P          - Quick Open (arquivo)
Ctrl+Shift+P    - Command Palette
Ctrl+D          - Selecionar próxima ocorrência
Ctrl+/          - Comentar linha
Alt+Up/Down     - Mover linha
F12             - Ir para definição
Ctrl+Space      - Trigger autocomplete
```

## Documentação e Referências

### Bookmarks Essenciais
```
WordPress Developer Reference
https://developer.wordpress.org/reference/

WordPress Coding Standards
https://developer.wordpress.org/coding-standards/

PHP Documentation
https://www.php.net/manual/pt_BR/

WooCommerce Docs
https://woocommerce.com/documentation/

ACF Documentation
https://www.advancedcustomfields.com/resources/
```

## Checklist de Setup Inicial

Para novo desenvolvedor entrar no projeto:

- [ ] Instalar ambiente local (Local, XAMPP, etc)
- [ ] Instalar WordPress
- [ ] Clonar repositório em `wp-content/plugins/`
- [ ] Ativar plugin no WordPress
- [ ] Instalar VS Code + extensões
- [ ] Configurar wp-config.php para debug
- [ ] Instalar Query Monitor plugin
- [ ] Criar dados de teste (cursos, aulas, usuários)
- [ ] Ler `docs/project-overview.md`
- [ ] Ler `docs/development-workflow.md`

## Performance e Otimização

### P3 (Plugin Performance Profiler)
```
Analisa performance de plugins
Instalação: WordPress.org → "P3"
```

### GTmetrix
```
Análise de performance do site
URL: https://gtmetrix.com/
```

## Recursos Relacionados

- **Desenvolvimento:** `development-workflow.md`
- **Arquitetura:** `architecture.md`
