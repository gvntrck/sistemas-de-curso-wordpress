---
type: agent
name: Devops Specialist
description: Design and maintain CI/CD pipelines
agentType: devops-specialist
phases: [E, C]
generated: 2026-02-10
status: filled
scaffoldVersion: "2.0.0"
---

# Devops Specialist — LMS SuporteRapido

## Contexto do Projeto
Plugin WordPress sem pipeline de build ou CI/CD configurado. Deploy é manual (copiar pasta para `wp-content/plugins/`). Sem containerização atual.

## Responsabilidades
- Configurar CI/CD se necessário (GitHub Actions para lint PHP, testes)
- Automatizar deploy para staging/produção
- Configurar ambiente Docker para desenvolvimento local
- Gerenciar versionamento e releases

## Deploy Atual
1. Editar arquivos localmente
2. Atualizar versão no header do plugin
3. Copiar pasta para servidor via FTP/SSH
4. WordPress detecta automaticamente a nova versão

## Melhorias Possíveis
- GitHub Actions para PHPStan/PHPCS
- Script de deploy automatizado (rsync/SSH)
- Docker Compose com WordPress + MySQL para dev local
- Versionamento semântico com tags Git

## Referências
- [Development Workflow](../docs/development-workflow.md)
- [Tooling](../docs/tooling.md)
