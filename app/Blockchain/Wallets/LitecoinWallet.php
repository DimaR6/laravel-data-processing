<?php

namespace App\Blockchain\Wallets;

use App\Blockchain\Contracts\WalletInterface;
use Denpa\Bitcoin\Client as BitcoinClient;

class LitecoinWallet implements WalletInterface
{
    protected $litecoinClient;

    public function __construct()
    {
        $this->litecoinClient = new BitcoinClient(config('bitcoind.litecoin'));
    }

    public function createWallet($label=''): array
    {
        $address = $this->litecoinClient->getnewaddress($label);

        return [
            'address' => $address
        ];
    }

    public function getBalance(string $address): float
    {
        return $this->litecoinClient->getreceivedbyaddress($address);
    }

    public function sendTransaction(string $toAddress, float $amount): string
    {
        return $this->litecoinClient->sendtoaddress($toAddress, $amount);
    }

    public function getTransactionStatus(string $txHash): string
    {
        $transaction = $this->litecoinClient->gettransaction($txHash);
        return $transaction['confirmations'] > 6 ? 'confirmed' : 'pending';
    }

    public function listSinceBlock($blockhash, $targetConfirmations = 6, $includeWatchonly = false)
    {
        return $this->litecoinClient->listsinceblock($blockhash, $targetConfirmations, $includeWatchonly);
    }

    public function isDepositConfirmed($txid, $requiredConfirmations = 6)
    {
        $transaction = $this->litecoinClient->gettransaction($txid);
        return $transaction['confirmations'] >= $requiredConfirmations;
    }
}