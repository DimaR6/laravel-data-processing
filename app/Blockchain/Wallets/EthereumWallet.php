<?php

namespace App\Blockchain\Wallets;

use App\Blockchain\Contracts\WalletInterface;
use App\Blockchain\Helpers\EncryptionHelper;
use Elliptic\EC;

use Illuminate\Support\Facades\Log;

class EthereumWallet extends EthereumRPC implements WalletInterface
{
    protected $web3;
    protected $eth;

    public function __construct()
    {
        $config = config('bitcoind.ethereum');
        $url = $config['scheme'] . '://' . $config['host'] . ':' . $config['port'];
        parent::__construct($url);
    }

    public function createWallet($label = ''): array
    {
      
        $privateKey = $key->getPrivate()->toString(16, 2);
        $publicKey = substr($key->getPublic(false, 'hex'), 2);
        $address = '0x' . substr($this->keccak256(hex2bin($publicKey)), 24);

        $privateKeyHex = str_pad($privateKey, 64, '0', STR_PAD_LEFT);

        return [
            'address' => $address,
            'privateKey' => $privateKeyHex,
            'publicKey' =>   $publicKey
        ];
    }

    public function getBalance(string $address): float
    {
        $balance = $this->call('eth_getBalance', [$address, 'latest']);
        return hexdec($balance) / 1e18; // Convert from wei to ether
    }

    public function sendTransaction(string $toAddress, float $amount, string $fromAddress = '', string $privateKey = ''): string
    {
        // Convert ether to wei
        $value = '0x' . dechex(intval($amount * 1e18));

        $nonce = $this->call('eth_getTransactionCount', [$fromAddress, 'latest']);
        $nonce = '0x' . dechex(hexdec($nonce));

        $decimalChainId = $this->getNetworkId();
        $hexChainId = '0x' . dechex($decimalChainId);

        // Fetch current gas prices from the network
        $gasPrices = $this->fetchGasPrices();
        $maxPriorityFeePerGasHex = $gasPrices['maxPriorityFeePerGas'];
        $maxFeePerGasHex = $gasPrices['maxFeePerGas'];

        // Gas limit for a simple BNB transfer
        $gasLimitHex = '0x' . dechex(21000);


        // Sign the transaction
        $transaction = new EIP1559Transaction(
            $txParams['nonce'],
            $txParams['maxPriorityFeePerGas'],
            $txParams['maxFeePerGas'],
            $txParams['gasLimit'],
            $txParams['to'],
            $txParams['value'],
            $txParams['data'],
            $txParams['chainId']
        );

        $signedTransaction = $transaction->getRaw($privateKey, $decimalChainId);

        // Send the signed transaction
        return $this->call('eth_sendRawTransaction', ['0x' . $signedTransaction]);
    }

    public function getTransactionStatus(string $txHash): string
    {
        try {
            // Get transaction details
            $transaction = $this->call('eth_getTransactionByHash', [$txHash]);

            if (!$transaction) {
                return 'NOT_FOUND';
            }

            // If the transaction doesn't have a blockNumber, it's pending
            if (!isset($transaction['blockNumber'])) {
                return 'PENDING';
            }

            // Get transaction receipt
            $receipt = $this->call('eth_getTransactionReceipt', [$txHash]);

            if (!$receipt) {
                return 'PENDING';
            }

            // Check the status in the receipt
            $status = hexdec($receipt['status']);

            if ($status === 1) {
                // Get current block number
                $currentBlock = hexdec($this->call('eth_blockNumber'));
                $transactionBlock = hexdec($transaction['blockNumber']);
                $confirmations = $currentBlock - $transactionBlock + 1;

                if ($confirmations >= 12) { // You can adjust this number as needed
                    return 'CONFIRMED';
                } else {
                    return 'SUCCESS';
                }
            } else {
                return 'FAILED';
            }
        } catch (\Exception $e) {
            Log::error('Error getting transaction status: ' . $e->getMessage());
            return 'ERROR';
        }
    }

    public function listSinceBlock($blockhash, $targetConfirmations = 20, $includeWatchonly = false)
    {
        $transactions = [];
        $currentBlockNumber = hexdec($this->call('eth_blockNumber', []));

        if ($blockhash) {
            $startBlockNumber = hexdec($this->call('eth_getBlockByHash', [$blockhash, false])['number']);
        } else {
            $startBlockNumber = max(0, $currentBlockNumber);
        }

        for ($i = $startBlockNumber - $targetConfirmations; $i <= $currentBlockNumber; $i++) {
            $block = $this->call('eth_getBlockByNumber', ['0x' . dechex($i), true]);

            foreach ($block['transactions'] as $txNotFormated) {
                $tx = $this->formatTransaction($txNotFormated);
                $confirmations = $currentBlockNumber - $tx['blockNumber'];

                if (($confirmations >= $targetConfirmations) && !EncryptionHelper::isZeroBalance($tx['value'])) {
                    $transactions[] = [
                        'txid' => $tx['hash'],
                        'address' => $tx['to'],
                        'from_address' => $tx['from'],
                        'amount' => $tx['value'],
                        'confirmations' => $confirmations,
                        'blocknum' => $tx['blockNumber'],
                        'category' => 'receive'
                    ];
                }
            }

            $lastBlock = $block['hash'];
        }

        return [
            'transactions' => $transactions,
            'lastblock' => $lastBlock
        ];
    }

    public function isDepositConfirmed($txid, $requiredConfirmations = 3)
    {
        try {
            // Get transaction details
            $transaction = $this->call('eth_getTransactionByHash', [$txid]);

            if (!$transaction) {
                return false;
            }

            // Get current block number
            $currentBlock = hexdec($this->call('eth_blockNumber'));

            // Check if transaction is confirmed
            if (!isset($transaction['blockNumber'])) {
                return false;
            }

            $transactionBlock = hexdec($transaction['blockNumber']);
            $confirmations = $currentBlock - $transactionBlock + 1;

            return $confirmations >= $requiredConfirmations;
        } catch (\Exception $e) {
            // Handle any errors (e.g., network issues)
            Log::error('Error checking deposit confirmation: ' . $e->getMessage());
            return false;
        }
    }

    public function getNetworkId()
    {
        return $this->call('net_version', []);
    }

    public function getNetworkName($networkId)
    {
        switch ($networkId) {
            case '1':
                return 'Mainnet';
            case '3':
                return 'Ropsten';
            case '4':
                return 'Rinkeby';
            case '5':
                return 'Goerli';
            case '42':
                return 'Kovan';
            case '11155111':
                return 'Sepolia';
            default:
                return 'Unknown network (ID: ' . $networkId . ')';
        }
    }

    public function formatTransaction(array $transaction): array
    {
        $formattedTx = [];

        foreach ($transaction as $key => $value) {
            switch ($key) {
                case 'blockNumber':
                case 'gas':
                case 'gasPrice':
                case 'nonce':
                case 'transactionIndex':
                case 'type':
                case 'chainId':
                case 'v':
                    $formattedTx[$key] = hexdec($value);
                    break;

                case 'value':
                    $formattedTx[$key] = (float)$this->weiToEther(hexdec($value));
                    break;

                case 'input':
                    $formattedTx[$key] = $this->formatInput($value);
                    break;

                default:
                    $formattedTx[$key] = $value;
            }
        }

        return $formattedTx;
    }

    private function weiToEther($wei)
    {
        return $wei / 1e18;
    }

    private function formatInput($input)
    {
        if (strlen($input) > 66) {
            return substr($input, 0, 66) . '...';
        }
        return $input;
    }

    private function keccak256($input)
    {
        return Keccak::hash($input, 256);
    }

    private function fetchGasPrices(): array
    {
        // Fetch the base fee from the latest block
        $latestBlock = $this->call('eth_getBlockByNumber', ['latest', false]);
        $baseFee = hexdec($latestBlock['baseFeePerGas']);

        // Fetch fee history for more accurate priority fee estimation
        $feeHistory = $this->call('eth_feeHistory', [4, 'latest', [25, 75]]);

        $priorityFees = array_map('hexdec', $feeHistory['reward'][0]);
        $avgPriorityFee = array_sum($priorityFees) / count($priorityFees);

        // Calculate max priority fee (can be adjusted based on your needs)
        $maxPriorityFee = $avgPriorityFee * 1.5;

        // Calculate max fee per gas
        $maxFeePerGas = $baseFee * 2 + $maxPriorityFee;

        return [
            'maxPriorityFeePerGas' => '0x' . dechex(intval($maxPriorityFee)),
            'maxFeePerGas' => '0x' . dechex(intval($maxFeePerGas))
        ];
    }
}
