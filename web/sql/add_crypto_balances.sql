-- Migration: add per-coin balances and withdrawal method
-- Run once against your MySQL database.

ALTER TABLE users
    ADD COLUMN btc_balance DECIMAL(18,8) NOT NULL DEFAULT 0.00000000,
    ADD COLUMN eth_balance DECIMAL(18,8) NOT NULL DEFAULT 0.00000000;

ALTER TABLE withdrawal_requests
    ADD COLUMN method VARCHAR(10) NOT NULL DEFAULT 'crypto';
