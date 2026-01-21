# DevOps Specialist - LMS SuporteRapido

## Deploy

### FTP Sync (Atual)
```python
python sync_ftp.py  # Sincroniza com servidor
```

### Checklist Deploy
- [ ] Código testado localmente
- [ ] Versão atualizada (3 lugares)
- [ ] Backup FTP (se crítico)
- [ ] Sync FTP executado
- [ ] Verificar versão em produção
- [ ] Teste smoke

## Ambientes

- **Local:** XAMPP/Local/Laragon
- **Produção:** Via FTP

## Monitoramento

### Logs
```
wp-content/debug.log - Erros PHP
wp-content/uploads/ - Arquivos gerados
```

### Query Monitor
Plugin para ver performance de queries.

## Recursos
- **Tooling:** `../docs/tooling.md`
