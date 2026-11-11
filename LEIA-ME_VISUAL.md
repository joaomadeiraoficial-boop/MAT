# MAT — Ajuste visual da Fila de revisão rápida (v3)

Esta atualização muda **somente a apresentação** da página de curadoria e nomes de filtros.
Não altera o banco de dados, algoritmos de bloqueio, notas, aprovações nem homologações.

## Instalação
1. Faça backup da pasta `C:\xampp\htdocs\MAT_Refinado_0_1`.
2. Extraia o ZIP **na pasta `C:\xampp\htdocs\`**, mantendo o caminho interno `MAT_Refinado_0_1/`.
3. Permita substituir os quatro arquivos da atualização.
4. Abra `http://localhost/MAT_Refinado_0_1/index.php?page=fila_rapida`.
5. Aperte `Ctrl + F5` se a aparência antiga ficar em cache.

## Mudanças
- Cabeçalho do modo de avaliação mais discreto.
- Resumo do banco com **520 aptos** (ou a contagem atual do seu banco) visível e clicável.
- Dois cartões que levam diretamente a **Prontos para revisar** e **Precisam de correção**.
- Formulário **Preencher notas mínimas em lote** passa a ficar recolhido por padrão.
- Mensagem explicativa caso nenhum candidato tenha menos de 11 notas (pode haver notas zero).
- Botões, espaçamentos, responsividade e legibilidade aprimorados.

O modo simplificado permite **aprovação preliminar**, mas não equivale à homologação científica.

## Arquivos substituídos
- `MAT_Refinado_0_1/views/fila_rapida.php`
- `MAT_Refinado_0_1/assets/fila-rapida.css`
- `MAT_Refinado_0_1/app/CuradoriaFilaRapida.php` (apenas rótulos dos filtros)
- `MAT_Refinado_0_1/views/header.php` (versão do cache CSS)
