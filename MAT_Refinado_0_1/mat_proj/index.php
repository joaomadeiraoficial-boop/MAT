<?php
/** Endereço histórico preservado; o MAT-Proj agora faz parte da navegação principal. */
declare(strict_types=1);
$params=$_GET;
$params['page']='projecoes';
header('Location: ../index.php?'.http_build_query($params),true,302);
exit;
