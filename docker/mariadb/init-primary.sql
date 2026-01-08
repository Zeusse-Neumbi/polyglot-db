-- MariaDB Primary Initialization
-- Creates replication user and grants permissions

-- Create replication user for slave
CREATE USER IF NOT EXISTS 'replication' @'%' IDENTIFIED BY 'replication_password';

GRANT REPLICATION SLAVE ON *.* TO 'replication' @'%';

-- Grant Laravel user full access to the database
-- Create Laravel user explicitly to ensure it replicates
CREATE USER IF NOT EXISTS 'laravel' @'%' IDENTIFIED BY 'secret';

GRANT ALL PRIVILEGES ON tp_bd.* TO 'laravel' @'%';

FLUSH PRIVILEGES;