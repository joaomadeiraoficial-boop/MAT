# Dicionário resumido do banco

- `usuarios`: operadores da pesquisa. Senha com hash gerado por `password_hash`. Não publicar dumps da tabela.
- `marcos`: título, ano negativo para a.C., ano final opcional, precisão, categoria, período, descrição, procedência e avaliação.
- `marcos.status`: `demonstracao`, `importado`, `pendente`, `aprovado`, `rejeitado`. **Só `aprovado` participa da Têmporis.**
- `impacto`, `confiabilidade`: escala 1 a 5 mantida para compatibilidade conceitual com a v5.1; ambas passam por conversão **provisória** em `app/temporis.php`.
- `fator_fundacional`: valor entre 1 e 1.5, ainda pendente de rubrica científica.
- `fontes`: metadados bibliográficos; preparada para vinculação com cada marco.
- `import_lotes`: lote CSV ou legado com hash para evitar repetição.
- `import_itens`: linha bruta em JSON e mapeamento parcial, com estado de revisão. Nunca importação científica automática.
- `revisoes`: alterações de registro, responsável, antes e depois.

**Atenção:** para importar legado com categorias e outras tabelas vinculadas, será preciso auditar o SQL real. O script genérico foi feito para não alterar o banco anterior.

## Ampliação v6.2 — escopo e proveniência

- `marcos.natureza`: classe informativa sugerida (`tecnologico`, `descoberta_cientifica`, etc.).
- `marcos.sugestao_elegibilidade`: saída **não vinculante** do classificador por regras.
- `marcos.elegibilidade_temporis`: parecer humano em `sim|nao|revisar`.
- `marcos.motivo_escopo`: justificativa revisável.
- `marcos.escopo_revisado`: flag que exige manifestação humana.
- `marcos.incluir_temporis`: autorização explícita condicionada à aprovação e à evidência.
- `marco_fontes`: relação N:N com tipo de evidência, trecho e página.
- `duplicate_candidates`: pares possivelmente repetidos, sempre revisáveis; nenhum evento é mesclado automaticamente.
- `revisoes`: log de ações de criação/edição, pareceres e vínculos. Decisões de duplicatas ficam com status na tabela respectiva.

**Gate do índice:** aprovado, escopo revisado como SIM, incluir_temporis=1, fonte formalmente vinculada, pontuação completa. O texto livre no campo `fonte` não basta para o cálculo.
