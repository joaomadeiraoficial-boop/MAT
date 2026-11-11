# Relatório de testes da revisão 0.1.2

**Executado:** 05/11/2026, PHP CLI 8.4.24, Linux com PHP embutido e sem driver PDO MySQL.

| Etapa | Resultado | Alcance |
|---|---|---|
| Suítes PHP do diretório `tests/test_*.php` | **32/32 aprovadas** (30 preexistentes e 2 novas) | Unidade, contrato de código, amostras controladas e regressão; NÃO demonstra SQL real |
| Checagens positivas impressas pelo conjunto | **345** linhas `[OK]`/`OK:` | Nem toda linha corresponde a um caso independente |
| Arquivos PHP do pacote | **141**, todos sem erros de sintaxe em `php -l` | Sintaxe, não HTML/JS e banco |
| Rotas HTTP testadas com PHP built-in | **10** requisições sem mensagens fatal/warning | Páginas sem dados; GET/POST anônimos; não testou autenticação de administrador |
| CSRF e acesso público | Ação POST sem CSRF 403; GET em `actions.php` 405; exportação de pendências anônima 403 | Regras de entrada testadas sem banco |
| Código de curadoria/cronologia | Testes de borda, fonte inexistente, bloqueio de fechamento global, fila incompleta, reabertura após edição, leave-one-out | Testes de função ou inspeção de código, com objetos de teste |
| Concordância de avaliadores | Leitura CSV independente, pares, contagem, concordância e rejeição de ID duplicado | Dados de teste; não são avaliações reais |
| SQL com PDO MySQL | **Não executado** | Driver indisponível; esquema e dados reais não fornecidos |
| Navegador autenticado e persistência | **Não executado** | Necessária bateria em XAMPP sobre bancos clonados |

**Conclusão operacional:** regressões disponíveis passaram e as rotas básicas responderam como esperado, mas a implantação sobre os dados reais requer validação em XAMPP. Não declare a Têmporis cientificamente validada com base nestes testes.
