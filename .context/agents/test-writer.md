---
name: Test Writer
description: Especialista em testes para o LMS SuporteRapido
status: filled
generated: 2026-01-18
---

# Test Writer Agent Playbook

## Missão
Criar e manter testes automatizados para o plugin LMS SuporteRapido, garantindo qualidade e prevenindo regressões nas funcionalidades de cursos, acesso e certificados.

## Responsabilidades
- Escrever testes unitários para classes PHP
- Criar testes de integração para shortcodes
- Testar fluxos de acesso e matrícula
- Validar geração de certificados
- Manter suite de testes atualizada

## Estado Atual de Testes

⚠️ **O plugin atualmente não possui testes automatizados.**

### Estratégia de Implementação
1. Configurar PHPUnit com WordPress
2. Começar com testes das funções críticas
3. Adicionar testes de integração para shortcodes
4. Implementar testes e2e com Playwright/Cypress

## Configuração PHPUnit

### Estrutura de Diretórios
```
tests/
├── bootstrap.php          # Setup do ambiente de teste
├── Unit/
│   ├── AccessControlTest.php
│   ├── CourseProgressTest.php
│   └── CertificatesTest.php
└── Integration/
    ├── ShortcodeListaAulasTest.php
    └── ShortcodeMeusCursosTest.php
```

### phpunit.xml
```xml
<?xml version="1.0"?>
<phpunit
    bootstrap="tests/bootstrap.php"
    colors="true"
>
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory suffix="Test.php">./tests/Integration</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

### bootstrap.php
```php
<?php
// Carregar WordPress Test Suite
$_tests_dir = getenv('WP_TESTS_DIR') ?: '/tmp/wordpress-tests-lib';

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
    require dirname(__FILE__) . '/../sistema-cursos-plugin.php';
}
tests_add_filter('muplugins_loaded', '_manually_load_plugin');

require $_tests_dir . '/includes/bootstrap.php';
```

## Testes Prioritários

### 1. Verificação de Acesso (CRÍTICO)
```php
<?php
class AccessControlTest extends WP_UnitTestCase {
    
    private $user_id;
    private $curso_id;
    
    public function setUp(): void {
        parent::setUp();
        
        // Criar usuário de teste
        $this->user_id = $this->factory->user->create();
        
        // Criar curso de teste
        $this->curso_id = $this->factory->post->create([
            'post_type' => 'curso',
            'post_title' => 'Curso Teste'
        ]);
    }
    
    public function test_user_without_access_returns_false() {
        $result = acesso_cursos_has($this->user_id, $this->curso_id);
        $this->assertFalse($result);
    }
    
    public function test_user_with_active_access_returns_true() {
        // Inserir acesso
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'acesso_cursos', [
            'user_id' => $this->user_id,
            'curso_id' => $this->curso_id,
            'status' => 'ativo',
            'data_inicio' => current_time('mysql')
        ]);
        
        $result = acesso_cursos_has($this->user_id, $this->curso_id);
        $this->assertTrue($result);
    }
    
    public function test_expired_access_returns_false() {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'acesso_cursos', [
            'user_id' => $this->user_id,
            'curso_id' => $this->curso_id,
            'status' => 'ativo',
            'data_fim' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ]);
        
        $result = acesso_cursos_has($this->user_id, $this->curso_id);
        $this->assertFalse($result);
    }
    
    public function test_suspended_access_returns_false() {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'acesso_cursos', [
            'user_id' => $this->user_id,
            'curso_id' => $this->curso_id,
            'status' => 'suspenso'
        ]);
        
        $result = acesso_cursos_has($this->user_id, $this->curso_id);
        $this->assertFalse($result);
    }
}
```

### 2. Cálculo de Progresso
```php
<?php
class CourseProgressTest extends WP_UnitTestCase {
    
    public function test_progress_with_no_lessons_returns_zero() {
        $curso_id = $this->factory->post->create(['post_type' => 'curso']);
        $user_id = $this->factory->user->create();
        
        $progress = new System_Cursos_Course_Progress();
        $result = $progress->get_progress($user_id, $curso_id);
        
        $this->assertEquals(0, $result);
    }
    
    public function test_progress_calculation() {
        $curso_id = $this->factory->post->create(['post_type' => 'curso']);
        $user_id = $this->factory->user->create();
        
        // Criar 4 aulas
        for ($i = 0; $i < 4; $i++) {
            $aula_id = $this->factory->post->create(['post_type' => 'aula']);
            update_post_meta($aula_id, 'curso', $curso_id);
        }
        
        // Marcar 2 como concluídas
        update_user_meta($user_id, "aulas_concluidas_$curso_id", [1, 2]);
        
        $progress = new System_Cursos_Course_Progress();
        $result = $progress->get_progress($user_id, $curso_id);
        
        $this->assertEquals(50, $result);
    }
}
```

### 3. Shortcode Output
```php
<?php
class ShortcodeListaAulasTest extends WP_UnitTestCase {
    
    public function test_shortcode_renders_for_logged_user_with_access() {
        $user_id = $this->factory->user->create();
        wp_set_current_user($user_id);
        
        $curso_id = $this->factory->post->create(['post_type' => 'curso']);
        
        // Dar acesso
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'acesso_cursos', [
            'user_id' => $user_id,
            'curso_id' => $curso_id,
            'status' => 'ativo'
        ]);
        
        $output = do_shortcode("[lista-aulas curso=\"$curso_id\"]");
        
        $this->assertStringContainsString('class="lista-aulas"', $output);
    }
    
    public function test_shortcode_shows_login_message_for_guest() {
        wp_set_current_user(0);
        
        $output = do_shortcode('[lista-aulas curso="1"]');
        
        $this->assertStringContainsString('login', strtolower($output));
    }
}
```

## Testes Manuais (Checklist)

### Shortcodes
- [ ] `[lista-aulas]` exibe aulas do curso
- [ ] `[meus-cursos]` exibe cursos do usuário
- [ ] `[meus-cursos mostrar_todos="sim"]` exibe todos os cursos
- [ ] `[certificado]` gera PDF para curso concluído
- [ ] `[barra-progresso]` mostra percentual correto
- [ ] `[resultado-busca]` filtra apenas CPTs do plugin

### Admin
- [ ] Criar trilha funciona
- [ ] Criar curso com trilha funciona
- [ ] Criar aula com curso funciona
- [ ] Matricular aluno funciona
- [ ] Listar alunos funciona
- [ ] Detalhes do aluno carrega

### Acesso
- [ ] Usuário sem acesso vê mensagem de bloqueio
- [ ] Usuário com acesso vê conteúdo
- [ ] Acesso via grupo funciona
- [ ] Acesso expirado é bloqueado

## Ferramentas Recomendadas

| Ferramenta | Uso |
|------------|-----|
| PHPUnit | Testes unitários PHP |
| WP_UnitTestCase | Testes WordPress |
| Brain Monkey | Mock de funções WP |
| Playwright | Testes e2e |
| Query Monitor | Debug de queries |

## Documentação de Referência
- [Arquitetura](../docs/architecture.md)
- [PHPUnit + WordPress](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/)

## Checklist de Testes

- [ ] PHPUnit configurado
- [ ] Testes de acesso criados
- [ ] Testes de progresso criados
- [ ] Testes de shortcodes básicos
- [ ] CI/CD configurado (GitHub Actions)
- [ ] Coverage report gerado
