---
status: filled
generated: 2026-01-18
---

# Estratégia de Testes - LMS SuporteRapido

Este documento descreve a estratégia de testes para o plugin LMS SuporteRapido.

## Visão Geral

O projeto atualmente **não possui testes automatizados** (Jest, PHPUnit, etc.). A qualidade é mantida através de:
1. Testes manuais em ambiente local
2. Validação de sintaxe PHP
3. Verificação de debug.log do WordPress
4. Testes de regressão em funcionalidades críticas

## Ambiente de Testes

### Requisitos
- WordPress 6.x+ instalado localmente
- Plugin ativado em ambiente de desenvolvimento
- `WP_DEBUG = true` no `wp-config.php`
- Dados de teste (cursos, aulas, alunos)

### Configuração de Debug
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
```

## Tipos de Teste

### 1. Validação de Sintaxe PHP
Antes de qualquer alteração, verificar sintaxe:

```bash
# Arquivo individual
php -l includes/class-access-control.php

# Todos os arquivos PHP do plugin
for file in $(find . -name "*.php"); do php -l "$file"; done
```

### 2. Testes Manuais de Funcionalidade

#### Shortcodes
| Shortcode | Onde Testar | O Que Verificar |
|-----------|-------------|-----------------|
| `[meus-cursos]` | Página do aluno | Lista cursos corretamente, carrossel funciona |
| `[lista-aulas]` | Página do curso | Mostra aulas do curso atual |
| `[aula-player]` | Página da aula | Vídeo carrega, botão concluir funciona |
| `[certificado]` | Página de certificado | PDF gerado para cursos 100% |
| `[resultado-busca]` | Página de busca | Filtra apenas trilha/curso/aula |

#### Controle de Acesso
| Cenário | Usuário | Curso | Resultado Esperado |
|---------|---------|-------|-------------------|
| Acesso direto | Aluno A | Curso X (matriculado) | ✅ Acesso permitido |
| Sem matrícula | Aluno A | Curso Y (não matriculado) | ❌ Acesso negado |
| Via grupo | Aluno B (grupo G) | Curso Z (no grupo G) | ✅ Acesso via grupo |
| Expirado | Aluno C | Curso W (data_fim passada) | ❌ Acesso negado |

#### Certificados
| Cenário | Condição | Resultado Esperado |
|---------|----------|-------------------|
| Curso 100% | Progresso completo | Certificado disponível |
| Curso 50% | Progresso parcial | Mensagem "complete para baixar" |
| Template grupo | Aluno em grupo com cert | Usa template do grupo |
| Template curso | Curso com cert específico | Usa template do curso |

### 3. Testes de Regressão

Após qualquer alteração, testar:
- [ ] Login de aluno funciona
- [ ] Lista de cursos exibe corretamente
- [ ] Player de aula carrega vídeo
- [ ] Botão "Concluir Aula" atualiza progresso
- [ ] Barra de progresso reflete conclusões
- [ ] Certificado gera PDF (curso 100%)
- [ ] Painel admin lista alunos
- [ ] Matrícula de aluno funciona

### 4. Testes de JavaScript

#### No Console do Navegador (F12)
```javascript
// Verificar carregamento do script
console.log(typeof sistemaCursosAjax); // Deve mostrar 'object'

// Verificar funções disponíveis
console.log(typeof atualizarBarraProgresso); // 'function'
console.log(typeof atualizarBotaoConcluir); // 'function'
```

#### Erros Comuns
| Erro | Causa | Solução |
|------|-------|---------|
| `sistemaCursosAjax is not defined` | Script não carregado | Verificar `wp_enqueue_script` |
| `html2pdf is not defined` | Biblioteca não carregada | Verificar CDN no certificado |
| `AJAX 400 Bad Request` | Nonce inválido | Verificar `wp_localize_script` |

### 5. Testes de SQL

#### Verificar Tabela de Acesso
```sql
-- Listar matrículas de um aluno
SELECT * FROM wp_acesso_cursos WHERE user_id = 1;

-- Verificar integridade
SELECT ac.*, u.user_login, p.post_title 
FROM wp_acesso_cursos ac
LEFT JOIN wp_users u ON ac.user_id = u.ID
LEFT JOIN wp_posts p ON ac.curso_id = p.ID
WHERE u.ID IS NULL OR p.ID IS NULL;
```

## Procedimento de Teste Pré-Deploy

### Checklist de Qualidade
- [ ] Todas as alterações commitadas
- [ ] `php -l` sem erros em arquivos modificados
- [ ] Debug.log limpo (sem erros do plugin)
- [ ] Console do navegador sem erros JS
- [ ] Shortcodes principais funcionando
- [ ] Fluxo de matrícula → aula → conclusão → certificado OK
- [ ] Versão do plugin atualizada

## Dados de Teste Recomendados

### Criar via Admin
1. **2-3 Trilhas** com nomes distintos
2. **5-10 Cursos** distribuídos nas trilhas
3. **3-5 Aulas por curso** com vídeos de teste
4. **2-3 Grupos** com cursos/trilhas diferentes
5. **5-10 Usuários** como alunos, alguns em grupos
6. **2-3 Templates de certificado**

### Cenários de Acesso
| Aluno | Acessos |
|-------|---------|
| teste1 | Curso A, Curso B (direto) |
| teste2 | Grupo X (que tem Curso C, D) |
| teste3 | Nenhum acesso |
| teste4 | Curso E (expirado), Curso F (ativo) |

## Debugging Avançado

### Log Manual
```php
// Adicionar em pontos críticos
error_log('LMS Debug [' . __FUNCTION__ . ']: ' . print_r($data, true));
```

### Query Monitor (Plugin Recomendado)
Instalar [Query Monitor](https://wordpress.org/plugins/query-monitor/) para:
- Ver todas as queries SQL
- Identificar queries lentas
- Ver hooks executados
- Verificar erros PHP em tempo real

## Futuro: Testes Automatizados

### Roadmap de Testes
1. **PHPUnit** para lógica de acesso (`acesso_cursos_has()`)
2. **WP_Mock** para testes de shortcodes
3. **Cypress** para testes E2E de fluxo completo

### Estrutura Proposta
```
tests/
├── bootstrap.php
├── unit/
│   ├── test-access-control.php
│   └── test-course-progress.php
└── integration/
    └── test-shortcodes.php
```
