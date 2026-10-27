# Integração do MAT v6 Têmporis Lab no Observatório v6.2

## Hierarquia de decisões

1. O Observatório continua sendo a aplicação e identidade principal: layout, navegação, formulários, dossiês, acervo, linha, atlas e Têmporis.
2. O MAT v6 Lab é fonte de técnicas e fluxos **científicos**, não de tema visual, rotas antigas ou instalação sobre o banco legado.
3. Registros antigos não são presumidos revisados. A migração passa por staging; decisões humanas são explicitamente registradas.

## Resultado da integração

| Módulo do Lab antigo | Estado na v6.2 | Limitação |
|---|---|---|
| Classificação de escopo por regras e pesos | Portado (`app/ScopeClassifier.php`, normalizador) | Regras personalizadas do dicionário original ainda não são editáveis na interface nova |
| Sugestão de métricas | Portado como pré-codificação de 7 critérios | Somente leitura; não recalibra automaticamente a fórmula original |
| Detector de duplicatas | Similaridade portadа, com tela e decisão | Mesclagem automática não foi portada (risco de perda/contaminação de dados); lotes >1500 exigem nova rotina |
| Proveniência documental | Catálogo, tabela associativa e vínculos por papel | Registros da fonte MySQL original precisam ser reconciliados |
| Auditabilidade | Revisões por registro e por parecer | Não substitui auditoria externa nem controle de revisores múltiplos |
| Importação CSV / dado bruto | Compatibilizada (nome ou titulo, JSON bruto) | Importação ainda local, arquivos pequenos para evitar travar o Apache |
| Importação do MySQL do MAT Lab | CLI leitura na origem → staging no destino | Fontes, avaliações e scores legados ficam no JSON até revisão |
| Têmporis | Fórmulas v6.1 preservadas e gate ampliado | Escalas, alpha e Kbase ainda requerem validação científica |
| Coletor Wikidata externo | **Não migrado** | Depende de APIs remotas, limites e verificação de fontes |
| Extrator Bunch–Hellemans | **Não implementado** | Exige acesso legítimo à fonte, mapeamento e protocolo de validação |
| Backtesting, RMSE, modelos lineares/logísticos, previsão | **Não implementados** | Permanecem no roadmap |

## Migração por etapas

1. Efetuar backup do banco `mat_temporis_lab` usando phpMyAdmin.
2. Criar ou atualizar o banco novo `mat_observatorio`.
3. Validar páginas do Observatório e criar login.
4. Rodar `cli/importar_lab_v6.php --source-db=mat_temporis_lab` (simulação).
5. Rodar a mesma ferramenta com `--apply` para copiar **apenas as linhas de marcos para preparação**.
6. Conferir e promover um pequeno lote; os dados brutos permanecem no JSON.
7. Vincular fontes verificáveis e registrar o parecer de escopo.
8. Atribuir/revisar as notas e justificar a inclusão.
9. Somente após aprovação, autorizar entrada explícita na Têmporis.

A migração não escreve no banco antigo, mas opera com as credenciais locais: configure uma conta MySQL de leitura apenas para a origem se possível. Não publique dump ou credenciais no Git.

## Próximo commit sugerido

`feat(data): valida migracao em staging de registros do MAT Lab` — executar um caso piloto no MySQL real e documentar divergências.
