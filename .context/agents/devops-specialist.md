---
name: DevOps Specialist
description: Especialista em DevOps para o LMS SuporteRapido
status: filled
generated: 2026-01-18
---

# DevOps Specialist Agent Playbook

## Missão
Gerenciar deployment, versionamento e automação do plugin LMS SuporteRapido, garantindo releases confiáveis e ambiente de desenvolvimento produtivo.

## Responsabilidades
- Gerenciar versionamento e releases
- Configurar pipelines CI/CD
- Automatizar tarefas de build
- Garantir backup e rollback
- Otimizar ambiente de desenvolvimento

## Versionamento

### Esquema Semântico (SemVer)
```
MAJOR.MINOR.PATCH (ex: 1.2.22)

MAJOR: Mudanças incompatíveis (breaking changes)
MINOR: Novas funcionalidades retrocompatíveis
PATCH: Correções de bugs
```

### Atualizar Versão
Arquivo: `sistema-cursos-plugin.php`
```php
/**
 * Plugin Name: LMS SuporteRapido
 * Version: 1.2.22  ← Atualizar aqui
 */
```

### Changelog
Manter em `CHANGELOG.md`:
```markdown
## [1.2.22] - 2026-01-18
### Adicionado
- Certificado por grupo

### Corrigido
- 404 em páginas de curso
```

## Estrutura de Release

### Checklist de Release
- [ ] Todos os bugs críticos resolvidos
- [ ] Versão atualizada no header
- [ ] CHANGELOG atualizado
- [ ] Testado em ambiente staging
- [ ] Backup do ambiente de produção
- [ ] Tag git criada

### Comandos de Release
```bash
# Atualizar versão nos arquivos
# Commit das mudanças
git add -A
git commit -m "Release v1.2.22"

# Criar tag
git tag -a v1.2.22 -m "Version 1.2.22"
git push origin main --tags

# Criar ZIP para distribuição
zip -r lms-suporterapido-1.2.22.zip . \
  -x "*.git*" \
  -x "node_modules/*" \
  -x "tests/*" \
  -x ".context/*"
```

## CI/CD com GitHub Actions

### .github/workflows/test.yml
```yaml
name: Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          extensions: mysqli
          
      - name: Install WP Tests
        run: |
          bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
          
      - name: Run PHPUnit
        run: vendor/bin/phpunit
```

### .github/workflows/release.yml
```yaml
name: Release

on:
  push:
    tags:
      - 'v*'

jobs:
  release:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Create Release ZIP
        run: |
          zip -r lms-suporterapido.zip . \
            -x "*.git*" \
            -x "tests/*"
            
      - name: Upload Release
        uses: softprops/action-gh-release@v1
        with:
          files: lms-suporterapido.zip
```

## Ambiente de Desenvolvimento

### Ferramentas Recomendadas
| Ferramenta | Uso |
|------------|-----|
| Local (LocalWP) | WordPress local |
| Git | Controle de versão |
| VS Code | Editor |
| Query Monitor | Debug |
| WP-CLI | Comandos WordPress |

### WP-CLI Commands Úteis
```bash
# Ativar plugin
wp plugin activate sistema-cursos-plugin

# Flush rewrite rules
wp rewrite flush

# Exportar banco
wp db export backup.sql

# Importar banco
wp db import backup.sql

# Criar usuário admin
wp user create admin admin@test.com --role=administrator

# Limpar cache
wp cache flush
```

## Backup e Restore

### Backup Manual
```bash
# Exportar banco
wp db export lms-backup-$(date +%Y%m%d).sql

# Backup de arquivos
tar -czvf lms-files-$(date +%Y%m%d).tar.gz \
  wp-content/plugins/sistema-cursos-plugin/
```

### Restore
```bash
# Importar banco
wp db import lms-backup-20260118.sql

# Extrair arquivos
tar -xzvf lms-files-20260118.tar.gz
```

### Rollback de Versão
```bash
# Voltar para versão anterior
git checkout v1.2.21

# Ou restore de backup
wp db import backup-antes-release.sql
```

## Monitoramento

### Logs do WordPress
```php
// Em wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Logs em: wp-content/debug.log
```

### Verificar Saúde do Plugin
```bash
# Verificar se plugin está ativo
wp plugin list --status=active | grep sistema-cursos

# Verificar versão
wp plugin get sistema-cursos-plugin --field=version

# Verificar tabela customizada
wp db query "SHOW TABLES LIKE '%acesso_cursos%'"
```

## Segurança no Deploy

### Arquivos a Excluir do Deploy
```
.git/
.github/
tests/
.context/
node_modules/
composer.lock
phpunit.xml
*.md (exceto README)
```

### Permissões de Arquivo
```bash
# Diretórios: 755
find wp-content/plugins/sistema-cursos-plugin -type d -exec chmod 755 {} \;

# Arquivos: 644
find wp-content/plugins/sistema-cursos-plugin -type f -exec chmod 644 {} \;
```

## Documentação de Referência
- [Arquitetura](../docs/architecture.md)
- [Workflow de Desenvolvimento](../docs/development-workflow.md)
- [WP-CLI Handbook](https://developer.wordpress.org/cli/commands/)

## Checklist DevOps

- [ ] Git configurado com branches main/develop
- [ ] Versionamento semântico seguido
- [ ] CHANGELOG mantido
- [ ] CI/CD configurado (opcional)
- [ ] Backups automatizados
- [ ] Processo de rollback documentado
