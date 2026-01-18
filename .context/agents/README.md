# Agent Handbook - LMS SuporteRapido

Este diretório contém playbooks de agentes IA personalizados para colaboração no plugin WordPress **LMS SuporteRapido**.

## Sobre o Projeto

O LMS SuporteRapido é um plugin WordPress de Learning Management System que oferece:
- Gerenciamento de Trilhas, Cursos e Aulas
- Controle de acesso e matrículas
- Grupos de alunos
- Certificados personalizáveis
- Acompanhamento de progresso

## Agentes Disponíveis

### Desenvolvimento
| Agente | Descrição |
|--------|-----------|
| [Feature Developer](./feature-developer.md) | Implementar novas funcionalidades |
| [Bug Fixer](./bug-fixer.md) | Diagnosticar e corrigir bugs |
| [Backend Specialist](./backend-specialist.md) | PHP/WordPress, CPTs, APIs |
| [Frontend Specialist](./frontend-specialist.md) | CSS, JavaScript, UI |
| [Database Specialist](./database-specialist.md) | Queries, tabelas, otimização |

### Qualidade
| Agente | Descrição |
|--------|-----------|
| [Code Reviewer](./code-reviewer.md) | Revisar código e garantir qualidade |
| [Test Writer](./test-writer.md) | Criar testes automatizados |
| [Security Auditor](./security-auditor.md) | Auditar vulnerabilidades |
| [Performance Optimizer](./performance-optimizer.md) | Otimizar performance |

### Arquitetura e Documentação
| Agente | Descrição |
|--------|-----------|
| [Architect Specialist](./architect-specialist.md) | Decisões arquiteturais |
| [Refactoring Specialist](./refactoring-specialist.md) | Melhorar código existente |
| [Documentation Writer](./documentation-writer.md) | Criar documentação |

### Infraestrutura
| Agente | Descrição |
|--------|-----------|
| [DevOps Specialist](./devops-specialist.md) | CI/CD, deployment, versionamento |
| [Mobile Specialist](./mobile-specialist.md) | Responsividade e touch |

## Como Usar

### 1. Escolha o Agente Apropriado
Baseado na tarefa, selecione o agente que melhor se alinha:
- **Nova feature?** → Feature Developer
- **Bug reportado?** → Bug Fixer
- **Código lento?** → Performance Optimizer
- **Revisão de PR?** → Code Reviewer

### 2. Leia o Playbook
Cada playbook contém:
- Missão e responsabilidades
- Arquivos-chave do projeto
- Padrões e exemplos de código
- Checklists de verificação

### 3. Execute a Tarefa
Siga as instruções do playbook para:
- Localizar arquivos relevantes
- Aplicar padrões do projeto
- Validar as mudanças

### 4. Documente Aprendizados
Atualize o playbook ou documentação com insights novos.

## Arquivos-Chave do Projeto

```
sistema-cursos-plugin.php       # Bootstrap do plugin
includes/
├── class-cpt-manager.php       # CPTs principais
├── class-access-control.php    # Controle de acesso (maior arquivo)
├── class-certificates.php      # Certificados
├── class-course-progress.php   # Progresso
└── shortcodes/                 # 10 shortcodes
```

## Recursos Relacionados

- [Visão Geral do Projeto](../docs/project-overview.md)
- [Arquitetura](../docs/architecture.md)
- [Fluxo de Dados](../docs/data-flow.md)
- [Glossário](../docs/glossary.md)
- [Workflow de Desenvolvimento](../docs/development-workflow.md)

## Status dos Agents

| Agente | Status |
|--------|--------|
| Feature Developer | ✅ Preenchido |
| Bug Fixer | ✅ Preenchido |
| Code Reviewer | ✅ Preenchido |
| Performance Optimizer | ✅ Preenchido |
| Security Auditor | ✅ Preenchido |
| Documentation Writer | ✅ Preenchido |
| Backend Specialist | ✅ Preenchido |
| Frontend Specialist | ✅ Preenchido |
| Database Specialist | ✅ Preenchido |
| Refactoring Specialist | ✅ Preenchido |
| Architect Specialist | ✅ Preenchido |
| Test Writer | ✅ Preenchido |
| DevOps Specialist | ✅ Preenchido |
| Mobile Specialist | ✅ Preenchido |
