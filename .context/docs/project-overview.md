# LMS SuporteRapido - Visão Geral do Projeto

## Descrição

O **LMS SuporteRapido** é um plugin WordPress desenvolvido como alternativa ao LearnDash, oferecendo uma solução completa de Learning Management System (LMS) para criação e gerenciamento de cursos online.

## Objetivo

Fornecer uma plataforma de ensino online integrada ao WordPress com:
- Gerenciamento de cursos, trilhas de aprendizado e aulas
- Controle de acesso e matrículas de alunos
- Acompanhamento de progresso
- Emissão de certificados personalizáveis
- Gerenciamento de grupos de alunos

## Principais Funcionalidades

### Gestão de Conteúdo
- **Trilhas (Tracks)**: Agrupamentos de cursos relacionados
- **Cursos**: Unidade principal de conteúdo com múltiplas aulas
- **Aulas**: Conteúdo individual com vídeos (Vimeo), descrições e materiais

### Controle de Acesso
- Matrícula de alunos em cursos individuais ou trilhas completas
- Suporte a grupos de alunos com acesso compartilhado
- Sistema de permissões com status (ativo, suspenso, revogado)
- Controle de validade (acesso vitalício ou com data de expiração)

### Acompanhamento de Progresso
- Marcação de aulas como concluídas
- Barra de progresso visual por curso
- Cálculo automático de percentual de conclusão

### Certificados
- Templates personalizáveis por curso ou grupo
- Geração dinâmica de PDF
- Suporte a Google Fonts e imagens de fundo
- Placeholders para nome, curso, data e carga horária

### Painel do Aluno
- Visualização de cursos matriculados
- Gerenciamento de perfil (foto, senha)
- Busca de conteúdo

## Stack Tecnológica

| Componente | Tecnologia |
|------------|------------|
| Plataforma | WordPress 6.x+ |
| Linguagem | PHP 7.4+ |
| Frontend | JavaScript (ES6), CSS3 |
| Database | MySQL (tabela customizada `wp_acesso_cursos`) |
| PDF | HTML Canvas + html2pdf.js |
| Vídeo | Vimeo oEmbed |

## Versão Atual

**1.2.22** - Janeiro 2026

## Autor

**Giovani Tureck** - SuporteRapido

## Estrutura de Arquivos

```
sistema-cursos-plugin/
├── sistema-cursos-plugin.php    # Arquivo principal do plugin
├── includes/
│   ├── class-cpt-manager.php    # Custom Post Types (Trilha, Curso, Aula, Grupo, Certificado)
│   ├── class-access-control.php # Controle de acesso e painel admin
│   ├── class-certificates.php   # Gerenciamento de certificados
│   ├── class-course-progress.php# Progresso do aluno
│   ├── class-user-fields.php    # Campos customizados de usuário
│   ├── class-assets.php         # CSS e JavaScript
│   ├── class-config.php         # Configurações
│   ├── role-aluno.php           # Role customizada "aluno"
│   └── shortcodes/              # Todos os shortcodes do plugin
├── assets/
│   ├── css/style.css            # Estilos frontend
│   └── js/script.js             # JavaScript frontend
└── documentacao.txt             # Documentação técnica rápida
```

## Links Relacionados

- [Documentação de Arquitetura](architecture.md)
- [Fluxo de Dados](data-flow.md)
- [Glossário](glossary.md)
