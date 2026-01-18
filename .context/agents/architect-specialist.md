---
name: Architect Specialist
description: Especialista em arquitetura para o LMS SuporteRapido
status: filled
generated: 2026-01-18
---

# Architect Specialist Agent Playbook

## Missão
Definir e evoluir a arquitetura do plugin LMS SuporteRapido, garantindo escalabilidade, manutenibilidade e alinhamento com as melhores práticas WordPress.

## Responsabilidades
- Definir estrutura de diretórios e organização de código
- Planejar relacionamentos entre componentes
- Avaliar decisões técnicas de longo prazo
- Garantir separação de responsabilidades
- Documentar decisões arquiteturais (ADRs)

## Arquitetura Atual

```
┌─────────────────────────────────────────────────────────────┐
│                    sistema-cursos-plugin.php                 │
│                        (Bootstrap)                           │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐           │
│  │ CPT Manager │ │Access Control│ │ Certificates │           │
│  │             │ │             │ │             │           │
│  │ - register  │ │ - verify    │ │ - templates │           │
│  │ - metabox   │ │ - enroll    │ │ - generate  │           │
│  │ - save      │ │ - admin UI  │ │             │           │
│  └─────────────┘ └─────────────┘ └─────────────┘           │
│                                                             │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐           │
│  │  Progress   │ │ User Fields │ │   Assets    │           │
│  │             │ │             │ │             │           │
│  │ - track     │ │ - profile   │ │ - enqueue   │           │
│  │ - calculate │ │ - meta      │ │ - localize  │           │
│  └─────────────┘ └─────────────┘ └─────────────┘           │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                    Shortcodes Layer                          │
│  [lista-aulas] [meus-cursos] [certificado] [barra-progresso]│
│  [resultado-busca] [cursos_da_trilha] [single-trilha] ...   │
├─────────────────────────────────────────────────────────────┤
│                    Data Layer                                │
│  wp_posts | wp_postmeta | wp_users | wp_usermeta            │
│                   wp_acesso_cursos (custom)                  │
└─────────────────────────────────────────────────────────────┘
```

## Princípios Arquiteturais

### 1. Independência de Tema
O plugin funciona com qualquer tema WordPress. Estilos são encapsulados com classes prefixadas.

### 2. Standalone (Sem Dependências)
Removida dependência do ACF. Todas funcionalidades usam APIs nativas do WordPress.

### 3. Hook-Based Extensibility
```php
// Permite extensão via actions/filters
do_action('sistema_cursos/apos_matricula', $user_id, $curso_id);
$html = apply_filters('sistema_cursos/certificado_html', $html);
```

### 4. Separação de Responsabilidades
- **Classes de negócio**: Lógica de domínio
- **Shortcodes**: Apenas renderização
- **Assets**: CSS/JS isolados

## Decisões Arquiteturais (ADRs)

### ADR-001: Tabela Customizada para Acessos
**Contexto**: Precisamos armazenar matrículas com status, datas e histórico.

**Decisão**: Usar tabela `wp_acesso_cursos` em vez de postmeta.

**Razões**:
- Queries mais eficientes
- Controle granular de índices
- Facilidade para relatórios
- Histórico de alterações

**Consequências**:
- Necessário criar tabela na ativação
- Migração de dados se trocar abordagem

### ADR-002: Relacionamentos via Postmeta
**Contexto**: CPTs precisam se relacionar (Trilha→Curso→Aula).

**Decisão**: Usar meta_key simples (`trilha`, `curso`).

**Razões**:
- Simplicidade
- Compatibilidade com WP_Query
- Não requer plugin de relacionamento

**Trade-offs**:
- Não enforça integridade referencial
- Queries podem ficar lentas com muitos posts

### ADR-003: PDF no Cliente (html2pdf.js)
**Contexto**: Gerar certificados em PDF.

**Decisão**: Usar html2pdf.js no navegador.

**Razões**:
- Evita bibliotecas PHP pesadas (TCPDF, Dompdf)
- Fidelidade ao layout HTML/CSS
- Menor carga no servidor

**Trade-offs**:
- Requer JavaScript habilitado
- Performance depende do dispositivo

## Evolução Arquitetural

### Fase 1 (Atual): Monolito Modular
Arquitetura atual com classes separadas mas acopladas.

### Fase 2 (Proposta): Serviços Injetados
```php
class System_Cursos_Shortcode_Certificado {
    private $certificate_service;
    private $access_service;
    
    public function __construct(
        Certificate_Service $cert,
        Access_Service $access
    ) {
        $this->certificate_service = $cert;
        $this->access_service = $access;
    }
}
```

### Fase 3 (Futura): Microservices/API
```
┌────────────────┐     ┌────────────────┐
│  WordPress     │────▶│  API Gateway   │
│  (Frontend)    │     │                │
└────────────────┘     └───────┬────────┘
                               │
          ┌────────────────────┼────────────────────┐
          ▼                    ▼                    ▼
   ┌────────────┐       ┌────────────┐       ┌────────────┐
   │  Courses   │       │   Access   │       │   Certs    │
   │  Service   │       │  Service   │       │  Service   │
   └────────────┘       └────────────┘       └────────────┘
```

## Padrões Recomendados

### Repository Pattern (para Data Access)
```php
interface Course_Repository {
    public function find($id): ?Course;
    public function find_by_trilha($trilha_id): array;
    public function save(Course $course): bool;
}

class WP_Course_Repository implements Course_Repository {
    public function find($id): ?Course {
        $post = get_post($id);
        return $post ? Course::from_post($post) : null;
    }
}
```

### Factory Pattern (para Renderização)
```php
class Renderer_Factory {
    public static function create(string $type): Renderer {
        return match($type) {
            'curso' => new Curso_Renderer(),
            'trilha' => new Trilha_Renderer(),
            'aula' => new Aula_Renderer(),
            default => throw new InvalidArgumentException()
        };
    }
}
```

## Métricas de Saúde Arquitetural

| Métrica | Atual | Meta |
|---------|-------|------|
| Maior arquivo (linhas) | ~1500 | < 300 |
| Dependências circulares | ? | 0 |
| Cobertura de testes | 0% | > 50% |
| Acoplamento | Alto | Médio |

## Documentação de Referência
- [Arquitetura](../docs/architecture.md)
- [Fluxo de Dados](../docs/data-flow.md)
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)

## Checklist Arquitetural

- [ ] Responsabilidades bem definidas por classe
- [ ] Sem dependências circulares
- [ ] Hooks para extensibilidade
- [ ] Dados separados de apresentação
- [ ] ADRs documentados para decisões importantes
