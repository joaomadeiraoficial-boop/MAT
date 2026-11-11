# Migrações de banco (Etapa 2)

- **Aplicação:** manual, via `mysql` CLI ou phpMyAdmin, **sempre após backup**.
- **Jornal:** execute `000_migration_journal.sql` uma vez por banco e registre cada migração aplicada em `schema_migrations`.
- **Escopo:** cada arquivo indica o banco-alvo. Não execute migrações de um banco em outro.
- **Idempotência:** scripts buscam ser seguros para reexecução quando possível; ainda assim, confira o resultado e o jornal.

## Ordem sugerida
1. `000_migration_journal.sql` (no banco-alvo, se ainda não existir a tabela)
2. Migrações do mesmo banco em ordem numérica (`010`, `020`, ...)

## Registro de aplicação (exemplo)
```sql
INSERT INTO schema_migrations (version, description) VALUES ('010', 'curadoria cur_* baseline verificado');
```

Consulte `docs/notas/ETAPA2_BANCO_MIGRACOES.md` para mapeamento e rotina de backup/restore.
