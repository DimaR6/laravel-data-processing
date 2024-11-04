<?php

namespace App\Blockchain\Wallets;

use App\Blockchain\Contracts\WalletInterface;
use IEXBase\TronAPI\Tron;
use IEXBase\TronAPI\Provider\HttpProvider;
use IEXBase\TronAPI\TronManager;
use IEXBase\TronAPI\Exception\TronException;

class TronWallet implements WalletInterface
{
    protected $tronClient;
    protected $tronManager;

    public function __construct()
    {
 

        $nodeUrl = $config['scheme'] . '://' .
            $config['host'] . ':' .
            $config['port'];


        $fullNode = new HttpProvider($nodeUrl);
        $solidityNode = new HttpProvider($nodeUrl);
        $eventServer = new HttpProvider($nodeUrl);

        $this->tronClient = new Tron($fullNode, $solidityNode, $eventServer);

        // Define the providers array
        $providers = [
            'fullNode'      =>  $nodeUrl,
            'solidityNode'  =>  $nodeUrl,
            'eventServer'   =>  $nodeUrl,
            'explorer'      =>  null,
            'signServer'    =>  null // or set your custom sign server URL
        ];

        // Initialize the TronManager
        try {
            $this->tronManager = new TronManager(null, $providers);
        } catch (TronException $e) {
            // Handle the exception
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function createWallet($label = ''): array
    {
        $account = $this->tronClient->createAccount();

        $address = $account->getAddress(true); // Returns base58 address
        $privateKey = $account->getPrivateKey();

        return [
            'address' => $address,
            'privateKey' => $privateKey
        ];
    }

    // seems like not working
    public function getBalance(string $address): float
    {
        $fromTron = true;
        $account = $this->getAccount($address);

        if (!array_key_exists('balance', $account)) {
            return 0;
        }

        return ($fromTron == true ?
            $this->tronClient->fromTron($account['balance']) :
            $account['balance']);
    }

    public function sendTransaction(string $toAddress, float $amount, string $fromAddress = '', string $privateKey = ''): string
    {
        $this->tronClient->setPrivateKey($privateKey);

        $transaction = $this->tronClient->sendTransaction($toAddress, $amount, $fromAddress);
        return $transaction['txid'];
    }

    public function getTransactionStatus(string $txHash): string
    {
        $transaction = $this->tronClient->getTransaction($txHash);
        return $transaction['ret'][0]['contractRet'] === 'SUCCESS' ? 'confirmed' : 'pending';
    }

    // need to check
    public function listSinceBlock($blockhash, $targetConfirmations = 3, $includeWatchonly = false)
    {
        // Get the block number for the given blockhash
        $block = [];

        if (!empty($blockhash)) {
            $block = $this->tronClient->getBlockByHash($blockhash);
        }

        $currentBlock = $this->tronClient->getCurrentBlock();

        $currentBlockNum = $currentBlock['block_header']['raw_data']['number'];
        $startBlockNum = $block['block_header']['raw_data']['number'] ?? $currentBlockNum;

        $transactions = [];

        // Fetch transactions for each block since the start block
        for ($i = $startBlockNum + 1; $i <= $currentBlockNum; $i++) {
            $blockInfo = $this->tronClient->getBlockByNumber($i);

            $blockTransactions = $blockInfo['transactions'] ?? [];

            foreach ($blockTransactions as $tx) {
                $confirmations = $currentBlockNum - $i + 1;

                $addressCondition = (isset($tx['raw_data']['contract'][0]['parameter']['value']['to_address']) && !empty($tx['raw_data']['contract'][0]['parameter']['value']['to_address']));
                // Only include transactions with the required confirmations
                if ($confirmations >= $targetConfirmations && ($addressCondition)) {
                    $amount = isset($tx['raw_data']['contract'][0]['parameter']['value']['amount'])
                        ? $this->tronClient->fromTron($tx['raw_data']['contract'][0]['parameter']['value']['amount'])
                        : 0;

                    $transactions[] = [
                        'txid' => $tx['txID'],
                        'address' => $this->tronClient->fromHex($tx['raw_data']['contract'][0]['parameter']['value']['to_address']) ?? null,
                        'from_address' => $tx['raw_data']['contract'][0]['parameter']['value']['owner_address'] ?? null,
                        'amount' => $amount,
                        'confirmations' => $confirmations,
                        'blocknum' => $i,
                        'type' => $tx['raw_data']['contract'][0]['type'],
                        'category' => 'receive'
                    ];
                }
            }
        }

        return [
            'transactions' => $transactions,
            'lastblock' => $currentBlock['blockID'] ?? ''
        ];
    }

    // not working
    public function isDepositConfirmed($txid, $requiredConfirmations = 3)
    {
        $transaction = $this->tronClient->getTransactionInfo($txid);
        return isset($transaction['blockNumber']) &&
            ($this->tronClient->getCurrentBlock()['block_header']['raw_data']['number'] - $transaction['blockNumber']) >= $requiredConfirmations;
    }

    public function getAccount(string $address = null): array
    {
        $address = (!is_null($address) ? $this->tronClient->toHex($address) : $this->tronClient->address['hex']);

        return $this->tronManager->request('wallet/getaccount', [
            'address'   =>  $address
        ]);
    }

    // it can be usefull for trc usdt balance
    // where I can get usdt trc20 id
    public function getTokenBalance(int $tokenId, string $address, bool $fromTron = false)
    {
        $account = $this->getAccount($address);

        if (isset($account['assetV2']) and !empty($account['assetV2'])) {
            $value = array_filter($account['assetV2'], function ($item) use ($tokenId) {
                return $item['key'] == $tokenId;
            });

            if (empty($value)) {
                throw new TronException('Token id not found');
            }

            $first = array_shift($value);
            return ($fromTron == true ? $this->tronClient->fromTron($first['value']) : $first['value']);
        }

        return 0;
    }
}
