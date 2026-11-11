<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/CuradoriaDuplicatas.php';
/** Simulacao de DAO: verifica alteracoes/log sem acesso ao MySQL do usuario. */
final class FalsoPDO extends PDO {
    public array $rows=[];public array $logs=[];public bool $emTransacao=false;
    public function __construct() {
        $this->rows=[
            'MCD-00002'=>'["MCD-00473","MCD-00559"]',
            'MCD-00473'=>'["MCD-00002"]',
            'MCD-00559'=>'[]',
        ];
    }
    public function beginTransaction():bool{$this->emTransacao=true;return true;}
    public function commit():bool{$this->emTransacao=false;return true;}
    public function rollBack():bool{$this->emTransacao=false;return true;}
    public function inTransaction():bool{return $this->emTransacao;}
    public function prepare(string $query,array $options=[]):PDOStatement|false{return new FalsoStatement($this,$query);}
}
final class FalsoStatement extends PDOStatement {
    private array $res=[];
    public function __construct(private FalsoPDO $pdo,private string $sql) {}
    public function execute(?array $params=null):bool{
        $params=$params??[];
        if(str_contains($this->sql,'SELECT id_extracao,duplicatas_json')){
            $this->res=[];
            foreach($params as $id)if(isset($this->pdo->rows[$id]))$this->res[]=['id_extracao'=>$id,'duplicatas_json'=>$this->pdo->rows[$id]];
        }elseif(str_contains($this->sql,'UPDATE cur_candidatos')){
            $this->pdo->rows[(string)$params[1]]=(string)$params[0];
        }elseif(str_contains($this->sql,'INSERT INTO cur_log')){
            $this->pdo->logs[]=['id'=>$params[0],'body'=>json_decode((string)$params[1],true)];
        }else throw new RuntimeException('SQL não esperado');
        return true;
    }
    public function fetchAll(int $mode=PDO::FETCH_DEFAULT,...$args):array{return $this->res;}
}
function ex(bool $ok,string $label):void{if(!$ok)throw new RuntimeException('FALHA '.$label);echo "[OK] $label\n";}
$db=new FalsoPDO();
$user=['id'=>1,'nome'=>'Pesquisador'];
$post=['a'=>'MCD-00002','b'=>'MCD-00473','decision'=>'nao_duplicado', 'motivo'=>'Os eventos são diferentes: um debate sobre Stonehenge versus uma ferramenta de cerâmica mesopotâmica.', 'confirmacao_comparacao'=>'1'];
$result=CuradoriaDuplicatas::registrar($db,$post,$user);
ex(str_contains($result,'distintos'),'Parecer distinto confirmado');
ex($db->rows['MCD-00002']==='["MCD-00559"]','Remove o vínculo de A apenas com B');
ex($db->rows['MCD-00473']==='[]','Remove simetricamente o vínculo de B com A');
ex(count($db->logs)===1 && $db->logs[0]['body']['decisao']==='nao_duplicado','Um log auditável registra parecer do par');
ex(!$db->emTransacao,'Transação encerrada');
try{CuradoriaDuplicatas::registrar($db,$post,$user);throw new RuntimeException('Permitiu repetir vínculo ausente!');}
catch(InvalidArgumentException $e){ex(str_contains($e->getMessage(),'já foi encerrada'),'Não aplica o mesmo encerramento repetidamente');}
try{CuradoriaDuplicatas::registrar($db,['a'=>'MCD-00002','b'=>'MCD-00559','decision'=>'nao_duplicado','motivo'=>'Apenas teste'], $user);throw new RuntimeException('Aceitou justificativa insuficiente!');}
catch(InvalidArgumentException $e){ex(str_contains($e->getMessage(),'40'),'Protege contra decisão sem justificativa adequada');}
echo "SUCESSO: transação de desvinculação simulada.\n";
