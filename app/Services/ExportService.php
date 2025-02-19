<?php

namespace App\Services;

use Throwable;

class ExportService
{
    private $host;

    public function __construct()
    {
        $this->host = env('EXPORT_SERVICE_HOST');
    }

    public function getFile($data = []): array
    {
        return $this->send($data);
    }

    public function send($data): array
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->host);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
            $request_json = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request_json);
            $response_json = curl_exec($ch);
            curl_close($ch);
            $response = json_decode($response_json, 1);
            if (isset($response['file_path'])) {
                return [
                    'status' => 1,
                    'message' => 'Успешно',
                    'url' => 'https://notify.nbu.uz:4443/fs/' . $response['file_path']
                ];
            }
            return [
                'status' => 0,
                'message' => 'Сервис экспорта не работает',
                'url' => ''
            ];
        } catch (Throwable $exception) {
            return [
                'status' => 0,
                'message' => $exception->getMessage(),
                'url' => ''
            ];
        }

    }
}
