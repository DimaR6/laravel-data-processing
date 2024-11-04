<?php

namespace App\Blockchain\Wallets;

use App\Blockchain\Contracts\WalletInterface;
use Denpa\Bitcoin\Client as BitcoinClient;

class BitcoinWallet implements WalletInterface
{
    protected $bitcoinClient;

    public function __construct()
    {
        $this->bitcoinClient = new BitcoinClient(config('bitcoind.default'));
    }

    public function createWallet($label=''): array
    {
        $address = $this->bitcoinClient->getnewaddress($label);
        return [
            'address' => $address
        ];

    }

    public function getBalance(string $address): float
    {
        return $this->bitcoinClient->getreceivedbyaddress($address);
    }

    public function sendTransaction(string $toAddress, float $amount): string
    {
        return $this->bitcoinClient->sendtoaddress($toAddress, $amount);
    }

    public function getTransactionStatus(string $txHash): string
    {
        $transaction = $this->bitcoinClient->gettransaction($txHash);
        return $transaction['confirmations'] > 6 ? 'confirmed' : 'pending';
    }

    public function listSinceBlock($blockhash, $targetConfirmations = 3, $includeWatchonly = false)
    {
        return $this->bitcoinClient->listsinceblock($blockhash, $targetConfirmations, $includeWatchonly);
    }

    public function isDepositConfirmed($txid, $requiredConfirmations = 3)
    {
        $transaction = $this->bitcoinClient->gettransaction($txid);
        return $transaction['confirmations'] >= $requiredConfirmations;
    }
    
}