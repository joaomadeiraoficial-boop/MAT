<?php
declare(strict_types=1);
/** Cliente Gemini isolado. Nao aprova marcos nem pesquisa fontes externas. */
final class GeminiMAT {
    public static function requestJson(string $model,string $system,string $user,string $key):array {
        if (!preg_match('/^gemini-[a-zA-Z0-9._-]{1,90}$/D',$model))throw new InvalidArgumentException('Nome do modelo Gemini invalido');
        if (trim($key)==='')throw new RuntimeException('Chave Gemini ausente');
        if (!function_exists('curl_init'))throw new RuntimeException('PHP cURL indisponivel');
        $payload=[
            'systemInstruction'=>['parts'=>[['text'=>$system]]],
            'contents'=>[['role'=>'user','parts'=>[['text'=>$user]]]],
            'generationConfig'=>['responseMimeType'=>'application/json','temperature'=>0]
        ];
        $url='https://generativelanguage.googleapis.com/v1beta/models/'.rawurlencode($model).':generateContent';
        $ch=curl_init($url);
        curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_HTTPHEADER=>['Content-Type: application/json','x-goog-api-key: '.$key],
            CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),
            CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>90,CURLOPT_CONNECTTIMEOUT=>15,CURLOPT_FOLLOWLOCATION=>false]);
        $raw=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$error=curl_error($ch);curl_close($ch);
        if($raw===false)throw new RuntimeException('Conexao Gemini falhou: '.$error);
        if($status!==200){
            $errorData=json_decode((string)$raw,true);
            $apiMessage=is_array($errorData)?(string)($errorData['error']['message']??''):'';
            // Nunca imprimir a chave, mesmo se o provedor ecoar parte da requisicao.
            $apiMessage=str_replace($key,'[CHAVE_OCULTA]',$apiMessage);
            $apiMessage=preg_replace('/AIza[A-Za-z0-9_-]{20,}/','[CHAVE_OCULTA]',$apiMessage);
            $apiMessage=preg_replace('/[\r\n\t]+/',' ',$apiMessage);
            $apiMessage=mb_substr_safe((string)$apiMessage,0,450);
            $advice=$status===429?'Cota ou limite de requisicoes; nao insistir.':
                ($status===400?'Solicitacao invalida ou pre-requisito nao atendido.':
                ($status===404?'Modelo ou endpoint indisponivel.':'Conferir permissoes e configuracao da API.'));
            throw new RuntimeException('Gemini HTTP '.$status.'. '.$advice.($apiMessage!==''?' Detalhe Google: '.$apiMessage:''));
        }
        $response=json_decode((string)$raw,true,512,JSON_THROW_ON_ERROR);
        $parts=$response['candidates'][0]['content']['parts']??[];
        $content='';foreach($parts as $part){if(is_array($part)&&isset($part['text']))$content.=$part['text'];}
        if($content==='')throw new RuntimeException('Gemini nao retornou texto JSON valido');
        $json=json_decode($content,true,512,JSON_THROW_ON_ERROR);
        if(!is_array($json))throw new RuntimeException('Gemini nao retornou objeto JSON');
        return ['json'=>$json,'model'=>$model,'uso'=>$response['usageMetadata']??[]];
    }
}

function mb_substr_safe(string $value,int $start,int $length):string {return function_exists('mb_substr')?mb_substr($value,$start,$length,'UTF-8'):substr($value,$start,$length);}
