<?php
class Response {
    public static function send($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    public static function error($message, $statusCode = 400) {
        self::send(['error' => $message], $statusCode);
    }
    
    public static function success($data = null, $message = 'Success') {
        $response = ['message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        self::send($response, 200);
    }
}