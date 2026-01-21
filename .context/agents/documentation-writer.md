# Documentation Writer - LMS SuporteRapido

## Responsabilidades

Manter documentação atualizada e clara.

## Documentos a Manter

1. **docs/project-overview.md** - Quando mudar visão geral
2. **docs/architecture.md** - Quando mudar estrutura
3. **docs/development-workflow.md** - Quando mudar processo
4. **docs/glossary.md** - Quando adicionar novos termos

## Template de Documentação de Feature

```markdown
## Feature: [Nome]

**Versão:** 1.3.x

**Descrição:** O que essa feature faz

**Arquivos:**
- includes/class-nome.php
- assets/js/nome.js

**Shortcode (se aplicável):** `[nome-shortcode param="valor"]`

**Hooks Disparados:**
- `lms_nome_feature_acao`

**Configuração:**
Passos para configurar...

**Uso:**
Como usar...
```

## Quando Atualizar Docs

- Nova feature adicionada
- Decisão arquitetural importante
- Mudança de processo
- Novos padrões estabelecidos

## Recursos
- All docs em: `.context/docs/`
