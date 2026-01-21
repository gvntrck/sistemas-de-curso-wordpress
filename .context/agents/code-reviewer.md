# Code Reviewer - LMS SuporteRapido

## Checklist de Code Review

### Segurança
- [ ] Nonces verificados em AJAX/POST
- [ ] Capabilities checadas quando necessário
- [ ] Inputs sanitizados (sanitize_text_field, etc)
- [ ] Outputs escapados (esc_html, esc_url, esc_attr)
- [ ] SQL queries com prepared statements
- [ ] Sem senhas ou credenciais hardcoded

### Código  
- [ ] Segue SOLID (Single Responsibility)
- [ ] Sem código duplicado (DRY)
- [ ] Lógica simples e clara (KISS)
- [ ] Nomes descritivos de variáveis/funções
- [ ] Comentários em lógica complexa
- [ ] Sem código comentado (debug)
- [ ] Sem var_dump() ou console.log() esquecidos

### WordPress
- [ ] Usa hooks WordPress (actions/filters)
- [ ] Segue padrões WordPress (snake_case para funções)
- [ ] Enqueue correto de scripts/styles
- [ ] Tradução preparada (se aplicável)

### Performance
- [ ] Queries otimizadas (fields => 'ids' quando possível)
- [ ] Cache considerado (transients)
- [ ] Não faz loop dentro de loop desnecessariamente
- [ ] Lazy loading de recursos pesados

### Versionamento
- [ ] **Versão atualizada em 3 lugares!**
- [ ] Commit message descritivo
- [ ] Changelog atualizado (se release)

## Feedback Construtivo

❌ Evitar: "Esse código está ruim"  
✅ Preferir: "Sugestão: Podemos extrair essa lógica para um método privado para melhorar testabilidade"

## Recursos
- **Development Workflow:** `../docs/development-workflow.md`
- **Security:** `../docs/security.md`
