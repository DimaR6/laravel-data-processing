<?php

namespace App\Blockchain\Factory;

use App\Blockchain\Wallets\{
    BitcoinWallet,
    LitecoinWallet,
    BinanceSmartChainWallet,
    TronWallet,
    EthereumWallet
};

class WalletFactory
{
    public function createWalletInstance($type)
    {
        switch ($type) {
            case 'BTC':
                return new BitcoinWallet();
            case 'LTC':
                return new LitecoinWallet();
            case 'BNB':
                return new BinanceSmartChainWallet();
            case 'TRX':
                return new TronWallet();
            case 'ETH':
                return new EthereumWallet();

            default:
                throw new \InvalidArgumentException("Unsupported wallet type: {$type}");
        }
    }
}
