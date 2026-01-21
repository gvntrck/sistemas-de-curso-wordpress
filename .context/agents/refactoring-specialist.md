# Refactoring Specialist - LMS SuporteRapido

## Quando Refatorar

- Código duplicado (DRY violation)
- Função muito longa (>50 linhas)
- Múltiplas responsabilidades
- Difícil de testar
- Performance ruim

## Padrões de Refactoring

### Extract Method
```php
// Antes
public function processar() {
    // 30 linhas validando
    // 20 linhas processando
    // 15 linhas salvando
}

// Depois
public function processar() {
    $this->validar();
    $resultado = $this->processar_dados();
    $this->salvar($resultado);
}
```

### Replace Conditional with Polymorphism
```php
// Ao invés de muitos if/elseif
// Usar classes especializadas
```

## Checklist
- [ ] Testes passam antes e depois
- [ ] Versão atualizada
- [ ] Sem quebrar funcionalidades existentes

## Recursos
- **Development Workflow:** `../docs/development-workflow.md`
