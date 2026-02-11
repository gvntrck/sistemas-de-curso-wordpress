---
type: doc
name: data-flow
description: How data moves through the system and external integrations
category: data-flow
generated: 2026-02-10
status: filled
scaffoldVersion: "2.0.0"
---

## Data Flow & Integrations

O sistema utiliza uma combinação de tabelas customizadas do MySQL e post meta do WordPress para armazenar e movimentar dados. As interações do frontend são feitas via AJAX, enquanto o admin usa formulários POST tradicionais do WordPress.

## Module Dependencies

- **Shortcodes** → `class-access-control.php` (verificação de acesso), `class-course-progress.php` (progresso)
- **class-access-control.php** → `class-course-progress.php` (detalhes de progresso), `$wpdb` (tabela `wp_acesso_cursos`)
- **class-course-progress.php** → `$wpdb` (tabela `wp_progresso_aluno`)
- **class-woocommerce-integration.php** → `class-access-control.php` (conceder acesso pós-compra)
- **class-certificates.php** → `class-course-progress.php` (verificar conclusão do curso)
- **class-cpt-manager.php** → WordPress Core (register_post_type, metaboxes)
- **assets/js/script.js** → AJAX endpoints PHP (toggle aula concluída, carregar seções do painel)

## Service Layer

- [`System_Cursos_Access_Control`](../../includes/class-access-control.php) — Gerencia matrículas, verificação de acesso (direto, grupo, trilha), log de ações
- [`System_Cursos_Progress`](../../includes/class-course-progress.php) — Rastreamento de progresso, marcar/desmarcar aulas concluídas
- [`System_Cursos_Certificates`](../../includes/class-certificates.php) — Geração e listagem de certificados
- [`System_Cursos_CPT_Manager`](../../includes/class-cpt-manager.php) — Registro de CPTs e metaboxes
- [`System_Cursos_WooCommerce`](../../includes/class-woocommerce-integration.php) — Integração e-commerce
- [`System_Cursos_Quiz_Builder`](../../includes/class-quiz-builder.php) — Construtor de quizzes no admin
- [`System_Cursos_Quiz_Process`](../../includes/class-quiz-process.php) — Processamento de respostas de quizzes

## High-level Flow

```
Aluno acessa página com [lms-painel]
    → WordPress processa shortcode
    → Verifica login (wp_get_current_user)
    → Renderiza shell SPA com sidebar
    → JS carrega seção via AJAX
        → PHP handler verifica acesso (Access_Control::has_access)
        → Consulta dados (WP_Query + tabelas customizadas)
        → Retorna HTML da seção
    → Aluno interage (ex: concluir aula)
        → AJAX POST → PHP handler
        → Atualiza tabela wp_progresso_aluno
        → Retorna JSON com novo estado
        → JS atualiza UI (barra de progresso, ícones)
```

### Fluxo de Compra (WooCommerce)

```
Cliente compra produto vinculado a curso/trilha/grupo
    → WooCommerce dispara hook woocommerce_order_status_completed
    → System_Cursos_WooCommerce::grant_access_on_purchase()
    → Verifica tipo de vínculo (curso, trilha, grupo)
    → Chama Access_Control::grant_access() ou adiciona grupo ao user_meta
    → Log registrado em wp_acesso_cursos_log
```

## Internal Movement

- **Shortcodes → Access Control**: Toda renderização de conteúdo protegido passa por `has_access()` que verifica 3 fontes: acesso direto (tabela), grupo do aluno, grupo da trilha.
- **Admin → Banco**: Formulários POST no admin processados em `admin_process()` com nonce verification.
- **Frontend → AJAX**: Todas as interações dinâmicas (concluir aula, navegar painel, ordenar conteúdo) usam `wp_ajax_*` handlers.
- **Progresso → Certificado**: Quando 100% das aulas são concluídas, o sistema habilita a geração do certificado.

## External Integrations

- **WooCommerce** (opcional): Vincula produtos a cursos/trilhas/grupos. Ao completar pedido, concede acesso automaticamente. Autenticação via hooks internos do WP.
- **WordPress User System**: Usa roles customizadas (`aluno`) e user_meta para dados adicionais (telefone, Instagram, grupos).
- **jQuery UI Sortable**: Usado no admin para ordenação drag-and-drop de trilhas e cursos.

## Observability & Failure Modes

- **Log de acesso**: Tabela `wp_acesso_cursos_log` registra todas as ações (concedido, revogado, suspenso, reativado, grupo_entrou, grupo_saiu).
- **Login tracking**: `_login_history` no user_meta armazena últimos 50 logins com IP e User-Agent (anti-pirataria).
- **Grupos órfãos**: `cleanup_orphaned_group_references()` limpa referências a grupos deletados de cursos, trilhas e usuários.
- **Fallback de progresso**: Se `System_Cursos_Progress` não estiver disponível, `get_detailed_progress()` retorna array vazio.
- **Erros AJAX**: Handlers retornam `wp_send_json_error()` com mensagem descritiva.
