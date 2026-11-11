> **ATUALIZAÇÃO IMPORTANTE — Têmporis reorganizado (09/11/2026):** para a navegação atual, seleção de dados e diagnóstico do MySQL, leia primeiro [LEIA_PRIMEIRO_TEMPORIS_REORGANIZADO.md](LEIA_PRIMEIRO_TEMPORIS_REORGANIZADO.md). Este documento descreve uma etapa anterior do projeto.

# Correção — MAT Final como método principal

## Problema identificado

A versão mesclada anteriormente manteve na vitrine inicial e no endereço `?page=temporis` o **Têmporis reformulado antigo**. O MAT Final foi colocado numa página secundária, dependente de importar um banco à parte. Isso fazia parecer que o novo método não tinha sido integrado.

## O que esta versão efetivamente muda

- **`index.php?page=temporis` agora abre o MAT Final**, com o método acumulado sem compressão `T(t)=100×K(t)/K(ano-base)`.
- **`index.php?page=temporis_reformulado` guarda o logaritmo antigo**; `temporis_legado` mantém a potência antiga.
- Página inicial, atalho central, navegação lateral e Caderno de método apontam para os endereços corretos.
- A exportação histórica real de **07/11/2026** foi convertida em JSON local (800 candidatos, 8.800 critérios); os gráficos e filtros agora carregam mesmo sem importar MySQL. Os 667 elegíveis e T(2026)≈156,24 foram reproduzidos pelo mesmo motor matemático.
- Se existir o banco **isolado** `mat_final_curadoria`, ele é preferido. Caso contrário, a tela informa que usa o snapshot estático; nunca mistura silenciosamente com `mat_temporis_curadoria`.
- Nenhum dado do banco do Refinado é escrito ou substituído por essa alteração.

## Instalação em Windows/XAMPP

1. Faça backup da instalação atual e dos bancos de dados.
2. Extraia a pasta completa `MAT_Refinado_0_1` dentro de `C:\xampp\htdocs\` **substituindo os arquivos de código** se quiser usar o endereço antigo, ou preferencialmente renomeie para `MAT_Refinado_Integrado` para testar lado a lado.
3. Inicie Apache. O novo método pode ser visto diretamente em `http://localhost/MAT_Refinado_0_1/index.php?page=temporis` sem criar novo banco; a página informa que usa o snapshot de 07/11/2026.
4. O Caderno de Método fica em `?page=metodo` e compara **Legada / Reformulada / Final**.
5. Se quiser carregar dados de MySQL ao invés do snapshot, importe opcionalmente `instalacao/mat_final_curadoria_snapshot.sql` para **novo banco isolado** `mat_final_curadoria`, seguindo `COMO_INSTALAR_MAT_INTEGRADO.md`.
6. Se a interface ainda mostrar «Têmporis reformulada» como método principal, você abriu a pasta antiga. Confira a URL e recarregue com `Ctrl+F5`.

## Limites

A seleção histórica e as notas de 07/11/2026 continuam provisórias. Fallback offline permite visualizar e comparar, **não comprova nem valida** aceleração tecnológica. O ambiente MySQL real do seu Windows ainda precisa ser testado localmente.
