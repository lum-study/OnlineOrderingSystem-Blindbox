<?php
class Recaptcha {
    public static function verify($response, $remoteIp = null) {
        if (empty($response)) {
            return false;
        }

        $secretKey = RECAPTCHA_SECRET_KEY;
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        
        $data = [
            'secret' => $secretKey,
            'response' => $response
        ];
        
        if ($remoteIp) {
            $data['remoteip'] = $remoteIp;
        }
        
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === false) {
            return false;
        }
        
        $resultJson = json_decode($result, true);
        
        return isset($resultJson['success']) && $resultJson['success'] === true;
    }
}
