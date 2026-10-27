# MAT — Observatório do Tempo | v6.2.1 (base documental piloto)

> A estética, a arquitetura e a navegação são do Observatório v6.1. Foram integrados recursos científicos selecionados do MAT v6 Têmporis Lab enviado pelo autor. **Não é uma cópia visual do MAT antigo.**


## Base documental piloto — 17 marcos (v6.2.1)

A versão 6.2.1 **não altera o schema** da v6.2. Acrescenta:

- `database/base_piloto_17_marcos.sql` — SQL seguro para importar 17 candidatos com URLs e vínculos em `fontes`/`marco_fontes`.
- `sample/base_piloto_17_marcos.csv` — importação por staging como alternativa.
- `sample/base_piloto_17_catalogo.json` — catálogo de origem para auditoria.
- `docs/BASE_CIENTIFICA_PILOTO.md` — explicação da seleção e de suas limitações.

Para usar o novo conjunto, **faça backup de mat_observatorio e importe SOMENTE** `database/base_piloto_17_marcos.sql` no phpMyAdmin, após o esquema v6.2 estar instalado. Os marcos entram `pendente` e não pontuam na Têmporis. Esta base **não foi validada para demonstração de aceleração**.

## O que foi integrado

- Laboratório científico com painel de qualidade.
- Classificador por regras de escopo (sugestão automática; decisão humana obrigatória).
- Pré-codificação de sete critérios herdados do Têmporis Lab (apenas sugestão, não altera as notas).
- Catálogo e vínculos múltiplos entre dossiês e referências bibliográficas.
- Comparação textual e de acrônimos para sugerir duplicatas; as decisões humanas são preservadas.
- Auditabilidade dos pareceres e vínculos por meio da tabela de revisões.
- Controle de inclusão da Têmporis: aprovação + escopo revisado = SIM + fonte vinculada + pontuações completas + autorização explícita.
- Importação CSV com cabeçalho `titulo` **ou** `nome`; preservação do JSON bruto.
- Utilitário para copiar **apenas para staging** os registros do MySQL do Têmporis Lab v6, sem escrever no banco de origem.
- Componentes editoriais exclusivos para toda essa estação.

**Não foi migrado automaticamente:** banco real do usuário (não estava no ZIP), vínculos documentais originais do banco MySQL, alterações feitas no repositório remoto, resultados de análises preditivas ou importação externa ao vivo. Consulte `docs/PARIDADE_E_MIGRACAO.md`.

## Instalação em novo XAMPP

1. Extraia em `C:\xampp\htdocs\MAT_Observatorio_v6_2_Integrado`.
2. Abra phpMyAdmin (`http://localhost/phpmyadmin`) e importe **somente** `database/schema.sql`. Ele cria o banco independente `mat_observatorio` e não toca em `mat_temporis_lab` nem `mat_v5`.
3. Opcional: importe `database/demo.sql`, que contém **somente dados ilustrativos**, fora da Têmporis.
4. Copie `config.example.php` para `config.local.php`; edite as credenciais sem publicar esse arquivo.
5. No terminal da pasta do projeto, crie o pesquisador com:

```powershell
C:\xampp\php\php.exe cli\criar_admin.php "Pesquisador MAT" "voce@exemplo.com" "sua-senha-segura-com-mais-de-12-caracteres"
```

6. Entre em `http://localhost/MAT_Observatorio_v6_2_Integrado/`. A navegação **Laboratório científico** abre os novos recursos.

### Se já instalou o Observatório v6.1

Faça **backup** do banco `mat_observatorio`, substitua os arquivos de aplicação pelo v6.2 e execute **uma vez** `database/update_v6_2.sql` pelo phpMyAdmin. **Não** execute o `schema.sql` como procedimento de atualização de versão; ele serve para instalação nova.

### Importar o banco do MAT v6 Têmporis Lab

Para simular a migração, mantendo a origem inalterada:

```powershell
C:\xampp\php\php.exe cli\importar_lab_v6.php --source-db=mat_temporis_lab
```

Para **importar os registros para a área de preparação**, após conferir a simulação e fazer backup:

```powershell
C:\xampp\php\php.exe cli\importar_lab_v6.php --source-db=mat_temporis_lab --apply
```

As linhas vão para **Importação → Preparação**, não para o índice. Informações e notas originais permanecem no JSON bruto; ao promover, o cadastro reaproveita nome, data, categoria, descrição, local, civilização e criador quando existirem. As notas e a aprovação antigas **não são homologadas sem revisão**. O script não altera o banco antigo.

### Importar CSV do Lab anterior

Vá a **Importar cronologias** e use `sample/import_demo.csv` ou `sample/modis_2025_14_marcos_mat.csv` apenas para testar coleta. As datas e descrições desse segundo arquivo **não foram auditadas nesta integração**. Os registros devem permanecer em revisão até confirmação bibliográfica independente.

## Fórmulas preservadas

```text
P_i = (I_i² × C_i/100) × F_i
K(t) = soma dos P_i para cada ano <= t
T(t) = 100 × (K(t)/K_base)^0,65
```

A conversão experimental `I(1–5) × 2` e `C(1–5) × 20` foi mantida da primeira versão do Observatório. A metodologia do laboratório antigo também possuía uma proposta mais detalhada com sete critérios e `beta` parametrizável. **Essas variantes não foram silenciosamente fundidas:** o usuário pode examinar as sugestões, mas os cálculos existentes permanecem identificáveis. Comparações e calibração seguem no roadmap.

## Testar

```powershell
C:\xampp\php\php.exe tests\test_core.php
C:\xampp\php\php.exe tests\test_science.php
```

A saída “TESTES ... CONCLUÍDOS” confirma apenas os testes sem banco. **Testes E2E reais de leitura, gravação, importação e aprovação exigem o MySQL em funcionamento no seu XAMPP.**

## Git — branch separada

Mantenha o `main` antigo intocado. Crie uma branch de integração e copie a pasta nova para ela, sem inserir `.git` dos ZIPs recebidos:

```powershell
git switch -c feature/observatorio-v6-2-integrado
git add MAT_Observatorio_v6_2_Integrado
git commit -m "feat(mat): integra laboratorio cientifico ao observatorio v6.2"
git push -u origin feature/observatorio-v6-2-integrado
```

Confirme os caminhos do projeto dentro do repositório antes de executar. Os arquivos `config.local.php`, `uploads/` e dumps privados devem permanecer fora do Git.

## Limites importantes

- Não se trata de uma previsão automática comprovada.
- Triagem por palavras-chave não substitui parecer científico.
- O cálculo da Têmporis é experimental, sem validação externa concluída.
- O comparador de duplicatas analisa até 1500 marcos por execução; varredura em lote para bases maiores permanece pendente.
- Fontes do MySQL legado permanecem na origem até que você valide e migre seus vínculos.
- A aplicação deve permanecer local durante o desenvolvimento; ainda não passou por auditoria de segurança para exposição pública.
- O formulário não insere sozinho fontes ou autoriza indicadores; são etapas de revisão separadas.

**Planejamento detalhado:** `docs/ROADMAP_MAT_GIT.md`.

---

## Estrutura

```text
app/                 fonte PHP, conexão, repositório e Têmporis
views/               páginas e componentes visuais
assets/              estilo, símbolo, interatividade e CSV modelo
api/                 consultas JSON (somente leitura)
cli/                 criação de administrador e migração conservadora
database/            esquema novo e semente ilustrativa
docs/                roadmap, estado atual e direção de arte
tests/               testes do núcleo sem MySQL
actions.php          ações autenticadas de escrita
export.php           downloads CSV, JSON e SQL de consulta
index.php            rotas/páginas
```

## Git — sugestão para o próximo commit

Antes, compare o projeto com **seu repositório real** e ajuste caminhos e banco. Não sobrescreva tudo indiscriminadamente.

```bash
git status
git switch -c feature/observatorio-v6-1
git add app assets views api cli database docs tests index.php actions.php export.php README.md .gitignore config.example.php
git commit -m "feat(ui): cria identidade autoral e base de pesquisa do observatorio MAT"
```

Para ver o planejamento científico completo: [`docs/ROADMAP_MAT_GIT.md`](docs/ROADMAP_MAT_GIT.md).

### Limitações da prévia offline

A prévia é um conjunto de páginas HTML estáticas derivadas das próprias views PHP. Você poderá navegar entre seções e dossiês e avaliar a estética. Formulários, resultados de busca, filtros, autenticação e escrita no banco **não funcionam nela**; abra o endereço `localhost` para a versão real.
