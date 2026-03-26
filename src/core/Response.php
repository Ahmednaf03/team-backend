<?php

class Response{
    public static function json($data, int $status = 200,$message = null): void{
        http_response_code($status);
        header('Content-Type: application/json');
         echo json_encode([
        'status'  => $status,
        'message' => $message,
        'data'    => $data
    ]);
        exit;
    }

    public static function paginated(array $data, array $pagination, int $status = 200, string $message = 'OK'): void{
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => $status,
            'message' => $message,
            'data' => $data,
            'pagination' => $pagination
        ]);
        exit;
    }
}
