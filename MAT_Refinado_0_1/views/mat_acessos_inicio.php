<?php
/** Atalhos de navegação na home. Não consulta o banco e não altera registros. */
$matHomeActions = [
    ['index.php?page=temporis', 'Índice Têmporis', 'Curva oficial e metodologia vigente'],
];
if (is_admin()) {
    if (is_file(MAT_ROOT . '/triagem_300_reais.php')) $matHomeActions[] = ['triagem_300_reais.php', 'Triagem real dos 300', 'Propostas de notas e progresso auditavel'];
    if (is_file(MAT_ROOT . '/assistente_ia.php')) {
        $matHomeActions[] = ['assistente_ia.php', 'Assistente de IA', 'Triagem e propostas dos 11 critérios'];
    }
    if (is_file(MAT_ROOT . '/experimento_temporis.php')) $matHomeActions[] = ['experimento_temporis.php', 'Teste histórico Têmporis', 'Previsão 1920 → 2020, comparação e erros'];
    $matHomeActions[] = ['index.php?page=importar', 'Importar cronologias', 'Preparação e revisão de candidatos'];
    if (is_dir(MAT_ROOT . '/mat_proj')) {
        $matHomeActions[] = ['mat_proj/', 'MAT-Proj', 'Experimentos de projeção'];
    }
    if (is_file(MAT_ROOT . '/views/avaliacao.php') && isset($pageLabels['avaliacao'])) {
        $matHomeActions[] = ['index.php?page=avaliacao', 'Avaliar 11 critérios', 'Rubricas e validação humana'];
    }
}
?>
<style>
.mat-home-access{padding:22px 0 24px;border-top:1px solid #d8cdbd}
.mat-home-access h2{font-family:Georgia,serif;font-size:clamp(23px,3vw,33px);font-weight:400;margin:7px 0 18px;color:#17282a}
.mat-home-access-eyebrow{font:10px/1.5 monospace;letter-spacing:.15em;color:#996947}
.mat-home-access-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px}
.mat-home-access-link{display:block;background:#fffcf6;border:1px solid #d8cdbd;padding:16px;min-height:100px;text-decoration:none!important;color:#192b2d!important;transition:border-color .15s,transform .15s}
.mat-home-access-link:hover,.mat-home-access-link:focus-visible{border-color:#a7744e;transform:translateY(-2px)}
.mat-home-access-link strong{display:block;font:600 18px/1.25 Georgia,serif;margin-bottom:7px}
.mat-home-access-link small{font:12px/1.5 system-ui,sans-serif;color:#596260}
.mat-home-access-warning{font:11px/1.5 system-ui,sans-serif;color:#765842;margin:14px 0 0}
</style>
<section class="mat-home-access" aria-label="Atalhos das funcionalidades MAT">
  <div class="mat-home-access-eyebrow">ESTAÇÃO DE TRABALHO / ATALHOS</div>
  <h2>Acesse suas ferramentas de pesquisa.</h2>
  <div class="mat-home-access-grid">
    <?php foreach ($matHomeActions as [$matTarget,$matLabel,$matHelp]): ?>
      <a class="mat-home-access-link" href="<?=h($matTarget)?>"><strong><?=h($matLabel)?> ↗</strong><small><?=h($matHelp)?></small></a>
    <?php endforeach; ?>
  </div>
  <p class="mat-home-access-warning">Candidatos e notas de IA permanecem pendentes de revisão científica.</p>
</section>
