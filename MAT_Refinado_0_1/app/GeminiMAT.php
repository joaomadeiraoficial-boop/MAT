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
            $msg=$status===429?'Limite gratuito atingido: encerre e tente outro dia':
                ($status===404?'Modelo indisponivel: consulte os modelos gratuitos no Google AI Studio':'Confira chave, permissao do modelo e cota gratuita');
            throw new RuntimeException('Gemini HTTP '.$status.'. '.$msg);
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
