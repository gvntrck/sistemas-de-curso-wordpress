# Documentation Index - LMS SuporteRapido

Bem-vindo à base de conhecimento do plugin **LMS SuporteRapido** para WordPress.

## Guias de Documentação

### Visão Geral
- **[Visão Geral do Projeto](./project-overview.md)** - Descrição, funcionalidades e stack tecnológica
- **[Arquitetura](./architecture.md)** - Componentes, padrões de design e decisões arquiteturais
- **[Glossário](./glossary.md)** - Termos do domínio e definições técnicas

### Desenvolvimento
- **[Workflow de Desenvolvimento](./development-workflow.md)** - Ambiente, convenções e padrões
- **[Fluxo de Dados](./data-flow.md)** - Modelo de dados, relacionamentos e fluxos
- **[Ferramentas e Produtividade](./tooling.md)** - Configurações, scripts e dicas

### Qualidade
- **[Estratégia de Testes](./testing-strategy.md)** - Tipos de teste e procedimentos
- **[Segurança](./security.md)** - Autenticação, sanitização e boas práticas

## Estrutura do Repositório

```
sistema-cursos-plugin/
├── sistema-cursos-plugin.php    # Arquivo principal do plugin
├── includes/                    # Classes PHP do plugin
│   ├── class-cpt-manager.php    # CPTs: Trilha, Curso, Aula, Grupo, Certificado
│   ├── class-access-control.php # Controle de acesso e painel admin
│   ├── class-certificates.php   # Templates de certificado
│   ├── class-course-progress.php# Rastreamento de progresso
│   ├── class-user-fields.php    # Campos customizados de usuário
│   ├── class-assets.php         # CSS e JavaScript
│   └── shortcodes/              # 10 shortcodes do plugin
├── assets/                      # Recursos estáticos
│   ├── css/style.css            # Estilos frontend
│   └── js/script.js             # JavaScript frontend
└── documentacao.txt             # Documentação técnica rápida
```

## Mapa de Documentação

| Guia | Arquivo | Conteúdo Principal |
|------|---------|-------------------|
| Visão Geral | `project-overview.md` | Descrição, funcionalidades, versão |
| Arquitetura | `architecture.md` | Componentes, CPTs, shortcodes, padrões |
| Workflow | `development-workflow.md` | Ambiente, convenções, boas práticas |
| Testes | `testing-strategy.md` | Testes manuais, validação, checklist |
| Glossário | `glossary.md` | Termos de domínio, campos, status |
| Fluxo de Dados | `data-flow.md` | Relacionamentos, queries, transformações |
| Segurança | `security.md` | Auth, sanitização, CSRF, SQL injection |
| Ferramentas | `tooling.md` | Ambiente, extensões, snippets |

## Status da Documentação

| Documento | Status |
|-----------|--------|
| project-overview.md | ✅ Preenchido |
| architecture.md | ✅ Preenchido |
| development-workflow.md | ✅ Preenchido |
| testing-strategy.md | ✅ Preenchido |
| glossary.md | ✅ Preenchido |
| data-flow.md | ✅ Preenchido |
| security.md | ✅ Preenchido |
| tooling.md | ✅ Preenchido |

## Links Rápidos

- **Agents**: [README dos Agents](../agents/README.md)
- **Plans**: [Pasta de Plans](../plans/)
- **Plugin Principal**: [sistema-cursos-plugin.php](../../sistema-cursos-plugin.php)

## Atualização

Última atualização: Janeiro 2026

Ao adicionar novas funcionalidades ao plugin, mantenha esta documentação atualizada:
1. Atualize o arquivo relevante (ex: `architecture.md` para novos CPTs)
2. Adicione novos termos ao `glossary.md`
3. Documente novos fluxos em `data-flow.md`
