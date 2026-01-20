---
status: in_progress
phase: V (Verificação)
generated: 2026-01-20
last_updated: 2026-01-20
agents:
  - type: "bug-fixer"
    role: "Analisar e corrigir o bug de exibição de grupos fantasma"
  - type: "backend-specialist"
    role: "Implementar validação de dados e limpeza de registros órfãos"
  - type: "test-writer"
    role: "Criar testes para validar a correção"
  - type: "code-reviewer"
    role: "Revisar alterações no código de controle de acesso"
---

# Plano: Correção de Bug - Acesso via Grupo Fantasma

## Resumo Executivo

### Problema Identificado
Na página de detalhes do aluno (`admin.php?page=acesso-cursos-alunos&action=view`), alguns alunos que **não pertencem a nenhum grupo** estão exibindo informações incorretas na tabela "Cursos e Permissões":

- **Coluna Status**: Mostra "Utilizando Grupo:" seguido de texto vazio
- **Coluna Expiração**: Mostra "Gerenciado pelo Grupo"
- **Coluna Ações Rápidas**: Mostra "Acesso via grupo. Edite o grupo ou remova o aluno dele."

Porém:
- O aluno não está em nenhum grupo (meta `_aluno_grupos` vazia ou inexistente)
- Na tabela "Lista de Alunos", a coluna "Cursos Ativos" mostra "Nenhum"
- Não há acesso real ao curso

### Causa Raiz
O método `get_access_source()` em `class-access-control.php` está retornando informações de grupo mesmo quando:
1. O grupo foi deletado mas ainda está referenciado em `_grupos_permitidos` do curso
2. O aluno foi removido do grupo mas o curso ainda tem o grupo na lista
3. Existe inconsistência entre os metadados do usuário e do curso

### Impacto
- **Severidade**: Média
- **Usuários Afetados**: Alunos que foram removidos de grupos ou cujos grupos foram deletados
- **Impacto Visual**: Informações confusas e incorretas na interface administrativa
- **Impacto Funcional**: Administradores podem tomar decisões erradas sobre permissões

### Solução Proposta
Implementar validação robusta no método `get_access_source()` para verificar:
1. Se o grupo ainda existe
2. Se o aluno realmente pertence ao grupo
3. Adicionar função de limpeza de dados órfãos

---

## Fase 1: Planejamento e Descoberta (P)

**Objetivo**: Entender completamente o bug, mapear todos os cenários problemáticos e definir critérios de sucesso.

**Agente Principal**: `bug-fixer`

### Passos

#### 1.1 Análise Detalhada do Código
- [ ] Revisar método `get_access_source()` (linhas 77-114 de `class-access-control.php`)
- [ ] Revisar método `render_details_page()` (linhas 752-1293)
- [ ] Identificar todos os pontos onde `get_access_source()` é chamado
- [ ] Mapear fluxo de dados: user_meta → curso_meta → exibição

**Artefatos**:
- Diagrama de fluxo do método `get_access_source()`
- Lista de todos os cenários de falha identificados

#### 1.2 Reprodução do Bug
- [ ] Criar cenário de teste 1: Grupo deletado mas ainda em `_grupos_permitidos`
- [ ] Criar cenário de teste 2: Aluno removido do grupo
- [ ] Criar cenário de teste 3: Meta `_grupos_permitidos` com IDs inválidos
- [ ] Documentar comportamento atual vs. esperado

**Artefatos**:
- Screenshots do bug
- Dados de teste (IDs de usuários, cursos e grupos afetados)

#### 1.3 Análise de Impacto
- [ ] Verificar quantos registros estão afetados no banco de dados
- [ ] Identificar se há outros métodos que dependem de `get_access_source()`
- [ ] Avaliar necessidade de migração de dados

**Query SQL para análise**:
```sql
-- Encontrar cursos com grupos inexistentes em _grupos_permitidos
SELECT p.post_id, p.meta_value 
FROM wp_postmeta p 
WHERE p.meta_key = '_grupos_permitidos' 
AND p.meta_value LIKE '%"%'
```

#### 1.4 Definição de Critérios de Sucesso
- [ ] Bug não aparece mais para alunos sem grupos
- [ ] Informações corretas exibidas para alunos com grupos válidos
- [ ] Performance não degradada
- [ ] Nenhum registro órfão no banco de dados

**Commit Checkpoint**:
```bash
git commit -m "docs(plan): complete phase 1 - bug analysis and reproduction"
```

---

## Fase 2: Revisão e Design (R)

**Objetivo**: Projetar a solução técnica e obter aprovação da abordagem.

**Agente Principal**: `backend-specialist`

### Passos

#### 2.1 Design da Solução

**Alteração 1: Validação no `get_access_source()`**

Adicionar validações antes de retornar informações de grupo:

```php
// 2a. Grupos no Curso
$curso_grupos = get_post_meta($curso_id, '_grupos_permitidos', true);
if (is_array($curso_grupos)) {
    // NOVA VALIDAÇÃO: Filtrar grupos que não existem mais
    $curso_grupos = array_filter($curso_grupos, function($grupo_id) {
        return get_post_status($grupo_id) !== false;
    });
    
    $intersect = array_intersect($user_grupos, $curso_grupos);
    if (!empty($intersect)) {
        $g_id = reset($intersect);
        
        // NOVA VALIDAÇÃO: Verificar se o grupo ainda existe
        if (get_post_status($g_id) === false) {
            continue; // Grupo foi deletado
        }
        
        return [
            'type' => 'group', 
            'label' => 'Grupo: ' . get_the_title($g_id), 
            'group_id' => $g_id
        ];
    }
}
```

**Alteração 2: Função de Limpeza de Dados Órfãos**

Criar método `cleanup_orphaned_group_references()`:

```php
public static function cleanup_orphaned_group_references() {
    global $wpdb;
    
    $cleaned = [
        'cursos' => 0,
        'trilhas' => 0,
        'usuarios' => 0
    ];
    
    // 1. Limpar grupos inexistentes de cursos
    $cursos = get_posts([
        'post_type' => 'curso',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ]);
    
    foreach ($cursos as $curso_id) {
        $grupos = get_post_meta($curso_id, '_grupos_permitidos', true);
        if (is_array($grupos)) {
            $grupos_validos = array_filter($grupos, function($g_id) {
                return get_post_status($g_id) !== false;
            });
            
            if (count($grupos_validos) !== count($grupos)) {
                update_post_meta($curso_id, '_grupos_permitidos', $grupos_validos);
                $cleaned['cursos']++;
            }
        }
    }
    
    // 2. Limpar grupos inexistentes de trilhas
    $trilhas = get_posts([
        'post_type' => 'trilha',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ]);
    
    foreach ($trilhas as $trilha_id) {
        $grupos = get_post_meta($trilha_id, '_grupos_permitidos', true);
        if (is_array($grupos)) {
            $grupos_validos = array_filter($grupos, function($g_id) {
                return get_post_status($g_id) !== false;
            });
            
            if (count($grupos_validos) !== count($grupos)) {
                update_post_meta($trilha_id, '_grupos_permitidos', $grupos_validos);
                $cleaned['trilhas']++;
            }
        }
    }
    
    // 3. Limpar grupos inexistentes de usuários
    $users = get_users(['fields' => 'ID']);
    foreach ($users as $user_id) {
        $grupos = get_user_meta($user_id, '_aluno_grupos', true);
        if (is_array($grupos)) {
            $grupos_validos = array_filter($grupos, function($g_id) {
                return get_post_status($g_id) !== false;
            });
            
            if (count($grupos_validos) !== count($grupos)) {
                update_user_meta($user_id, '_aluno_grupos', $grupos_validos);
                $cleaned['usuarios']++;
            }
        }
    }
    
    return $cleaned;
}
```

**Alteração 3: Adicionar WP-CLI Command**

Criar comando para executar limpeza via terminal:

```php
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('cursos cleanup-groups', function() {
        $result = System_Cursos_Access_Control::cleanup_orphaned_group_references();
        WP_CLI::success(sprintf(
            'Limpeza concluída: %d cursos, %d trilhas, %d usuários atualizados',
            $result['cursos'],
            $result['trilhas'],
            $result['usuarios']
        ));
    });
}
```

#### 2.2 Revisão de Segurança
- [ ] Verificar se a limpeza não remove dados válidos
- [ ] Adicionar logs de auditoria
- [ ] Implementar dry-run mode para testes

#### 2.3 Aprovação do Design
- [ ] Revisar com code-reviewer
- [ ] Validar abordagem com stakeholders
- [ ] Documentar decisões técnicas

**Commit Checkpoint**:
```bash
git commit -m "docs(plan): complete phase 2 - solution design approved"
```

---

## Fase 3: Execução (E)

**Objetivo**: Implementar a correção do bug.

**Agente Principal**: `backend-specialist`

### Passos

#### 3.1 Implementar Validações no `get_access_source()`

**Arquivo**: `includes/class-access-control.php`

- [ ] Adicionar validação de existência de grupo na seção 2a (linhas 90-98)
- [ ] Adicionar validação de existência de grupo na seção 2b (linhas 100-111)
- [ ] Adicionar filtro para remover grupos inexistentes antes da interseção
- [ ] Atualizar versão do plugin no header

**Código a modificar**:
```php
// Linha 91-98: Validação para grupos no curso
$curso_grupos = get_post_meta($curso_id, '_grupos_permitidos', true);
if (is_array($curso_grupos) && !empty($curso_grupos)) {
    // Filtrar grupos que não existem mais
    $curso_grupos_validos = array_filter($curso_grupos, function($grupo_id) {
        return get_post_status($grupo_id) !== false;
    });
    
    if (empty($curso_grupos_validos)) {
        // Todos os grupos são inválidos, pular esta verificação
    } else {
        $intersect = array_intersect($user_grupos, $curso_grupos_validos);
        if (!empty($intersect)) {
            $g_id = reset($intersect);
            return [
                'type' => 'group', 
                'label' => 'Grupo: ' . get_the_title($g_id), 
                'group_id' => $g_id
            ];
        }
    }
}
```

#### 3.2 Implementar Função de Limpeza

**Arquivo**: `includes/class-access-control.php`

- [ ] Adicionar método `cleanup_orphaned_group_references()` após linha 114
- [ ] Adicionar método `cleanup_user_orphaned_groups($user_id)` para limpeza individual
- [ ] Adicionar logs com `error_log()` para auditoria

#### 3.3 Adicionar Interface Admin para Limpeza

**Arquivo**: `includes/class-access-control.php`

- [ ] Adicionar botão "Limpar Referências Órfãs" na página de lista de alunos
- [ ] Criar handler para processar a limpeza via POST
- [ ] Exibir mensagem de sucesso com estatísticas

**Código a adicionar** (após linha 619):
```php
<div style="margin: 20px 0; padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px;">
    <h3 style="margin-top: 0;">🔧 Manutenção do Sistema</h3>
    <p>Limpe referências a grupos que foram deletados mas ainda estão associados a cursos, trilhas ou alunos.</p>
    <form method="post" style="display: inline;">
        <?php wp_nonce_field('cleanup_orphaned_groups'); ?>
        <button type="submit" name="cleanup_orphaned_groups" value="1" class="button" 
                onclick="return confirm('Deseja realmente limpar as referências órfãs? Esta ação não pode ser desfeita.');">
            Executar Limpeza de Grupos Órfãos
        </button>
    </form>
</div>
```

#### 3.4 Processar Ação de Limpeza

**Arquivo**: `includes/class-access-control.php`

Adicionar no método `handle_admin_actions()` (linha 420):

```php
// Limpeza de grupos órfãos
if (isset($_POST['cleanup_orphaned_groups']) && check_admin_referer('cleanup_orphaned_groups')) {
    $result = self::cleanup_orphaned_group_references();
    
    $message = sprintf(
        'Limpeza concluída com sucesso! %d cursos, %d trilhas e %d usuários foram atualizados.',
        $result['cursos'],
        $result['trilhas'],
        $result['usuarios']
    );
    
    wp_redirect(add_query_arg([
        'page' => 'acesso-cursos-alunos',
        'msg' => 'cleanup_success',
        'stats' => base64_encode(json_encode($result))
    ], admin_url('admin.php')));
    exit;
}
```

#### 3.5 Atualizar Versão do Plugin

**Arquivo**: `sistema-cursos-plugin.php`

- [ ] Incrementar versão no header (ex: 1.0.0 → 1.0.1)
- [ ] Adicionar entrada no changelog

**Commit Checkpoint**:
```bash
git commit -m "fix(access-control): corrigir bug de grupo fantasma e adicionar limpeza de dados órfãos

- Adicionar validação de existência de grupo em get_access_source()
- Implementar cleanup_orphaned_group_references() para limpeza automática
- Adicionar interface admin para executar limpeza manual
- Filtrar grupos deletados antes de verificar interseção
- Incrementar versão do plugin para 1.0.1

Fixes: Bug onde alunos sem grupos exibiam 'Utilizando Grupo:' vazio"
```

---

## Fase 4: Verificação (V)

**Objetivo**: Validar que a correção funciona corretamente.

**Agente Principal**: `test-writer`

### Passos

#### 4.1 Testes Manuais

**Cenário 1: Aluno sem grupos**
- [ ] Acessar detalhes de aluno sem grupos
- [ ] Verificar que coluna Status mostra "Sem acesso" (não "Utilizando Grupo:")
- [ ] Verificar que coluna Expiração mostra "—" (não "Gerenciado pelo Grupo")
- [ ] Verificar que ações rápidas mostram "Conceder Acesso"

**Cenário 2: Aluno com grupo válido**
- [ ] Acessar detalhes de aluno em grupo ativo
- [ ] Verificar que coluna Status mostra "Utilizando Grupo: [Nome do Grupo]"
- [ ] Verificar que coluna Expiração mostra "Gerenciado pelo Grupo"
- [ ] Verificar que ações rápidas mostram mensagem de grupo

**Cenário 3: Grupo deletado**
- [ ] Criar grupo, associar a curso e aluno
- [ ] Deletar o grupo
- [ ] Verificar que aluno não mostra mais acesso via grupo
- [ ] Executar limpeza manual
- [ ] Verificar que referências foram removidas

**Cenário 4: Limpeza em massa**
- [ ] Executar "Limpar Referências Órfãs"
- [ ] Verificar mensagem de sucesso com estatísticas
- [ ] Confirmar que dados válidos não foram afetados

#### 4.2 Testes de Performance

- [ ] Medir tempo de carregamento da página de detalhes antes e depois
- [ ] Verificar número de queries SQL (não deve aumentar significativamente)
- [ ] Testar com 100+ alunos e 50+ cursos

**Benchmark esperado**:
- Tempo de carregamento: < 2 segundos
- Queries adicionais: máximo +2 por curso exibido

#### 4.3 Testes de Regressão

- [ ] Verificar que acesso direto (não via grupo) ainda funciona
- [ ] Verificar que suspensão/revogação ainda funciona
- [ ] Verificar que concessão de acesso manual ainda funciona
- [ ] Verificar que matrícula em trilha ainda funciona

#### 4.4 Validação de Dados

**Query SQL para validação**:
```sql
-- Verificar se ainda existem referências órfãs
SELECT COUNT(*) as total_orfaos
FROM wp_postmeta pm
WHERE pm.meta_key = '_grupos_permitidos'
AND pm.meta_value REGEXP '[0-9]+'
AND NOT EXISTS (
    SELECT 1 FROM wp_posts p 
    WHERE p.ID = CAST(pm.meta_value AS UNSIGNED)
    AND p.post_type = 'grupo'
    AND p.post_status != 'trash'
);
```

Resultado esperado: `total_orfaos = 0`

**Commit Checkpoint**:
```bash
git commit -m "test(access-control): validar correção de bug de grupo fantasma"
```

---

## Fase 5: Conclusão (C)

**Objetivo**: Documentar a correção e preparar para produção.

**Agente Principal**: `documentation-writer`

### Passos

#### 5.1 Atualizar Documentação

**Arquivo**: `documentacao.txt`

- [ ] Adicionar seção sobre limpeza de grupos órfãos
- [ ] Documentar comando WP-CLI (se implementado)
- [ ] Atualizar troubleshooting com este bug

**Conteúdo a adicionar**:
```
## Manutenção: Limpeza de Grupos Órfãos

### Problema
Quando grupos são deletados, suas referências podem permanecer em:
- Meta `_grupos_permitidos` de cursos
- Meta `_grupos_permitidos` de trilhas  
- Meta `_aluno_grupos` de usuários

### Solução
Execute a limpeza manual via admin:
1. Acesse "Alunos" no menu
2. Clique em "Executar Limpeza de Grupos Órfãos"
3. Confirme a ação

Ou via WP-CLI:
```bash
wp cursos cleanup-groups
```

### Frequência Recomendada
- Após deletar grupos em massa
- Mensalmente como manutenção preventiva
```

#### 5.2 Criar Changelog

**Arquivo**: `CHANGELOG.md` (criar se não existir)

```markdown
# Changelog

## [1.0.1] - 2026-01-20

### Corrigido
- Bug onde alunos sem grupos exibiam "Utilizando Grupo:" vazio na página de detalhes
- Referências órfãs a grupos deletados não eram removidas automaticamente

### Adicionado
- Função `cleanup_orphaned_group_references()` para limpeza de dados órfãos
- Interface admin para executar limpeza manual de grupos órfãos
- Validação de existência de grupo antes de exibir informações de acesso

### Melhorado
- Método `get_access_source()` agora valida se grupos ainda existem antes de retornar
- Performance da página de detalhes do aluno
```

#### 5.3 Preparar Deploy

- [ ] Criar backup do banco de dados
- [ ] Testar em ambiente de staging
- [ ] Preparar rollback plan
- [ ] Notificar administradores sobre nova funcionalidade de limpeza

#### 5.4 Monitoramento Pós-Deploy

**Métricas a monitorar**:
- Número de erros 500 na página de detalhes do aluno
- Tempo de carregamento médio
- Feedback de usuários sobre informações incorretas

**Período de monitoramento**: 7 dias

**Commit Checkpoint**:
```bash
git commit -m "docs(changelog): adicionar entrada para v1.0.1 - correção de bug de grupo fantasma"
git tag -a v1.0.1 -m "Release v1.0.1: Correção de bug de grupo fantasma"
```

---

## Plano de Rollback

### Gatilhos para Rollback
- Erros críticos na página de detalhes do aluno
- Perda de dados de grupos válidos
- Degradação de performance > 50%
- Mais de 5 relatos de bugs relacionados em 24h

### Procedimento de Rollback

#### Rollback da Fase 3 (Código)
**Ação**: Reverter commits e restaurar versão anterior

```bash
# 1. Reverter commits
git revert HEAD~3..HEAD

# 2. Fazer deploy da versão anterior
git checkout v1.0.0
```

**Impacto de Dados**: Nenhum (apenas código)  
**Tempo Estimado**: < 30 minutos

#### Rollback da Limpeza de Dados
**Ação**: Restaurar backup do banco de dados

```bash
# 1. Restaurar backup
mysql -u usuario -p nome_banco < backup_pre_limpeza.sql

# 2. Verificar integridade
wp db check
```

**Impacto de Dados**: Referências órfãs voltam a existir  
**Tempo Estimado**: 1-2 horas (dependendo do tamanho do banco)

### Ações Pós-Rollback
1. Documentar motivo do rollback em incident report
2. Notificar stakeholders sobre rollback e impacto
3. Agendar post-mortem para analisar falha (48h após rollback)
4. Atualizar plano com lições aprendidas antes de nova tentativa
5. Criar testes adicionais para cenário que causou rollback

---

## Evidências e Acompanhamento

### Artefatos a Coletar

**Fase 1 (Planejamento)**:
- [ ] Screenshots do bug reproduzido
- [ ] Dump SQL de dados de teste
- [ ] Diagrama de fluxo do `get_access_source()`

**Fase 2 (Revisão)**:
- [ ] Documento de design da solução
- [ ] Aprovação do code-reviewer
- [ ] Análise de impacto de performance

**Fase 3 (Execução)**:
- [ ] Pull Request com código implementado
- [ ] Logs de execução da limpeza em staging
- [ ] Diff do código alterado

**Fase 4 (Verificação)**:
- [ ] Relatório de testes manuais
- [ ] Resultados de queries de validação
- [ ] Screenshots de antes/depois

**Fase 5 (Conclusão)**:
- [ ] Documentação atualizada
- [ ] Changelog publicado
- [ ] Métricas de monitoramento (7 dias)

### Ações de Acompanhamento

| Ação | Responsável | Prazo | Status |
|------|-------------|-------|--------|
| Implementar WP-CLI command | Backend Specialist | Fase 3 | Pendente |
| Criar testes automatizados | Test Writer | Fase 4 | Pendente |
| Monitorar métricas pós-deploy | DevOps | 7 dias após deploy | Pendente |
| Post-mortem (se necessário) | Bug Fixer | 48h após rollback | N/A |

### Métricas de Sucesso

**Quantitativas**:
- ✅ 0 ocorrências do bug após correção
- ✅ 100% dos grupos órfãos removidos após limpeza
- ✅ Tempo de carregamento < 2s
- ✅ 0 erros 500 relacionados em 7 dias

**Qualitativas**:
- ✅ Administradores relatam informações corretas
- ✅ Interface mais clara e confiável
- ✅ Confiança restaurada no sistema de grupos

---

## Notas Adicionais

### Considerações Técnicas

1. **Compatibilidade**: Esta correção é compatível com WordPress 5.0+
2. **Dependências**: Nenhuma dependência externa adicionada
3. **Performance**: Impacto mínimo, apenas 1-2 queries adicionais por verificação
4. **Segurança**: Função de limpeza protegida por nonce e capabilities

### Melhorias Futuras

1. **Automação**: Executar limpeza automaticamente via cron job semanal
2. **Alertas**: Notificar admin quando grupos órfãos são detectados
3. **Auditoria**: Log detalhado de todas as limpezas executadas
4. **Prevenção**: Hook para limpar referências automaticamente ao deletar grupo

### Referências

- Arquivo principal: `includes/class-access-control.php`
- Método afetado: `get_access_source()` (linhas 77-114)
- Página admin: `render_details_page()` (linhas 752-1293)
- Issue tracking: [Link para issue, se houver]
