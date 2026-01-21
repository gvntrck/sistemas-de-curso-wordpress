# Visão Geral do Projeto

## Sobre o Projeto

**LMS SuporteRapido** é um plugin WordPress completo para gerenciamento de cursos online (Learning Management System), desenvolvido como uma alternativa robusta e customizável ao Learndash.

- **Nome:** LMS SuporteRapido
- **Versão:** 1.3.10
- **Autor:** Giovani Tureck
- **Tipo:** Plugin WordPress
- **Text Domain:** lms-suporte-rapido

## Propósito

Este plugin foi desenvolvido para criar e gerenciar um sistema completo de cursos online dentro do WordPress, oferecendo funcionalidades como:

- Gerenciamento de cursos, aulas e trilhas de aprendizado
- Sistema de controle de acesso e permissões
- Acompanhamento de progresso do aluno
- Sistema de certificados personalizáveis
- Integração com WooCommerce para vendas de cursos
- Sistema de quizzes e avaliações
- Controle anti-pirataria e log de acessos
- Sistema de grupos de alunos

## Componentes Principais

### Custom Post Types (CPTs)
1. **Curso** (`curso`) - Representa um curso completo
2. **Aula** (`aula`) - Representa uma aula individual
3. **Trilha** (`trilha`) - Agrupa múltiplos cursos em uma jornada de aprendizado
4. **Grupo** (`grupo_aluno`) - Organiza alunos em grupos

### Funcionalidades Core

#### 1. Gerenciamento de Acesso (`class-access-control.php`)
- Controle de permissões por usuário
- Sistema de datas de validade de acesso
- Gestão de acesso a trilhas e cursos
- Logs de atividades e segurança anti-pirataria

#### 2. Progress Tracker (`class-course-progress.php`)
- Rastreamento de progresso individual por curso
- Sistema de marcação de aulas como concluídas
- Cálculo de percentuais de conclusão

#### 3. Sistema de Certificados (`class-certificates.php`)
- Geração automática de certificados
- Personalização por curso ou trilha
- Emissão baseada em percentual de conclusão configurável

#### 4. Sistema de Quizzes
- **Builder** (`class-quiz-builder.php`) - Criação de questionários dentro das aulas
- **Process** (`class-quiz-process.php`) - Processamento de respostas e pontuação
- Controle de tentativas máximas
- Validação de respostas

#### 5. Integração WooCommerce (`class-woocommerce-integration.php`)
- Matricula automática após compra
- Configuração de produtos vinculados a cursos/trilhas
- Definição de duração de acesso

### Shortcodes Disponíveis

| Shortcode | Descrição |
|-----------|-----------|
| `[meus-cursos]` | Lista todos os cursos do usuário logado com progresso |
| `[lista-aulas]` | Player de vídeo + sidebar de aulas |
| `[minha-conta]` | Painel de edição de dados do usuário |
| `[cadastro-usuario]` | Formulário de cadastro + importação CSV |
| `[certificado]` | Gestão e exibição de certificados |
| `[barra-progresso-geral]` | Barra de progresso geral de todos os cursos |
| `[single-trilha]` | Exibição de cursos de uma trilha |
| `[cursos_da_trilha]` | Lista personalizável de cursos por trilha |
| `[resultado-busca]` | Resultados de pesquisa customizados |
| `[redireciona-aula]` | Utilitário para redirecionamento de aulas |

### Sistema de Roles

- **Role Aluno** (`role-aluno.php`)
  - Acesso restrito ao backend WordPress
  - Barra de administração oculta
  - Permissões específicas para alunos

## Stack Tecnológico

- **Backend:** PHP 7.4+
- **CMS:** WordPress 5.8+
- **Frontend:** JavaScript (jQuery), HTML5, CSS3
- **AJAX:** Para navegação dinâmica entre aulas
- **Integrações:**
  - WooCommerce (opcional, para e-commerce)
  - Advanced Custom Fields (ACF) para metaboxes

## Estrutura de Arquivos

```
sistema-cursos-plugin/
├── sistema-cursos-plugin.php     # Arquivo principal
├── assets/
│   ├── css/                       # Estilos do plugin
│   └── js/                        # Scripts JavaScript
├── includes/
│   ├── class-cpt-manager.php      # Gerenciamento de CPTs
│   ├── class-access-control.php   # Controle de acesso
│   ├── class-course-progress.php  # Progresso de cursos
│   ├── class-certificates.php     # Sistema de certificados
│   ├── class-quiz-builder.php     # Construtor de quizzes
│   ├── class-quiz-process.php     # Processamento de quizzes
│   ├── class-woocommerce-integration.php
│   ├── class-user-fields.php      # Campos adicionais de usuário
│   ├── class-admin-filters.php    # Filtros administrativos
│   ├── class-assets.php           # Carregamento de assets
│   ├── class-config.php           # Configurações
│   ├── role-aluno.php             # Role de aluno
│   └── shortcodes/                # Todos os shortcodes
│       ├── class-shortcode-meus-cursos.php
│       ├── class-shortcode-lista-aulas.php
│       └── ... (outros shortcodes)
└── .context/                      # Contexto AI (este diretório)
```

## Primeiros Passos

### Instalação
1. Fazer upload do plugin para `/wp-content/plugins/`
2. Ativar o plugin no painel WordPress
3. O plugin criará automaticamente:
   - Custom Post Types (Curso, Aula, Trilha, Grupo)
   - Role de Aluno
   - Tabelas personalizadas no banco de dados
   - Menu administrativo "LMS SuporteRapido"

### Configuração Inicial
1. Acesse **LMS SuporteRapido** no menu admin
2. Configure os shortcodes nas páginas apropriadas
3. Crie cursos, aulas e trilhas
4. Configure certificados conforme necessário
5. (Opcional) Configure produtos WooCommerce vinculados

### Desenvolvimento
- Código segue princípios **SOLID**, **Clean Code**, **DRY** e **KISS**
- Erros de lint são toleráveis em código WordPress
- Sempre atualizar número de versão no header ao modificar o plugin

## Roadmap

Funcionalidades planejadas:
- [ ] "Continuar Assistindo" na home
- [ ] Log de Atividades aprimorado
- [ ] Ordenação de aulas dependente do curso
- [ ] Nome do curso no resultado de busca de aula
- [ ] Liberação de aulas/trilhas com agenda
- [ ] Mensagem de aula bloqueada até data de liberação
- [ ] Certificado por grupo de alunos

## Recursos Relacionados

- **Documentação Técnica:** Ver `architecture.md`
- **Fluxo de Dados:** Ver `data-flow.md`
- **Workflow de Desenvolvimento:** Ver `development-workflow.md`
- **Estrutura do Código:** Ver `codebase-map.json`
