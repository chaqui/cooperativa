<?php

namespace App\Services;

use App\Traits\Loggable;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Tymon\JWTAuth\Facades\JWTAuth;

class CatologoService
{
    protected $client;

    use Loggable;
    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => env('CATALOGOS'), // Leer la URL base desde el archivo .env
            'timeout'  => 5.0,
        ]);
    }

    public function getCatalogo($id)
    {

        try {
            $this->log('Obteniendo item');
            $uri = "v1/item/{$id}";
            $this->log('URI completa: ' . $this->client->getConfig('base_uri') . $uri);
            $response = $this->client->request('GET', $uri, [
                'headers' => [
                    'Authorization' => 'Bearer ' . JWTAuth::getToken()
                ]
            ]);
            $data = json_decode($response->getBody(), true);
            $this->log($data);
            return $data;
        } catch (RequestException $e) {
            $this->logError($e->getMessage());
            // Manejar la excepción
            return ['error' => $e->getMessage()];
        }
    }
}
