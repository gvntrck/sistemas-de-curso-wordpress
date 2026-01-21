# Architect Specialist - LMS SuporteRapido

## Decisões Arquiteturais

### MVC Adaptado para WordPress
- Models: CPTs + User/Post Meta
- Views: Shortcodes + Templates
- Controllers: Classes PHP

### Padrões
- **Single Responsibility:** Uma classe = uma responsabilidade
- **Hooks para extensibilidade**
- **User Meta para permissões** (+ tabela de log para auditoria)
- **AJAX para navegação fluida**

## Decisões Importantes

### Por que User Meta e não tabela própria para acessos?
- Integração nativa com WP
- Fácil de consultar
- Tabela de log separada para auditoria

### Por que AJAX na navegação de aulas?
- Melhor UX (sem reload)
- Player de vídeo não interrompe
- Menor consumo de dados

## Trade-offs

### Performance vs Simplicidade
- Atual: User meta (simples, mas pode ficar lento com 10k+ usuários)
- Futuro: Migrar para tabela customizada se necessário

## Recursos
- **Architecture:** `../docs/architecture.md`
