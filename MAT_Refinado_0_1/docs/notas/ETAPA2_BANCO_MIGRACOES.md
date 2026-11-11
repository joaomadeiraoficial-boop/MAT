# Etapa 2 — Banco de dados: mapeamento, migrações e consistência

Este documento registra o mapeamento dos esquemas do projeto, a rotina de backup/restore e a estratégia de migrações versionadas.

> **Importante:** execute alterações de banco **somente após backup** e, preferencialmente, em ambiente de teste/homologação antes do banco em uso.

## 1. Mapeamento de bancos e esquemas

Configuração (ver `config.example.php` / `config.local.php`):

| Chave | Banco padrão | Papel | Esquema/artefatos principais |
|---|---|---|---|
| `db_name` | `mat_observatorio` | Acervo/observatório principal | `database/schema.sql`, `database/update_v6_2.sql`, `database/demo.sql`, `database/base_piloto_17_marcos.sql` |
| `curadoria_db_name` | `mat_temporis_curadoria` | Curadoria científica (dossiês, 11 rubricas, fontes, rodadas, log) | `database/curadoria_cientifica_schema_novo.sql`; consultas em `app/CuradoriaRodadas.php`, `app/TemporisCuradoria.php` |
| `temporis_final_db_name` | `mat_final_curadoria` | Base isolada/snapshot do MAT Final (leitura) | `instalacao/mat_final_curadoria_snapshot.sql`, `instalacao/temporis_final_snapshot.json`, `app/TemporisFinal.php` |

### 1.1 `mat_observatorio` (acervo)
Tabelas (v6.2): `usuarios`, `fontes`, `import_lotes`, `marcos`, `import_itens`, `revisoes`, `marco_fontes`, `duplicate_candidates`.  
Observações: `marcos` concentra status/elegibilidade/pontuações agregadas e vínculo a fontes/importação; há chaves únicas e FKs parciais definidas no esquema base.

### 1.2 `mat_temporis_curadoria` (curadoria — família `cur_*`)
Tabelas: `cur_rodadas`, `cur_candidatos`, `cur_criterios`, `cur_fontes`, `cur_log`.  
- `cur_candidatos`: identificação/rodada, anos (`ano_original`, `ano_validado`), `estado_revisao_humana`, `homologado`, metadados e JSONs (`analise_historica_json`, `pendencias_json`, `duplicatas_json`).
- `cur_criterios`: 11 rubricas por candidato (`codigo`, `nota` 0–3 ou NULL, `situacao`, justificativa/evidências/fontes/pendências textuais ou JSON).
- `cur_fontes`: fontes do dossiê por candidato.
- `cur_log`: trilha de ações.

Scripts operacionais relevantes (somente leitura/diagnóstico ou enriquecimento controlado): `00_CONFERIR_ANTES.sql`, `MAT_DIAGNOSTICO_POS_INSTALACAO.sql`, `01_ENRIQUECER_BASE_SEM_APROVAR.sql`, `triagem_assistida_750/INSTALAR_TRIAGEM_ASSISTIDA.sql` (quando aplicável).

### 1.3 Família alternativa `temporis_cur_*` (observada no código)
`app/CuradoriaSetup.php` e `app/CuradoriaSeed.php` preparam/consultam tabelas `temporis_cur_rodadas`, `temporis_cur_fila`, `temporis_cur_avaliacoes`, `temporis_cur_criterios`, `temporis_cur_fontes` no banco de conexão do MAT (fluxo de lote de 300 itens). Este fluxo é distinto da curadoria `cur_*` em `mat_temporis_curadoria` usada por `CuradoriaRodadas/TemporisCuradoria`. Alterações/migrações devem respeitar o fluxo efetivamente em uso.

## 2. Rotina de backup e restore (obrigatória antes de migrações/importações)

### 2.1 Backup (mysqldump)
No PowerShell (ajuste usuário/senha/host/porta):

```powershell
# Exemplo: backup de cada banco
$ts = Get-Date -Format "yyyyMMdd-HHmmss"
& "C:\xampp\mysql\bin\mysqldump.exe" -u root -h 127.0.0.1 -P 3306 --routines --triggers --single-transaction mat_observatorio > "backup_mat_observatorio_$ts.sql"
& "C:\xampp\mysql\bin\mysqldump.exe" -u root -h 127.0.0.1 -P 3306 --routines --triggers --single-transaction mat_temporis_curadoria > "backup_mat_temporis_curadoria_$ts.sql"
& "C:\xampp\mysql\bin\mysqldump.exe" -u root -h 127.0.0.1 -P 3306 --routines --triggers --single-transaction mat_final_curadoria > "backup_mat_final_curadoria_$ts.sql"
```

- Para bancos grandes, considere `--quick`. Se houver senha, use `-p` (o cliente solicitará) em vez de embutir no comando.
- Armazene os dumps fora da pasta servida pelo Apache e **não versione** backups com dados reais.

### 2.2 Restore

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -h 127.0.0.1 -P 3306 mat_temporis_curadoria < "backup_mat_temporis_curadoria_YYYYMMDD-HHMMSS.sql"
```

Restaure em banco vazio/adequado, confira contagens (ex.: `00_CONFERIR_ANTES.sql`) e valide a aplicação antes de retomar a operação.

## 3. Estratégia de migrações versionadas

- **Diretório:** `database/migrations/` (arquivos numerados e idempotentes quando possível).
- **Jornal:** tabela `schema_migrations` (ver `000_migration_journal.sql`) para registrar migrações aplicadas por banco.
- **Ordem e escopo:** prefixos numéricos; cada script indica o(s) banco(s)-alvo. Aplicar manualmente via cliente MySQL/phpMyAdmin **após backup**, registrando a execução em `schema_migrations`.
- **Compatibilidade:** XAMPP pode usar MariaDB/MySQL com suporte variável a `CHECK` e DDL online. Migrações de integridade privilegiam índices e verificações; restrições `CHECK` são fornecidas quando seguras e podem ser adaptadas conforme a versão do servidor.
- **Princípio:** nunca “reaplicar” `schema.sql` completo sobre base em uso; preferir migrações incrementais e verificações (`cli/verificar_consistencia_db.php`, `database/verificacao_consistencia_curadoria.sql`).

## 4. Regras de integridade esperadas (referência)

### Curadoria `cur_*`
- `cur_criterios.nota`: `NULL` ou inteiro entre 0 e 3.
- 11 códigos por candidato (conjunto canônico): `impacto_difusao`, `impacto_magnitude`, `impacto_alcance`, `impacto_persistencia`, `conf_rastreabilidade`, `conf_independencia`, `conf_datacao`, `conf_consistencia`, `fund_pervasividade`, `fund_aprimoramento`, `fund_complementaridade` (1 registro por código quando completo).
- `cur_candidatos.estado_revisao_humana`: valores do fluxo (ex.: `NAO_REVISADO`, `EM_REVISAO`, `APROVADO`, `REJEITADO`, `HOMOLOGADO` conforme uso do sistema); `homologado` coerente com o estado quando aplicável.
- Datas/intervalos: validar conforme `app/CuradoriaDatacao.php` (intervalos consistentes, ano representativo, ausência de ano 0 nas regras do domínio).

### Observatório `mat_observatorio`
- `marcos.status`, `elegibilidade_temporis`, `precisao_data`, notas/flags dentro dos domínios definidos em `schema.sql`.
- Integridade referencial e unicidade (`uq_marco_titulo_ano`, vínculos de fonte/importação, duplicatas).

## 5. Verificação de consistência

- **SQL (somente leitura):** `database/verificacao_consistencia_curadoria.sql`
- **CLI (PHP):** `php cli/verificar_consistencia_db.php` (no diretório do app, com `config.local.php` configurado) — relata anomalias em observatório e curadoria sem alterar dados.
- **Diagnósticos existentes:** `00_CONFERIR_ANTES.sql`, `MAT_DIAGNOSTICO_POS_INSTALACAO.sql`.

## 6. Próximos passos (dentro da Etapa 2)
- Refinar migrações conforme a versão real de MySQL/MariaDB em produção.
- Expandir verificações para a família `temporis_cur_*` se esse fluxo estiver ativo.
- Adicionar migrações incrementais para ajustes de esquema detectados em bases reais (com idempotência e rollback documentado).
