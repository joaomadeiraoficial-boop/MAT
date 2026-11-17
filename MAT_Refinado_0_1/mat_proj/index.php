<?php
/** Endereço histórico preservado; o MAT-Proj agora é o Backtesting integrado. */
declare(strict_types=1);
$params=$_GET;
$params['page']='backtesting';
if(!isset($params['fonte']))$params['fonte']='snapshot';
header('Location: ../index.php?'.http_build_query($params),true,302);
exit;
