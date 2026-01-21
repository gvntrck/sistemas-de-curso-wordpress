# Estratégia de Testes

## Visão Geral

O LMS SuporteRapido atualmente usa **testes manuais** devido à natureza do projeto (plugin WordPress). Esta estratégia documenta os testes essenciais a serem realizados antes de cada release.

##  Abordagem de Teste

### 1. Testes Manuais (Atual)
- Testes funcionais no ambiente local
- Validação de fluxos críticos
- Verificação de regressão em funcionalidades existentes

### 2. Testes Automatizados (Futuro)
- PHPUnit para lógica de negócio
- Selenium/Cypress para fluxos de usuário
- Jest para JavaScript

## Categorias de Teste

### 🔐 Testes de Acesso e Permissões

#### TC-001: Concessão de Acesso Manual
```
Pré-condição: Admin logado, usuário teste existe
Passos:
1. Ir para "LMS → Alunos"
2. Clicar em "Detalhes" do aluno teste
3. Na seção "Cursos e Permissões", clicar "Conceder Acesso"
4. Selecionar um curso
5. Definir validade (vitalício ou data)
6. Salvar

Resultado Esperado:
- Mensagem de sucesso exibida
- Curso aparece na lista de "Cursos e Permissões"
- Log registrado em "Histórico de Matrículas"
- Aluno consegue acessar o curso no frontend
```

#### TC-002: Verificação de Expiração de Acesso
```
Pré-condição: Usuário com acesso que expira em data passada
Passos:
1. Login como usuário teste
2. Acessar página do curso expirado

Resultado Esperado:
- Mensagem "Seu acesso a este curso expirou"
- Curso não aparece em [meus-cursos]
- Não consegue acessar aulas
```

#### TC-003: Acesso via Grupo
```
Pré-condição: Grupo criado com cursos permitidos
Passos:
1. Adicionar usuário ao grupo
2. Verificar se recebeu acesso aos cursos do grupo
3. Remover usuário do grupo
4. Verificar se perdeu acesso (se origem = grupo)

Resultado Esperado:
- Ao entrar no grupo: acesso concedido
- Log mostra "entrou_grupo"
- Ao sair: acessos com origem="grupo" removidos
- Acessos manuais/woocommerce permanecem
```

### 📚 Testes de Navegação e Progresso

#### TC-004: Navegação AJAX entre Aulas
```
Pré-condição: Usuário com acesso, curso com múltiplas aulas
Passos:
1. Login e acessar curso
2. Clicar em aula na sidebar
3. Verificar se vídeo troca sem reload
4. Navegar entre 5+ aulas rapidamente

Resultado Esperado:
- Vídeo troca sem recarregar página
- Título e descrição atualizam
- Aula ativa destacada na sidebar
- Sem erros no console
- Layout mantém-se correto
```

#### TC-005: Marcar Aula como Completa
```
Pré-condição: Usuário em aula não completa
Passos:
1. Clicar em "Marcar como Completa"
2. Verificar feedback visual
3. Atualizar página
4. Verificar se permanece marcada

Resultado Esperado:
- Botão muda para "✓ Completa"
- Barra de progresso atualiza
- Percentual atualizado em tempo real
- Estado persiste após reload
```

#### TC-006: Cálculo de Progresso
```
Pré-condição: Curso com 10 aulas
Passos:
1. Marcar 5 aulas como completas
2. Verificar progresso em [meus-cursos]
3. Verificar progresso na página do curso

Resultado Esperado:
- Progresso exibe 50% (5/10)
- Barra visual reflete percentual
- Progresso consistente em todas as páginas
```

### 🎓 Testes de Certificados

#### TC-007: Emissão Automática de Certificado
```
Pré-condição: Curso com percentual_conclusao = 80%
Passos:
1. Completar 8 de 10 aulas (80%)
2. Ao marcar 8ª aula, verificar notificação
3. Acessar [certificado curso_id="X"]

Resultado Esperado:
- Notificação "Certificado emitido!" aparece
- Certificado disponível para visualização
- Código único gerado
- Data de emissão correta
```

#### TC-008: Impressão de Certificado
```
Pré-condição: Certificado emitido
Passos:
1. Acessar certificado
2. Clicar "Imprimir Certificado"
3. Verificar preview de impressão

Resultado Esperado:
- Dialog de impressão abre
- Layout otimizado para A4 paisagem
- Todos os dados preenchidos corretamente
```

### ❓ Testes de Quiz

#### TC-009: Responder Quiz com Sucesso
```
Pré-condição: Aula com quiz, 3 perguntas, aprovação = 60%
Passos:
1. Responder 3 de 3 corretas
2. Submeter quiz

Resultado Esperado:
- Mensagem "Parabéns! Você passou!"
- Pontuação exibida (3/3)
- Aula marcada como completa automaticamente
- Tentativa registrada em wp_quiz_attempts
```

#### TC-010: Falhar em Quiz
```
Pré-condição: Aula com quiz, max 3 tentativas
Passos:
1. Responder todas erradas
2. Submeter quiz
3. Verificar feedback

Resultado Esperado:
- Mensagem "Você não atingiu a nota mínima"
- Pontuação exibida (ex: 0/3)
- "Tentativas restantes: 2"
- Aula NÃO marcada como completa
```

#### TC-011: Esgotamento de Tentativas
```
Pré-condição: 2 tentativas já usadas, 1 restante
Passos:
1. Falhar na 3ª tentativa
2. Verificar comportamento

Resultado Esperado:
- Mensagem "Sem tentativas restantes"
- Quiz desabilitado
- Opção "Solicitar nova tentativa" (futuro) ou contato admin
```

### 🛒 Testes de Integração WooCommerce

#### TC-012: Compra de Produto Vinculado a Curso
```
Pré-condição: WooCommerce ativo, produto vinculado a curso_id=123
Passos:
1. Adicionar produto ao carrinho
2. Finalizar compra (status: Completed)
3. Verificar acesso do usuário

Resultado Esperado:
- Acesso concedido automaticamente
- Origem = 'woocommerce'
- Validade conforme configurado no produto
- Log registrado
```

#### TC-013: Produto com Trilha Vinculada
```
Pré-condição: Produto vinculado a trilha com 3 cursos
Passos:
1. Comprar produto
2. Verificar acessos concedidos

Resultado Esperado:
- Acesso concedido à trilha
- Acesso concedido aos 3 cursos da trilha
- Origem = 'woocommerce' para todos
```

### 👤 Testes de Usuário e Perfil

####TC-014: Cadastro de Novo Usuário
```
Pré-condição: Admin em [cadastro-usuario]
Passos:
1. Preencher formulário (nome, email, CPF, etc)
2. Submeter
3. Verificar criação

Resultado Esperado:
- Usuário criado com role 'aluno'
- Email de boas-vindas enviado
- Dados salvos corretamente em user_meta
```

#### TC-015: Edição de Perfil pelo Aluno
```
Pré-condição: Aluno logado em [minha-conta]
Passos:
1. Alterar endereço
2. Salvar
3. Recarregar página

Resultado Esperado:
- Mensagem de sucesso
- Dados atualizados no banco
- Campos preenchidos com novos valores
```

#### TC-016: Restrição de Acesso ao Admin (Role Aluno)
```
Pré-condição: Usuário com role 'aluno'
Passos:
1. Tentar acessar /wp-admin/
2. Verificar redirecionamento

Resultado Esperado:
- Redirecionado para home
- Barra de admin não aparece
- Não consegue acessar painel
```

### 🔍 Testes de Busca e Listagem

#### TC-017: Busca de Aulas
```
Pré-condição: Multiple aulas cadastradas
Passos:
1. Usar busca do site com termo relacionado a aula
2. Acessar [resultado-busca]

Resultado Esperado:
- Aulas correspondentes exibidas
- Link para aula funciona (redireciona para curso)
```

#### TC-018: Listagem em [meus-cursos]
```
Pré-condição: Usuário com 5 cursos
Passos:
1. Acessar página com [meus-cursos]

Resultado Esperado:
- Todos os 5 cursos listados
- Percentual de progresso correto para cada
- Cards clicáveis levam ao curso
```

## Teste de Regressão

Após qualquer mudança significativa, executar:

### Checklist de Regressão Rápida (15 min)
- [ ] Login funciona
- [ ] [meus-cursos] renderiza
- [ ] Navegação entre aulas funciona
- [ ] Marcar aula como completa funciona
- [ ] Quiz submete corretamente
- [ ] Progresso atualiza
- [ ] Certificado aparece quando deveria
- [ ] Concessão manual de acesso funciona
- [ ] Shortcodes principais renderizam sem erro

## Testes de Performance

### Carga de Dados
```
Criar cenário de teste:
- 100 usuários
- 50 cursos
- 500 aulas
- Verificar tempos de resposta
```

### Consultas Otimizadas
```
Monitorar queries lentas:
define('SAVEQUERIES', true);

No footer:
global $wpdb;
echo "<pre>";
print_r($wpdb->queries);
echo "</pre>";
```

## Testes de Segurança

### Checklist de Segurança
- [ ] Tentativa de AJAX sem nonce bloqueia
- [ ] Aluno não consegue acessar /wp-admin/
- [ ] SQL injection (tentar ' OR '1'='1 em inputs)
- [ ] XSS (tentar <script>alert('XSS')</script> em inputs)
- [ ] CSRF (submeter form sem nonce)

## Testes de Compatibilidade

### Browsers
- [ ] Chrome (última versão)
- [ ] Firefox (última versão)
- [ ] Safari
- [ ] Edge
- [ ] Mobile (Chrome Android, Safari iOS)

### WordPress
- [ ] WordPress 5.8+
- [ ] WordPress 6.0+
- [ ] Última versão estável

### PHP
- [ ] PHP 7.4
- [ ] PHP 8.0
- [ ] PHP 8.1+

## Documentação de Bugs

### Template de Bug Report
```
**Título:** [Componente] Descrição curta

**Severidade:** Crítico | Alto | Médio | Baixo

**Ambiente:**
- WordPress: 6.0
- PHP: 8.0
- Navegador: Chrome 120

**Passos para Reproduzir:**
1. Passo 1
2. Passo 2
3. Passo 3

**Resultado Atual:**
O que acontece

**Resultado Esperado:**
O que deveria acontecer

**Screenshots:**
(anexar se aplicável)

**Logs:**
(copiar erro do console/PHP)
```

## Automação Futura

### PHPUnit (Backend)
```php
class Test_Course_Progress extends WP_UnitTestCase {
    public function test_mark_lesson_complete() {
        $user_id = $this->factory->user->create();
        $lesson_id = 123;
        
        $progress = new System_Cursos_Progress();
        $result = $progress->mark_lesson_complete($user_id, $lesson_id);
        
        $this->assertTrue($result);
        // Mais assertions...
    }
}
```

### Cypress (Frontend)
```javascript
describe('Navegação de Aulas', () => {
    it('deve trocar de aula via AJAX', () => {
        cy.login('aluno', 'senha');
        cy.visit('/curso/php-basico');
        cy.get('.aula-item').eq(1).click();
        cy.get('.video-titulo').should('contain', 'Aula 2');
    });
});
```

## Recursos Relacionados

- **Desenvolvimento:** `development-workflow.md`
- **Segurança:** `security.md`
