# AGENTS.md

> Guia para agentes de IA trabalharem neste projeto de plugin WordPress.

## Visão Geral do Projeto

Este é um **plugin WordPress** chamado **Sistema de Cursos** (versão atual: 1.2.25) que gerencia:
- Cursos online e trilhas de aprendizado
- Aulas com controle de acesso e progresso
- Grupos de alunos com permissões
- Certificados de conclusão
- Shortcodes para frontend

**Stack tecnológica:**
- PHP 7.4+ (principal)
- JavaScript vanilla (interatividade frontend)
- CSS vanilla (estilos)
- WordPress 5.0+ (framework)

## Ambiente de Desenvolvimento

### Requisitos
- WordPress instalado localmente (XAMPP, Local WP, Docker, etc.)
- PHP 7.4 ou superior
- Este plugin deve estar em `wp-content/plugins/sistema-cursos-plugin/`

### Ativação
1. Copie a pasta do plugin para `wp-content/plugins/`
2. Ative o plugin no painel WordPress em Plugins → Plugins Instalados
3. Configure as opções no menu "Sistema de Cursos" no admin

### Sem Build Process
Este é um plugin PHP tradicional - **não há processo de build**. Alterações em arquivos PHP, JS e CSS são refletidas imediatamente.

## Instruções de Teste

### Testes Manuais (Recomendado)
1. Ative o plugin em um ambiente WordPress de desenvolvimento
2. Crie posts de teste para os CPTs: Trilha, Curso, Aula
3. Teste os shortcodes no frontend:
   - `[meus-cursos]` - Lista cursos do usuário
   - `[aula-player]` - Player de aula
   - `[certificado]` - Geração de certificados
   - `[resultado-busca]` - Resultados de busca

### Validação de Código
- Verifique sintaxe PHP: `php -l arquivo.php`
- Confira erros no debug.log do WordPress
- Use `WP_DEBUG = true` no `wp-config.php`

## Instruções para Pull Requests

### Versionamento
- **IMPORTANTE**: Sempre atualize a versão no header de `sistema-cursos-plugin.php` ao fazer alterações
- Use versionamento semântico: `MAJOR.MINOR.PATCH`

### Convenções de Commit
- Prefixos recomendados: `feat:`, `fix:`, `refactor:`, `docs:`, `style:`
- Exemplo: `feat: adiciona carrossel no shortcode meus-cursos`

### Checklist para PRs
- [ ] Versão atualizada no header do plugin
- [ ] Testado localmente no WordPress
- [ ] Sem erros de sintaxe PHP
- [ ] Código segue padrões WordPress (escapamento, sanitização)

## Mapa do Repositório

### `sistema-cursos-plugin.php`
**Arquivo principal do plugin.** Contém:
- Header com metadados (nome, versão, autor)
- Inicialização do plugin
- Carregamento de dependências via `includes/`
- Registro de hooks principais

**Quando editar:** Alterações na versão, adicionar novos includes, hooks globais.

### `includes/`
**Classes PHP do plugin.** Estrutura:
- `class-cpt-manager.php` - Registra Custom Post Types (Trilha, Curso, Aula, Grupo, Certificado)
- `class-access-control.php` - Controle de acesso e página admin de alunos
- `class-progress-tracker.php` - Rastreamento de progresso nas aulas
- `class-certificates.php` - Lógica de geração de certificados
- `shortcodes/` - Subpasta com classes de shortcodes

**Quando editar:** Adicionar funcionalidades, corrigir bugs, criar novos shortcodes.

### `includes/shortcodes/`
**Classes de shortcodes.** Cada arquivo = um shortcode:
- `class-shortcode-meus-cursos.php` - `[meus-cursos]`
- `class-shortcode-aula-player.php` - `[aula-player]`
- `class-shortcode-certificado.php` - `[certificado]`
- `class-shortcode-resultado-busca.php` - `[resultado-busca]`

**Quando editar:** Modificar output de shortcodes, adicionar parâmetros, ajustar estilos inline.

### `assets/`
**Recursos estáticos do plugin:**
- `css/style.css` - Estilos principais do frontend
- `js/script.js` - JavaScript para interatividade (progresso, player)
- `images/` - Imagens e ícones (se houver)

**Quando editar:** Ajustar estilos visuais, adicionar animações, modificar comportamento JS.

### `documentacao.txt`
**Documentação técnica** do plugin em formato texto. Descreve funcionalidades, configurações e uso.

**Quando editar:** Documentar novas features, atualizar instruções.

### `modelo.html`
**Template HTML de referência** para layouts de frontend. Usado como guia de design para os shortcodes.

**Quando editar:** Raramente - serve como referência visual.

### `roadmap.txt`
**Planejamento de features futuras** do plugin.

**Quando editar:** Adicionar ideias, marcar features concluídas.

## Referências do AI Context

- Índice de documentação: `.context/docs/README.md`
- Playbooks de agentes: `.context/agents/README.md`
- Arquitetura: `.context/docs/architecture.md`
- Fluxo de dados: `.context/docs/data-flow.md`

## Boas Práticas para Este Projeto

### Segurança WordPress
- Sempre use `esc_html()`, `esc_attr()` para output
- Use `wp_nonce_field()` e verificação em formulários
- Sanitize inputs com `sanitize_text_field()`, `intval()`, etc.

### Performance
- Evite queries desnecessárias no frontend
- Use transients para cache quando apropriado
- Minimize enqueue de assets desnecessários

### Compatibilidade
- Mantenha suporte a PHP 7.4+
- Evite funções deprecated do WordPress
- Teste com diferentes temas
