<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class CatologoService
{
    protected $client;

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
            $response = $this->client->request('GET', "v1/item/{$id}");

            $data = json_decode($response->getBody(), true);
            return $data;
        } catch (RequestException $e) {
            // Manejar la excepción
            return ['error' => $e->getMessage()];
        }
    }
}
