<?php

namespace App\Blockchain\Wallets;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class BinanceSmartChainRPC
{
    protected $client;

    public function __construct($url)
    {
        $this->client = new Client([
            'base_uri' => $url,
            'timeout' => 30,
        ]);
    }

    public function call($method, array $params = [])
    {
        try {
            $response = $this->client->post('/', [
                'json' => [
                    'jsonrpc' => '2.0',
                    'method' => $method,
                    'params' => $params,
                    'id' => 1,
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                throw new \Exception($data['error']['message'], $data['error']['code']);
            }

            return $data['result'];
        } catch (GuzzleException $e) {
            throw new \Exception('RPC request failed: ' . $e->getMessage());
        }
    }
}