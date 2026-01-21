# Fluxo de Dados

## Visão Geral

O LMS SuporteRapido processa dados em três fluxos principais:
1. **Fluxo de Acesso** - Concessão e verificação de permissões
2. **Fluxo de Aprendizado** - Navegação, progresso e conclusão
3. **Fluxo de Integração** - WooCommerce e sistemas externos

## Fluxo de Acesso

### Concessão Manual de Acesso

```
Admin → Detalhes do Aluno → Conceder Acesso
    ↓
class-access-control.php::grant_access()
    ↓
update_user_meta($user_id, 'acesso_cursos', [...])
    ↓
Registrar em wp_acesso_cursos_log
    ↓
Retornar confirmação
```

**Dados Gravados:**
```php
user_meta['acesso_cursos'][curso_id] = [
    'validade' => 'YYYY-MM-DD' | 'vitalicio',
    'origem' => 'manual'
];
```

**Log:**
```sql
INSERT INTO wp_acesso_cursos_log (
    user_id, curso_id, acao, origem, validade, data_acao
) VALUES (
    123, 456, 'acesso_concedido', 'manual', '2026-12-31', NOW()
);
```

### Verificação de Acesso

```
Usuário acessa página do curso
    ↓
Shortcode [lista-aulas curso_id="X"]
    ↓
class-access-control::has_access($user_id, $curso_id)
    ↓
Ler user_meta['acesso_cursos']
    ↓
    ├─ Não tem registro? → return false
    ├─ Validade expirada? → return false
    └─ Acesso válido? → return true
    ↓
Renderizar conteúdo OU mensagem de bloqueio
```

## Fluxo de Aprendizado

### Navegação entre Aulas (AJAX)

```
[Frontend] Usuário clica em aula na sidebar
    ↓
JavaScript: navegacao-aulas.js dispara AJAX
    ↓
Data: {
    action: 'carregar_aula',
    aula_id: 789,
    curso_id: 456,
    nonce: '...'
}
    ↓
[Backend] wp_ajax_carregar_aula
    ↓
Verificar acesso ao curso
    ↓
Buscar dados da aula (post_meta)
    ↓
Retornar JSON: {
    titulo: '...',
    link_video: '...',
    descricao: '...',
    quiz_enabled: true/false,
    quiz_data: {...}
}
    ↓
[Frontend] Atualizar DOM
    - Trocar vídeo no player
    - Atualizar título e descrição
    - Renderizar quiz se houver
```

### Marcação de Aula como Completa

```
[Frontend] Usuário clica "Marcar como Completa"
    ↓
AJAX → {
    action: 'marcar_aula_completa',
    aula_id: 789,
    curso_id: 456,
    nonce: '...'
}
    ↓
[Backend] class-course-progress::mark_lesson_complete()
    ↓
Ler user_meta['aulas_completas'][curso_id]
    ↓
Adicionar aula_id ao array
    ↓
update_user_meta($user_id, 'aulas_completas', [...])
    ↓
Recalcular progresso do curso
    ↓
$progresso = (count(aulas_completas) / total_aulas) * 100
    ↓
do_action('sistema_cursos_aula_completa', $user_id, $curso_id, $aula_id)
    ↓
Verificar se atingiu percentual para certificado
    ↓
    └─ Se sim → class-certificates::issue_certificate()
    ↓
Retornar JSON: {
    success: true,
    progresso: 75.5,
    certificado_emitido: true/false
}
```

### Processamento de Quiz

```
[Frontend] Usuário submete quiz
    ↓
AJAX → {
    action: 'processar_quiz',
    aula_id: 789,
    respostas: {
        'q1': 'opcao_a',
        'q2': 'opcao_c'
    },
    nonce: '...'
}
    ↓
[Backend] class-quiz-process::process_submission()
    ↓
Buscar quiz_data da aula
    ↓
Validar respostas (comparar com gabarito)
    ↓
Calcular pontuação:
    score = respostas_corretas
    max_score = total_perguntas
    ↓
Verificar se passou (score >= nota_minima)
    ↓
Registrar tentativa:
INSERT INTO wp_quiz_attempts (
    user_id, aula_id, score, max_score, 
    passed, attempt_number, answers
)
    ↓
    ├─ Se passou → Marcar aula como completa
    └─ Se falhou → Verificar tentativas restantes
    ↓
Retornar JSON: {
    success: true,
    passed: true/false,
    score: 8,
    max_score: 10,
    tentativas_restantes: 2
}
```

## Fluxo de Integração WooCommerce

### Compra de Produto Vinculado

```
Cliente finaliza compra no WooCommerce
    ↓
Pedido muda para status "Completed"
    ↓
Hook: woocommerce_order_status_completed
    ↓
class-woocommerce-integration::handle_order_completed($order_id)
    ↓
Buscar itens do pedido
    ↓
Para cada item:
    ↓
    Ler product_meta:
        - _curso_vinculado
        - _trilha_vinculada
        - _duracao_acesso
    ↓
    Se tem curso/trilha vinculado:
        ↓
        Buscar user_id do pedido
        ↓
        Calcular validade:
            - Se _duracao_acesso = 0 → 'vitalicio'
            - Se _duracao_acesso > 0 → date('+X days')
        ↓
        class-access-control::grant_access(
            $user_id,
            $curso_id | $trilha_id,
            $validade,
            'woocommerce'
        )
        ↓
        Registrar log
        ↓
        Enviar email de boas-vindas (futuro)
```

**Product Meta (WooCommerce):**
```php
[
    '_curso_vinculado' => 456,       // ID do curso
    '_trilha_vinculada' => 0,        // ID da trilha (0 = nenhuma)
    '_duracao_acesso' => 365         // dias (0 = vitalício)
]
```

## Fluxo de Dados de Grupo

### Adicionar Aluno a Grupo

```
Admin → Detalhes do Aluno → Adicionar a Grupo
    ↓
Selecionar grupo_id
    ↓
update_user_meta($user_id, 'grupo_aluno', $grupo_id)
    ↓
Buscar post_meta do grupo:
    - cursos_permitidos[]
    - trilhas_permitidas[]
    ↓
Para cada curso/trilha permitido:
    ↓
    Conceder acesso com origem 'grupo'
    ↓
    update_user_meta('acesso_cursos', [...])
    ↓
Registrar em log:
    acao = 'entrou_grupo'
```

### Herança de Permissões

```
Grupo tem cursos [A, B, C]
    ↓
Usuário entra no grupo
    ↓
Sistema verifica cursos do grupo
    ↓
Para cada curso:
    ↓
    Se usuário NÃO tem acesso:
        → Conceder acesso (origem='grupo')
    ↓
    Se usuário JÁ tem acesso:
        → Não sobrescrever (manter origem original)
```

## Fluxo de Certificados

### Emissão Automática

```
Aula marcada como completa
    ↓
Recalcular progresso
    ↓
Progresso >= percentual_conclusao_certificado?
    ↓
    ├─ Não → Apenas atualizar progresso
    └─ Sim ↓
        class-certificates::issue_certificate($user_id, $curso_id)
        ↓
        Verificar se já existe certificado:
        get_user_meta($user_id, "certificado_curso_{$curso_id}")
        ↓
        ├─ Já existe → Não emitir novamente
        └─ Não existe ↓
            Gerar código único:
            $codigo = "CERT-{$curso_id}-{$user_id}-" . time()
            ↓
            Salvar certificado:
            update_user_meta($user_id, "certificado_curso_{$curso_id}", [
                'codigo' => $codigo,
                'data_emissao' => date('Y-m-d H:i:s'),
                'progresso_final' => $progresso
            ])
            ↓
            Retornar $codigo
```

### Visualização de Certificado

```
Usuário acessa [certificado curso_id="456"]
    ↓
Verificar se tem certificado:
get_user_meta($user_id, "certificado_curso_456")
    ↓
    ├─ Não tem → "Você ainda não completou este curso"
    └─ Tem ↓
        Buscar modelo_certificado do curso
        ↓
        Buscar dados do usuário e curso
        ↓
        Renderizar template HTML/CSS
        ↓
        Substituir variáveis:
            {{nome_aluno}}
            {{nome_curso}}
            {{data_conclusao}}
            {{codigo_certificado}}
        ↓
        Exibir com opção de impressão
```

## Dependências entre Módulos

```
sistema-cursos-plugin.php (Core)
    └─ Carrega todas as classes
    
class-access-control.php
    ├─ Depende: Nada (standalone)
    └─ Usado por: Shortcodes, WooCommerce Integration
    
class-course-progress.php
    ├─ Depende: class-access-control (verificar acesso)
    └─ Usado por: Shortcodes, Quiz Process
    
class-certificates.php
    ├─ Depende: class-course-progress (obter progresso)
    └─ Usado por: Shortcode Certificado
    
class-quiz-process.php
    ├─ Depende: class-course-progress (marcar aula completa)
    └─ Usado por: AJAX handlers
    
class-woocommerce-integration.php
    ├─ Depende: class-access-control (conceder acesso)
    └─ Usado por: WooCommerce hooks
```

## Observabilidade

### Logs Disponíveis

**1. Log de Acesso (`wp_acesso_cursos_log`)**
```sql
SELECT * FROM wp_acesso_cursos_log 
WHERE user_id = 123 
ORDER BY data_acao DESC;
```

**2. Log de Tentativas de Quiz (`wp_quiz_attempts`)**
```sql
SELECT * FROM wp_quiz_attempts 
WHERE user_id = 123 AND aula_id = 789 
ORDER BY created_at DESC;
```

**3. WordPress Debug Log**
```php
error_log('LMS: Aula marcada completa - User: ' . $user_id . ', Aula: ' . $aula_id);
```

### Métricas Importantes

- **Taxa de Conclusão:** `(aulas_completas / total_aulas) * 100`
- **Tempo Médio por Aula:** Registrar timestamp ao marcar como completa
- **Taxa de Aprovação em Quiz:** `(tentativas_passed / total_tentativas) * 100`
- **Acessos Simultâneos:** Detectar múltiplos IPs para anti-pirataria

## Modos de Falha

### Falha na Navegação AJAX
```
Erro de rede → Retry automático (JavaScript)
    ↓
Se persistir → Recarregar página manualmente
```

### Falha na Marcação de Aula
```
AJAX timeout → Mostrar erro ao usuário
    ↓
Usuário tenta novamente
    ↓
Backend verifica se já estava marcada (idempotência)
```

### Falha na Emissão de Certificado
```
Erro ao gerar → Logar erro
    ↓
Não bloquear conclusão da aula
    ↓
Admin pode emitir manualmente depois
```

## Recursos Relacionados

- **Arquitetura:** `architecture.md`
- **Estrutura do Código:** `codebase-map.json`
