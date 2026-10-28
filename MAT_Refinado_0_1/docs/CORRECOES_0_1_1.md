# MAT — Correções de segurança matemática (0.1.1)

**Data:** 28/10/2026. **Tipo:** patch corretivo, sem migração SQL.

## Bugs reproduzidos e corrigidos

1. **Contaminação do referencial temporal**: `app/temporis.php` usava o último
   acumulado encontrado em uma lista potencialmente desordenada para `K_base`.
   Em listas com evento posterior ao ano-base antes de um evento antigo, o resultado
   ficava errado. A série agora é ordenada em memória por ano e desempates estáveis.
   `K_base` soma apenas os eventos com ano não posterior à referência.
2. **Nota fracionária truncada**: `app/Methodology.php` fazia `(int)$value`, de
   forma que uma entrada 2,9 virava 2 sem aviso. Agora somente inteiros na escala
   0 a 3 são aceitos; vazio/null continuam indicando ausência de nota.
3. **Data ausente convertida em ano zero**: o motor não inclui no cálculo registros
   sem ano inteiro válido, mesmo que outros campos tenham sido preenchidos.

## Invariantes preservados

- Método experimental A: `P_i = I_i² × C_i/100 × F_i`, conversão I=2×impacto
  e C=20×confiabilidade do Refinado, sem alterações de escala.
- Método B do Lab (`Methodology::score`, sete critérios) **não** foi incorporado ao
  cálculo central, para não misturar modelos sem aprovação metodológica.
- `K(t)=ΣP_i`; `T(t)=100(K(t)/K_base)^0,65`; dados reais pendentes não são
  aprovados por esse patch.
- `reference_year` configurado no banco/projeto continua sendo o mesmo.

## Testes para executar

```powershell
C:\xampp\php\php.exe MAT_Refinado_0_1\tests\test_core.php
C:\xampp\php\php.exe MAT_Refinado_0_1\tests\test_science.php
C:\xampp\php\php.exe MAT_Refinado_0_1\tests\test_regressoes_0_1_1.php
```

Se estiver no diretório `MAT_Refinado_0_1`, remova o prefixo
`MAT_Refinado_0_1\` dos comandos.

## Ainda não resolvido neste patch

- A metodologia B carece de integração completa (7 evidências e confiança percentual).
- Os modelos A e B não são comparáveis enquanto as escalas e a referência temporal
  não forem padronizadas experimentalmente.
- Falta restaurar paridade do Lab antigo (importador multiformato, regras, lotes,
  duplicatas, sensibilidade, coletor, etc.).
- Não é possível garantir o comportamento do MySQL real sem o ambiente XAMPP.

**Aplicação segura:** usar a branch `feature/mat-refinado-0-1`, fazer backup,
conferir `git status`, aplicar o patch, executar testes, revisar diff e fazer
um commit **somente** desta correção. Não copiar `.git` ou alterar a `main`.
