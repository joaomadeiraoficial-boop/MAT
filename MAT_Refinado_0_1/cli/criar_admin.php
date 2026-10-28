<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Somente CLI'); }
require __DIR__.'/../app/bootstrap.php';
if(!db()){fwrite(STDERR,"Banco indisponivel. Importe database/schema.sql e configure config.local.php.\n");exit(1);}
if($argc!==4){fwrite(STDERR,"Uso: php cli/criar_admin.php \"Nome\" email@exemplo.com \"senha-forte\"\n");exit(1);}
[$file,$name,$email,$password]=$argv;
if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<12){fwrite(STDERR,"Email invalido ou senha com menos de 12 caracteres.\n");exit(1);}
$stmt=db()->prepare('INSERT INTO usuarios(nome,email,senha_hash) VALUES(?,?,?) ON DUPLICATE KEY UPDATE nome=VALUES(nome),senha_hash=VALUES(senha_hash)');
$stmt->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]);
echo "Usuario criado/atualizado: $email\n";
