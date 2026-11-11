# Registro da mesclagem — 09/11/2026

**Origem:** `MAT_Refinado_0_1(4).zip` + `MAT_Final.zip`.

**Política:** partir do MAT Refinado; trazer o laboratório Final e mudanças de navegação; não alterar nenhum banco existente. Não restaurar o dump automaticamente.

### Implementado

- Adicionados módulo de cálculo, nova página Têmporis, estilos, testes unitários e de isolamento, snapshot e teste de banco.
- Atualizados `index.php`, `views/header.php`, `views/metodo.php`, `views/observatorio.php`, `views/projecoes.php`, `views/temporis.php`.
- `CuradoriaRodadas` continua acessando a **curadoria original** por padrão, agora com nome configurável.
- `TemporisFinal::conectar()` usa **mat_final_curadoria** independentemente; recusa se coincidir com qualquer banco principal.
- `config.local.php` e `config.example.php` preservam usuário, senha, banco principal e ano-base da instalação original, apenas acrescentando chaves de banco.
- `database/*.sql`, `triagem_assistida_750/*`, `export.php` e os scripts originais do Refinado permanecem sem reescrita para os bancos do Final.
- Incorporada instrução de instalação de snapshot específica para a pasta integrada, sem a tentativa do instalador do MAT Final independente de duplicar o banco principal.

### Mudanças intencionalmente não transplantadas

- `MAT_Final` alterava os nomes dos bancos **de todas as páginas** para `mat_final_*`, bem como sua sessão e identidade global; isto seria incompatível com o objetivo de incorporar o módulo *dentro* do Refinado. Essas alterações foram rejeitadas.
- O instalador do pacote MAT Final independente e seu banco principal auxiliar `mat_final_observatorio` não são necessários neste projeto integrado. O novo SQL foi mantido em banco **separado para não misturar a exportação de 800 dossiês com a curadoria ativa**.
- Resultados científicos não foram recalculados como verdade; a página de método sinaliza o exemplo fixo datado de 07/11/2026.

### Testes

Veja a seção final de `COMO_INSTALAR_MAT_INTEGRADO.md`; os testes com MySQL exigem importação prévia do snapshot.
