<?php

namespace App\Blockchain\Contracts;

interface WalletInterface
{
/**
     * Create a new wallet and return its identifier.
     *
     * @return array The wallet identifier
     */
    public function createWallet(): array;

    /**
     * Get the balance of a wallet.
     *
     * @param string $address The wallet address
     * @return float The wallet balance
     */
    public function getBalance(string $address): float;

    /**
     * Send a transaction from the wallet.
     *
     * @param string $toAddress The recipient's address
     * @param float $amount The amount to send
     * @return string The transaction hash
     */
    public function sendTransaction(string $toAddress, float $amount): string;

    /**
     * Get the status of a transaction.
     *
     * @param string $txHash The transaction hash
     * @return string The transaction status
     */
    public function getTransactionStatus(string $txHash): string;

    /**
     * List transactions since a specific block.
     *
     * @param string|null $blockhash The block hash to start from (null for genesis block)
     * @param int $targetConfirmations The number of confirmations to wait for
     * @param bool $includeWatchonly Whether to include watch-only addresses
     * @return array List of transactions
     */
    public function listSinceBlock(string $blockhash, int $targetConfirmations, bool $includeWatchonly);

    /**
     * Check if a deposit transaction is confirmed.
     *
     * @param string $txid The transaction ID
     * @param int $requiredConfirmations The number of required confirmations
     * @return bool Whether the deposit is confirmed
     */
    public function isDepositConfirmed(string $txid, int $requiredConfirmations);
}