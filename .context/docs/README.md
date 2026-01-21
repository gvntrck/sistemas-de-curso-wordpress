# 📚 Documentação do LMS SuporteRapido

Bem-vindo à documentação completa do plugin **LMS SuporteRapido** - Sistema de Gerenciamento de Aprendizagem para WordPress.

## 🚀 Início Rápido

Novo no projeto? Comece por aqui:

1. **[Visão Geral do Projeto](project-overview.md)** - Entenda o que é o LMS e suas funcionalidades principais
2. **[Arquitetura](architecture.md)** - Compreenda a estrutura técnica do sistema
3. **[Workflow de Desenvolvimento](development-workflow.md)** - Aprenda como desenvolver e contribuir

## 📖 Índice da Documentação

### Documentação Essencial

| Documento | Descrição | Quando Usar |
|-----------|-----------|-------------|
| **[Visão Geral](project-overview.md)** | Introdução ao projeto, componentes principais e primeiros passos | Primeiro contato com o projeto |
| **[Arquitetura](architecture.md)** | Padrões arquiteturais, camadas do sistema e decisões técnicas | Entendimento profundo da estrutura |
| **[Fluxo de Dados](data-flow.md)** | Como os dados se movem pelo sistema | Implementar nova funcionalidade |
| **[Glossário](glossary.md)** | Termos técnicos e conceitos do sistema | Referência rápida de termos |

### Desenvolvimento e Qualidade

| Documento | Descrição | Quando Usar |
|-----------|-----------|-------------|
| **[Workflow de Desenvolvimento](development-workflow.md)** | Processo diário, branches, versionamento e padrões | Antes de começar a codificar |
| **[Estratégia de Testes](testing-strategy.md)** | Casos de teste e checklist de QA | Antes de fazer deploy |
| **[Segurança](security.md)** | Práticas de segurança e vulnerabilidades comuns | Implementar features sensíveis |
| **[Ferramentas](tooling.md)** | Setup do ambiente e ferramentas recomendadas | Configurar ambiente local |

### Referências Técnicas

| Recurso | Descrição |
|---------|-----------|
| **[Mapa do Código](codebase-map.json)** | Estrutura de arquivos e diretórios do projeto |
| **[Agentes Especializados](../agents/)** | Playbooks para diferentes tipos de tarefas de desenvolvimento |

## 🎯 Guias por Persona

### 👨‍💼 CEO / Gestor de Produto
```
1. project-overview.md (Seção: Propósito e Roadmap)
2. glossary.md (Para entender termos técnicos)
```

### 👨‍💻 Desenvolvedor Novo no Projeto
```
1. project-overview.md (Visão geral completa)
2. architecture.md (Entender a estrutura)
3. development-workflow.md (Como contribuir)
4. tooling.md (Setup do ambiente)
5. glossary.md (Termos do sistema)
```

### 🏗️ Arquiteto / Tech Lead
```
1. architecture.md (Padrões e decisões)
2. data-flow.md (Fluxo de dados completo)
3. security.md (Práticas de segurança)
4. codebase-map.json (Mapa técnico)
```

### 🐛 QA / Tester
```
1. testing-strategy.md (Casos de teste)
2. security.md (Testes de segurança)
3. glossary.md (Conceitos do sistema)
```

### 🔧 DevOps
```
1. development-workflow.md (Deploy e releases)
2. tooling.md (Ambientes e ferramentas)
3. security.md (Headers e configurações)
```

## 📊 Estrutura do Projeto

```
sistema-cursos-plugin/
├── sistema-cursos-plugin.php     # Arquivo principal do plugin
├── includes/                      # Classes core e lógica de negócio
│   ├── class-cpt-manager.php     # Gerenciamento de CPTs
│   ├── class-access-control.php  # Controle de acesso
│   ├── class-course-progress.php # Rastreamento de progresso
│   ├── class-certificates.php    # Sistema de certificados
│   ├── class-quiz-*.php          # Sistema de quizzes
│   ├── class-woocommerce-*.php   # Integração WooCommerce
│   └── shortcodes/               # Todos os shortcodes
├── assets/                        # CSS e JavaScript
│   ├── css/style.css
│   └── js/*.js
└── .context/                      # Contexto AI (este diretório)
    ├── docs/                      # Documentação
    ├── agents/                    # Playbooks de agentes
    ├── skills/                    # Habilidades especializadas
    └── workflow/                  # Estado do workflow PREVC
```

## 🔑 Conceitos-Chave do Sistema

### Custom Post Types (CPTs)
- **Curso** - Unidade principal de conteúdo educacional
- **Aula** - Conteúdo individual dentro de um curso
- **Trilha** - Agrupamento de cursos relacionados
- **Grupo** - Organização de alunos com permissões compartilhadas

### Fluxos Principais
1. **Acesso** - Concessão e verificação de permissões
2. **Aprendizado** - Navegação, progresso e conclusão
3. **Certificação** - Emissão automática baseada em progresso
4. **Integração** - WooCommerce para vendas e matrículas

### Shortcodes Principais
- `[meus-cursos]` - Lista cursos do usuário
- `[lista-aulas]` - Player de vídeo + sidebar
- `[certificado]` - Visualização de certificados
- `[minha-conta]` - Painel do usuário

## 🛠️ Desenvolvimento

### Antes de Começar a Codificar
1. Ler `development-workflow.md`
2. Entender padrões: **SOLID, DRY, KISS**
3. Lembrar: **Sempre atualizar versão em 3 lugares!**

### Antes de Fazer Deploy
1. Seguir checklist em `testing-strategy.md`
2. Revisar `security.md` para operações sensíveis
3. Atualizar documentação se necessário

## 📞 Recursos Externos

- **WordPress Developer Docs:** https://developer.wordpress.org/
- **WooCommerce Docs:** https://woocommerce.com/documentation/
- **ACF Docs:** https://www.advancedcustomfields.com/resources/

## 🔄 Manutenção da Documentação

Esta documentação deve ser atualizada quando:
- Nova funcionalidade é adicionada
- Arquitetura é modificada
- Novos padrões são estabelecidos
- Decisões técnicas importantes são tomadas

### Como Atualizar
1. Editar arquivo `.md` relevante
2. Atualizar cross-references se necessário
3. Commit com mensagem `[docs] Descrição da atualização`

## 📝 Template de Decisão Técnica

Quando uma decisão arquitetural importante for tomada, adicione em `architecture.md` seção "Decisões Arquiteturais":

```markdown
### [Número]. [Título da Decisão]

**Contexto:** Por que essa decisão foi necessária?

**Opções Consideradas:**
1. Opção A - Prós e contras
2. Opção B - Prós e contras

**Decisão:** Qual opção foi escolhida e por quê

**Consequências:** Impacto da decisão no sistema

**Data:** YYYY-MM-DD
```

## 🎓 Aprendizado Contínuo

### WordPress
- [ ] Entender Hooks (Actions & Filters)
- [ ] Dominar WP_Query
- [ ] Conhecer WordPress Security Handbook

### PHP
- [ ] Princípios SOLID
- [ ] PSR Standards
- [ ] Design Patterns comuns

### Específico do Projeto
- [ ] Fluxo de acesso e permissões
- [ ] Sistema de progresso e certificados
- [ ] Navegação AJAX entre aulas
- [ ] Integração WooCommerce

---

**Versão da Documentação:** 1.0  
**Última Atualização:** 2026-01-21  
**Mantido por:** Equipe de Desenvolvimento

Para sugestões ou correções nesta documentação, contate o time técnico.
