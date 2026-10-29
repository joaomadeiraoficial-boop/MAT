<?php
declare(strict_types=1);
require __DIR__.'/../app/RevisedMethodology.php';
require __DIR__.'/../app/TemporisPiloto.php';
$scenarios=[];
foreach([1.0,1.5,2.0] as $beta) $scenarios[] = TemporisPiloto::series(TemporisPiloto::examples(),TemporisPiloto::BASE_YEAR,$beta);
header_remove();
echo json_encode(['status'=>'ENSAIO_DIDATICO_NAO_VALIDADO',
    'aviso'=>'Notas HIPOTETICAS; resultados NAO representam avaliacao cientifica de marcos.',
    'formula'=>'I=1+9*SI/12; C=SC/12; F=1+0.5*SF/9; w=(I/10)^beta*C*F; T=100*ln(1+K)/ln(1+K_base)',
    'scenarios'=>$scenarios],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
