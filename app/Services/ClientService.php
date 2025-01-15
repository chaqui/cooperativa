<?php

namespace App\Services;

use App\Models\Client;


class ClientService {
    public function createClient($data) {
        $client = Client::generateCliente($data);
        $client->save();
    }

    public function updateClient($data, $id) {
        $client = Client::find($id);
        $client->name = $data['name'];
        $client->email = $data['email'];
        $client->phone = $data['phone'];
        $client->save();
    }

    public function deleteClient($id) {
        $client = Client::find($id);
        $client->delete();
    }

    public function getClient($id) {
        return Client::find($id);
    }

    public function getClients() {
        return Client::all();
    }

    public function getCuentasBancarias($id) {
        $client = Client::find($id);
        return $client->cuentasBancarias;
    }
}
