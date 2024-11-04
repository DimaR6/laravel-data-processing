<?php

namespace App\Blockchain\Wallets;

class EthereumRPC
{
    protected $url;
    protected $id = 0;

    public function __construct($url)
    {
        $this->url = $url;
    }

    protected function call($method, $params = [])
    {
        $this->id++;
        $data = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => $this->id
        ];

        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        if ($response === false) {
            throw new \Exception('CURL Error: ' . curl_error($ch));
        }
        curl_close($ch);

        $decoded = json_decode($response, true);
        if (isset($decoded['error'])) {
            throw new \Exception('ETH RPC Error: ' . $decoded['error']['message']);
        }

        return $decoded['result'];
    }
}