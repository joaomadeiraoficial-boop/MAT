# Checklist antes do commit do MAT Observatório v6.2

- [ ] Instalar em `C:\xampp\htdocs\MAT_Observatorio_v6_2_Integrado`.
- [ ] Fazer backup de qualquer `mat_observatorio` que já exista.
- [ ] Importar `database/schema.sql` **só em instalação nova**, ou `update_v6_2.sql` sobre a instalação 6.1.
- [ ] Conferir conexão, login e telas (Laboratório, Acervo, Fontes, Triagem, Duplicatas e Auditoria).
- [ ] Importar `sample/import_demo.csv`, promover 1 item e conferir JSON bruto.
- [ ] Registrar uma fonte, vinculá-la e tomar um parecer de escopo. Verificar que itens não aprovados não entram na Têmporis.
- [ ] Simular a migração com `cli/importar_lab_v6.php` e verificar o nome do banco de origem.
- [ ] Aplicar migração real para staging **só depois do backup** e revisar uma amostra.
- [ ] Não enviar ao Git `config.local.php`, senhas, dumps privados, PDFs de obras protegidas ou `.git/` antigo.

## Sugestão de commit

No repositório original, com a nova pasta adicionada à branch própria:

```powershell
git status
git switch -c feature/observatorio-v6-2-integrado
git add MAT_Observatorio_v6_2_Integrado
git commit -m "feat(mat): integra laboratorio cientifico ao observatorio v6.2"
git push -u origin feature/observatorio-v6-2-integrado
```

Se o repositório já estiver em uma branch com esse nome, não recrie-a. Não faça push para `main` ainda.
