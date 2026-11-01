# MAT: RESULTADO MATEMATICO E ENSAIO IA (v0.5)

## O que voce recebe

- `MAT_Refinado_0_1/resultado_temporis_piloto.php`: pagina web de demonstracao matematica com 6 acontecimentos de historia real, **mas com 11 notas HIPOTETICAS**. Calcula I/C/F, pesos, K, T, variacao beta e exporta CSV.
- `MAT_Refinado_0_1/app/TemporisPiloto.php`: calculadora experimental isolada. Chama a implementacao oficial `RevisedMethodology::weight` e sua escala.
- `MAT_Refinado_0_1/cli/temporis_ensaio.php`: imprime JSON do ensaio sem MySQL, para reproducao.
- `MAT_Refinado_0_1/cli/triagem_ia_piloto.php`: experimento opcional para 1 a 3 **candidatos importados com texto documental**. Faz leitura do MySQL e utiliza Qwen local existente via `AssistenteCientifico::queryOllama`. Mostra notas **propostas**, recusando notas sem citacao literal; calcula peso **PROVISORIO** somente se todas as 11 notas tiverem evidencia. Nunca altera ou aprova dados.
- `MAT_Refinado_0_1/tests/test_temporis_piloto.php`: testa a equivalencia das contas com `RevisedMethodology::series`.
- `RELATORIO_PARA_PROFESSOR.pdf`: resultados didaticos, formula e limites cientificos.
- `resultado_temporis_demonstrativo.json`: dados do ensaio e cenarios (beta=1,1,5,2).

## Instalar (sem substituir arquivos antigos)

1. Faca backup ou commit do projeto. **Mantenha seu banco MAT e Git atuais.**
2. Baixe o ZIP, copie-o pelo Explorador de Arquivos para `D:\Faculdade nero\xampp\htdocs\MAT_Refinado_Git`.
3. No PowerShell do VS Code:

```powershell
cd "D:\Faculdade nero\xampp\htdocs\MAT_Refinado_Git"
Expand-Archive -LiteralPath ".\MAT_TEMPORIS_RESULTADO_PILOTO_v05.zip" -DestinationPath "." -Force
& "D:\Faculdade nero\xampp\php\php.exe" ".\MAT_Refinado_0_1\tests\test_temporis_piloto.php"
```

4. Com Apache e MySQL ligados, entre no MAT e abra:

`http://localhost/MAT_Refinado_Git/MAT_Refinado_0_1/resultado_temporis_piloto.php`

5. Veja a curva e exporte CSV. **Esses valores NAO contam como avaliacoes confirmadas.**

## IA local: opcional e separada do resultado didatico

O Ollama deve estar aberto e o Qwen `qwen3.5:4b` instalado. Rode somente UM candidato primeiro:

```powershell
& "D:\Faculdade nero\xampp\php\php.exe" ".\MAT_Refinado_0_1\cli\triagem_ia_piloto.php" --limite=1
```

A saida e JSON com propostas de notas, nao uma Temporis validada. Se quiser salvar, redirecione para uma pasta **fora do htdocs e do Git**:

```powershell
New-Item -ItemType Directory -Force "D:\MAT_Relatorios_Privados"
& "D:\Faculdade nero\xampp\php\php.exe" ".\MAT_Refinado_0_1\cli\triagem_ia_piloto.php" --limite=1 > "D:\MAT_Relatorios_Privados\triagem_ia.json"
```

Na GTX 1050 4 GB essa chamada pode ser lenta; erro de timeout nao corrompe o banco. Nao publique esse JSON ou os trechos do livro no GitHub.

## O fluxo cientifico correto

1. IA sugere natureza do candidato, 11 notas e citacoes do material fornecido.
2. Pesquisador confere pagina, data, duplicata, independencia de fontes, escopo e interpretacao.
3. O pesquisador transfere os resultados documentados ao acervo, confirma a avaliacao e autoriza a inclusao.
4. A pagina Têmporis *oficial* do MAT (`index.php?page=temporis`) usa SOMENTE avaliacoes confirmadas e marcos aprovados. Sua funcao oficial e `RevisedMethodology::series()`. Este pacote nao interfere nela.
5. Relatar quantidade elegivel, metodo, incertezas e sensibilidade beta / ano-base para o orientador.

## Situacao observada em capturas, nao leitura do banco do seu computador

Ultima tela: 352 itens em preparacao, 54 no acervo e 0 rubricas confirmadas. Portanto, **nao existe ainda uma curva empirica validada** para apresentar. O ensaio com 6 eventos prova o funcionamento das formulas, NAO uma tese de aceleracao.

## Observacao de dependencia

Este pacote PRESSUPOE que a instalacao atual possui `app/RevisedMethodology.php` (MAT 0.2) e `app/AssistenteCientifico.php` (modulo v0.4). Nao substitui nem reinstala nenhum dos dois.
