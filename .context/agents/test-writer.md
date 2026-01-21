# Test Writer - LMS SuporteRapido

## Estratégia de Testes

Atualmente: **Testes Manuais**  
Futuro: PHPUnit + Cypress

## Casos de Teste Principais

### Acesso
- TC-001: Concessão manual de acesso
- TC-002: Verificação de expiração
- TC-003: Acesso via grupo

### Aprendizado
- TC-004: Navegação AJAX entre aulas
- TC-005: Marcar aula como completa
- TC-006: Cálculo de progresso

### Certificados
- TC-007: Emissão automática
- TC-008: Impressão de certificado

### Quizzes
- TC-009: Responder quiz com sucesso
- TC-010: Falhar em quiz
- TC-011: Esgotamento de tentativas

### WooCommerce
- TC-012: Compra de produto vinculado

## Checklist Pré-Deploy

- [ ] Login funciona
- [ ] [meus-cursos] renderiza
- [ ] Navegação AJAX funciona
- [ ] Marcar completa funciona
- [ ] Quiz submete
- [ ] Certificado aparece quando deveria

## Recursos
- **Testing Strategy:** `../docs/testing-strategy.md`
