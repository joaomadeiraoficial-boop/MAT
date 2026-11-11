# MAT Refinado + Final — Índice Têmporis reorganizado

## Objetivo

O Têmporis Final é a **página principal**. O resultado do índice e sua curva aparecem primeiro. As fórmulas alternativas aparecem apenas após abrir **Mostrar mais · outras fórmulas e escalas**. Os modelos de previsão (linear, exponencial, aceleração local, Ímpeto, logístico, Gompertz e controles) estão em **Mostrar mais · modelos de previsão concorrentes**, com retrotestes detalhados na página **Backtesting**.

O **Caderno de Fórmulas** reúne as **seis expressões principais**: o peso e a regra do índice nas versões Legada, Reformulada e Final. Isso não significa seis provas independentes ou seis métodos preditivos distintos. As fórmulas antigas foram adaptadas às rubricas atuais para uma comparação matemática; não reconstituem os dados de 1 a 5 da antiga curadoria.

Menu:

- Índice Têmporis → **Têmporis · resultado atual** (`index.php?page=temporis`)
- Índice Têmporis → **Caderno de Fórmulas** (`index.php?page=metodo`)
- Índice Têmporis → **Backtesting · validação** (`index.php?page=backtesting`)
- Índice Têmporis → **Formulações anteriores** (Legada, Reformulada)
- Pesquisa e curadoria → **Laboratório de evidências** (trabalho de curadoria, não fórmula do índice)

## Origem dos dados — mudança importante

A página do Têmporis **prioriza o banco de curadoria ativa `mat_temporis_curadoria`**, definido por `curadoria_db_name` em `config.local.php`. A conexão executa **somente SELECT**. Se não houver conexão válida, a opção *Automática* tenta o banco separado do MAT Final (`mat_final_curadoria`) e, por último, a exportação local datada de 07/11/2026. O nome da **fonte efetivamente utilizada é exibido na tela**. Se você selecionar explicitamente uma fonte indisponível, o sistema exibe um erro; não troca por outra silenciosamente.

Você pode escolher manualmente a fonte no formulário **Origem dos dados**: Automática / Curadoria ativa / Banco isolado / Exportação fixa.

A exportação de 07/11/2026 contém **800 candidatos brutos**, mas somente **667 elegíveis** na configuração padrão: **3 rejeitados e 130 sem data utilizável**. Os 800 permanecem registrados; os 130 sem data não podem ser colocados arbitrariamente na curva cronológica. Os demais campos de qualidade (notas provisórias, datas extraídas, evidências pendentes) são mostrados em **Por que nem todos os marcos aparecem?**. Para sua base MySQL viva, os números podem ser diferentes — **não são contagens fixas da aplicação**.

## Como instalar no XAMPP

1. Faça backup da pasta do projeto e dos bancos (exportação SQL via phpMyAdmin). **Preserve seu arquivo `config.local.php`** existente e verifique credenciais antes de substituir arquivos.
2. Copie a pasta completa `MAT_Refinado_0_1` para `C:\xampp\htdocs\` (ou em pasta paralela para comparar).
3. Ligue Apache e MySQL no painel XAMPP. Garanta que o driver `pdo_mysql` esteja habilitado no PHP utilizado.
4. Abra `http://localhost/MAT_Refinado_0_1/index.php?page=temporis`.
5. Confira o aviso **Origem dos números** e abra **Por que nem todos os marcos aparecem?**. Se necessário, selecione *Curadoria atual (MySQL do MAT Refinado)* para forçar a consulta ao banco de trabalho.
6. Abra `http://localhost/MAT_Refinado_0_1/index.php?page=metodo` e `http://localhost/MAT_Refinado_0_1/index.php?page=backtesting`.
7. Se aparecer conteúdo antigo, atualize com Ctrl+F5.

### Diagnóstico por linha de comando

No PowerShell, dentro da pasta do projeto:

```powershell
C:\xampp\php\php.exe .\cli\diagnosticar_temporis.php
```

A saída compara os bancos disponíveis com o snapshot, mostrando total, elegíveis e motivos de exclusão. Não altera registros.

### Verificação no phpMyAdmin (MySQL ativo)

```sql
SELECT COUNT(*) AS candidatos FROM mat_temporis_curadoria.cur_candidatos;
SELECT estado_revisao_humana, COUNT(*) AS quantidade
FROM mat_temporis_curadoria.cur_candidatos
GROUP BY estado_revisao_humana;
SELECT COUNT(*) AS notas FROM mat_temporis_curadoria.cur_criterios;
```

Se sua curadoria tiver mais candidatos que a exportação e a tela estiver mostrando *snapshot*, o banco da curadoria **não está sendo consultado**. Confira `config.local.php`, `curadoria_db_name` e a existência das tabelas exigidas pelo módulo CuradoriaRodadas. O script não cria nem sincroniza bancos automaticamente.

## Limitações e testes

- Não foi possível testar uma conexão MySQL real neste ambiente: a instalação de PHP de teste não possui `pdo_mysql`. O funcionamento **offline** e a origem do snapshot foram verificados com testes de PHP e requisições HTTP.
- A mudança para fonte ativa foi implementada e verificada no código; a confirmação da conexão depende do seu XAMPP e das tabelas existentes.
- **Backtesting** estima crescimento de K, não valida a seleção dos acontecimentos por si só. WAPE e MAE não são intervalos de confiança. Modelos com diferente número de cortes não são diretamente comparáveis como um ranking definitivo.
- Fórmula adotada: `w=(I/10)^2*C*F`, `K(t)=soma dos pesos ate t`, `T(t)=100*K(t)/K(t0)`, desde que `K(t0)>0`.
- O MAT não inventa datas ausentes, não muda notas e não transforma candidatos provisórios em marcos homologados.
