# Banco de dados para reprodução (26/11/2026)

Dumps comprimidos dos três bancos usados pela aplicação, exportados com
`mysqldump --single-transaction --quick --default-character-set=utf8mb4`.

| Arquivo | Banco | Conteúdo | Tamanho | SHA-256 |
| --- | --- | --- | --- | --- |
| `mat_observatorio.sql.gz` | `mat_observatorio` | 53 marcos, 2 lotes de importação, 300 fichas da fila Têmporis | 0,08 MB | `5781a586b23ea800df7dd8cb658e33f683b7a3833fba3ca2170ad32842c08a4a` |
| `mat_temporis_curadoria.sql.gz` | `mat_temporis_curadoria` | 1.200 candidatos, 13.240 notas de critérios, 2.451 fontes, 129 rodadas | 1,93 MB | `51f6c02f2d1bc5012fc8e6c5df3fdc35dca903f98e80ffd736a8ddd091c7ffa5` |
| `mat_final_curadoria.sql.gz` | `mat_final_curadoria` | 683 candidatos, 7.882 notas, 1.388 fontes, 80 rodadas | 0,79 MB | `5b746a87e9c74f8731d5862c1e79959a0990b5b1ec406914078a2f7841dcb38b` |

Cada arquivo inclui `CREATE DATABASE` e `USE`, então basta importar na ordem
acima (o `mat_observatorio` é o banco da aplicação e referencia a curadoria).

## Conta de reprodução

A tabela `usuarios` foi sanitizada: as contas reais e os hashes de senha não
fazem parte deste dump. No lugar há uma única conta local:

- **E-mail:** `reproducao@local.test`
- **Senha:** `MATreproducao2026!`

Troque a senha (ou apague a conta e rode `php cli/criar_admin.php`) antes de
usar o banco em qualquer ambiente que não seja local.

## Como importar

Interface gráfica: no phpMyAdmin, aba **Importar**, selecione o `.sql.gz`
(o phpMyAdmin descomprime sozinho).

Linha de comando (Windows, a partir da pasta do MySQL do XAMPP):

```bat
mysql -u root -p < mat_observatorio.sql.gz
```

O cliente `mysql` não descomprime `.gz`; se preferir a linha de comando,
extraia antes com 7-Zip ou `gzip -d` e importe o `.sql` resultante.

Depois de importar, confira `config.local.php`:

```php
'db_name' => 'mat_observatorio',
'curadoria_db_name' => 'mat_temporis_curadoria',
'temporis_final_db_name' => 'mat_final_curadoria',
```

## Antes de tornar o conteúdo amplamente público

Os dumps preservam títulos, fontes, justificativas e pareceres de curadoria.
Confirme a autorização para redistribuir esses registros e revise se algum
trecho de fonte citada não pode ser publicado. Os dados de acesso já foram
removidos, mas o conteúdo documental é de responsabilidade de quem publica.
