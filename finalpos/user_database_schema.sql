-- 1. User Management
CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role_id INT,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- 2. Product & Stock Management
-- ... (rest of the schema is the same)

-- 5. Basic Accounts Handling
CREATE TABLE chart_of_accounts (
    account_id INT AUTO_INCREMENT PRIMARY KEY,
    account_name VARCHAR(100) NOT NULL UNIQUE COMMENT 'Cash, Bank, Sales Revenue, Inventory, COGS, etc.',
    account_type ENUM('Asset', 'Liability', 'Equity', 'Revenue', 'Expense') NOT NULL
);

CREATE TABLE accounts_ledger (
    ledger_id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_date DATETIME NOT NULL,
    narration TEXT,
    debit_account_id INT NOT NULL COMMENT 'Account Debited',
    credit_account_id INT NOT NULL COMMENT 'Account Credited',
    amount DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (debit_account_id) REFERENCES chart_of_accounts(account_id),
    FOREIGN KEY (credit_account_id) REFERENCES chart_of_accounts(account_id)
);

-- Initial Data
INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(1, 'Admin'),
(2, 'Manager'),
(3, 'Cashier');

INSERT INTO `chart_of_accounts` (`account_id`, `account_name`, `account_type`) VALUES
(1, 'Cash', 'Asset'),
(2, 'Inventory', 'Asset'),
(3, 'Accounts Payable', 'Liability'),
(4, 'Sales Revenue', 'Revenue'),
(5, 'Cost of Goods Sold', 'Expense'),
(6, 'Sales Returns', 'Revenue');
