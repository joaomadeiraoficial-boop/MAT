# MAT-Proj — primeira etapa do módulo preditivo (M7 / experimental)

**Situação:** código experimental executável. Não é evidência de precisão nem validação da Têmporis.

- **Implementado:** reconstrução de dados parciais por referência histórica, variações estratificadas, previsão de ano seguinte sem dados do alvo, baseline ingênuo, métricas MAE/RMSE, retrotestes e testes anti-vazamento.
- **Fonte real preparada:** ITU / Banco Mundial, distribuída pela Our World in Data; requer importação ou atualização local do CSV.
- **Amostra incluída:** 120 unidades sintéticas para verificar execução, nunca para alegar precisão científica.
- **Não implementado:** coleta agendada, incerteza calibrada, testes reais em MySQL. Acesso no menu principal e na abertura do Observatório: adicionado na integração 0.1.1 do MAT-Proj.
- **Preservação do legado:** nenhuma alteração nos cálculos da Têmporis, fontes, marcos, rubricas, migrations ou banco antigo.

Leia `mat_proj/README.md` e `mat_proj/docs/PROTOCOLO_EXPERIMENTAL.md` antes de interpretar resultados.
