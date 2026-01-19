---
status: filled
generated: 2026-01-18
---

# Ferramentas e Produtividade - LMS SuporteRapido

Este documento lista as ferramentas, configurações e dicas de produtividade para desenvolvimento do plugin.

## Ferramentas Necessárias

### Ambiente Local WordPress

| Ferramenta | Descrição | Instalação |
|------------|-----------|------------|
| **Local WP** | Ambiente WordPress local (recomendado) | [localwp.com](https://localwp.com) |
| **XAMPP** | Alternativa com Apache/MySQL/PHP | [apachefriends.org](https://apachefriends.org) |
| **Docker** | Containerizado (avançado) | `docker-compose` com WordPress |

### PHP
- **PHP 7.4+** (incluído no ambiente local)
- Comando `php -l` para validação de sintaxe

### Editor de Código

| Editor | Extensões Recomendadas |
|--------|----------------------|
| **VS Code** | PHP Intelephense, WordPress Snippets, GitLens |
| **PHPStorm** | WordPress Support (built-in) |
| **Cursor/Windsurf** | Extensões PHP padrão |

### Navegador
- **Chrome DevTools** (F12) para debug JS/CSS
- **Firefox Developer Edition** para testes alternativos

## Ferramentas Recomendadas

### Plugins WordPress para Debug

| Plugin | Função | Quando Usar |
|--------|--------|-------------|
| **Query Monitor** | Debug de queries, hooks, erros | Desenvolvimento |
| **Debug Bar** | Painel de debug na admin bar | Desenvolvimento |
| **Log Deprecated Notices** | Identifica funções obsoletas | Antes de atualizar WP |

### Ferramentas de Banco de Dados

| Ferramenta | Uso |
|------------|-----|
| **phpMyAdmin** | Incluído no XAMPP/Local WP |
| **Adminer** | Alternativa leve ao phpMyAdmin |
| **TablePlus** | Cliente desktop para Mac/Windows |
| **HeidiSQL** | Cliente desktop para Windows |

### Git
```bash
# Comandos úteis
git status                    # Ver alterações
git diff includes/            # Diff de pasta específica
git log --oneline -10         # Últimos 10 commits
git checkout -- file.php      # Desfazer alterações em arquivo
```

## Configurações de Desenvolvimento

### wp-config.php
```php
// Debug habilitado
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Scripts não minificados
define('SCRIPT_DEBUG', true);

// Salvar queries (cuidado com performance)
define('SAVEQUERIES', true);

// Desabilitar revisões (opcional)
define('WP_POST_REVISIONS', 3);
```

### VS Code - settings.json
```json
{
  "php.validate.executablePath": "C:\\xampp\\php\\php.exe",
  "editor.formatOnSave": false,
  "files.associations": {
    "*.php": "php"
  },
  "emmet.includeLanguages": {
    "php": "html"
  },
  "[php]": {
    "editor.defaultFormatter": null,
    "editor.tabSize": 4
  }
}
```

### VS Code - Extensões Recomendadas
```json
{
  "recommendations": [
    "bmewburn.vscode-intelephense-client",
    "wordpresstoolbox.wordpress-toolbox",
    "eamodio.gitlens",
    "formulahendry.auto-close-tag",
    "formulahendry.auto-rename-tag"
  ]
}
```

## Scripts e Comandos Úteis

### Validação de PHP
```bash
# Windows PowerShell - Validar todos os arquivos PHP
Get-ChildItem -Recurse -Filter "*.php" | ForEach-Object { php -l $_.FullName }

# Arquivo específico
php -l sistema-cursos-plugin.php
```

### Debug de Erros
```bash
# Ver últimas linhas do debug.log (Windows)
Get-Content "C:\path\to\wordpress\wp-content\debug.log" -Tail 50

# Linux/Mac
tail -f wp-content/debug.log
```

### Flush de Cache/Rewrite
```php
// Via código (uma vez)
flush_rewrite_rules();

// Via WP-CLI
wp rewrite flush
```

### WP-CLI (Opcional)
Se instalado, comandos úteis:
```bash
wp plugin list                    # Listar plugins
wp post list --post_type=curso    # Listar cursos
wp user list --role=aluno         # Listar alunos
wp transient delete --all         # Limpar transients
```

## Snippets Úteis

### Debug Rápido
```php
// Dump formatado
echo '<pre>' . print_r($variable, true) . '</pre>';

// Para log
error_log('DEBUG: ' . print_r($variable, true));

// Com stack trace
error_log('DEBUG at ' . __FILE__ . ':' . __LINE__);
```

### Query Debug
```php
global $wpdb;
// Antes da query
$wpdb->show_errors();

// Depois da query
echo '<pre>Query: ' . $wpdb->last_query . '</pre>';
echo '<pre>Error: ' . $wpdb->last_error . '</pre>';
```

### Meta Debug (CPT)
```php
// Ver todos os metas de um post
$metas = get_post_meta($post_id);
echo '<pre>' . print_r($metas, true) . '</pre>';

// Ver meta específica
$valor = get_post_meta($post_id, 'trilha', true);
```

## Fluxo de Trabalho

### Novo Feature
1. Criar branch (se usando Git): `git checkout -b feature/nome`
2. Implementar funcionalidade
3. Validar sintaxe: `php -l arquivo.php`
4. Testar localmente no WordPress
5. Verificar debug.log e console JS
6. Atualizar versão no header do plugin
7. Commit e merge

### Correção de Bug
1. Reproduzir o bug localmente
2. Identificar arquivo(s) afetado(s)
3. Adicionar logs de debug se necessário
4. Implementar correção
5. Testar cenário original + relacionados
6. Remover logs de debug
7. Atualizar versão e commit

## Dicas de Produtividade

### Atalhos VS Code
| Atalho | Ação |
|--------|------|
| `Ctrl+P` | Buscar arquivo por nome |
| `Ctrl+Shift+F` | Buscar em todos os arquivos |
| `Ctrl+G` | Ir para linha |
| `F12` | Ir para definição |
| `Alt+↑/↓` | Mover linha |
| `Ctrl+D` | Selecionar próxima ocorrência |

### Chrome DevTools
| Atalho | Ação |
|--------|------|
| `F12` | Abrir DevTools |
| `Ctrl+Shift+C` | Inspecionar elemento |
| `Ctrl+Shift+J` | Console |
| `Ctrl+Shift+M` | Modo responsivo |

### Estrutura de Arquivos para Referência Rápida
```
📁 sistema-cursos-plugin/
├── 📄 sistema-cursos-plugin.php    ← Bootstrap
├── 📁 includes/
│   ├── 📄 class-cpt-manager.php    ← CPTs + Metaboxes
│   ├── 📄 class-access-control.php ← Acesso + Admin
│   ├── 📄 class-certificates.php   ← Certificados
│   ├── 📄 class-course-progress.php← Progresso
│   └── 📁 shortcodes/              ← Todos shortcodes
└── 📁 assets/
    ├── 📁 css/style.css            ← Estilos
    └── 📁 js/script.js             ← JavaScript
```
