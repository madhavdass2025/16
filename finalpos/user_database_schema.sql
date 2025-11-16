
-- 1. User Management
CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role_id INT,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- 2. Product & Stock Management
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL,
    mrp DECIMAL(10, 2) NOT NULL,
    tax_rate DECIMAL(4, 2) NOT NULL COMMENT 'e.g., 0.05 for 5%',
    category_id INT,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

CREATE TABLE stock_batches (
    batch_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    batch_number VARCHAR(50) NOT NULL,
    purchase_price DECIMAL(10, 2) NOT NULL COMMENT 'Cost per unit for COGS',
    selling_price DECIMAL(10, 2) NOT NULL COMMENT 'Current selling price of this batch',
    current_qty INT NOT NULL DEFAULT 0,
    expiry_date DATE NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    UNIQUE KEY unique_batch_product (product_id, batch_number)
);

CREATE TABLE reorder_levels (
    reorder_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL UNIQUE,
    reorder_level INT NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- 3. Supplier & Purchase Management
CREATE TABLE suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(15),
    gst_number VARCHAR(15)
);

CREATE TABLE purchase_orders (
    po_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    order_date DATE NOT NULL,
    status ENUM('Pending', 'Received', 'Cancelled') NOT NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id)
);

CREATE TABLE purchase_invoices (
    purchase_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    invoice_date DATE NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    payment_status ENUM('Paid', 'Credit') NOT NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id)
);

CREATE TABLE purchase_items (
    p_item_id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT NOT NULL COMMENT 'Links to Purchase Invoice/Bill Header',
    batch_id INT NOT NULL COMMENT 'Links to the specific batch created/updated',
    quantity_purchased INT NOT NULL,
    unit_cost DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (purchase_id) REFERENCES purchase_invoices(purchase_id),
    FOREIGN KEY (batch_id) REFERENCES stock_batches(batch_id)
);

CREATE TABLE purchase_returns (
    return_id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT NOT NULL COMMENT 'The original Purchase Invoice being returned against',
    return_date DATE NOT NULL,
    amount_credited DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (purchase_id) REFERENCES purchase_invoices(purchase_id)
);

CREATE TABLE purchase_return_items (
    return_item_id INT AUTO_INCREMENT PRIMARY KEY,
    return_id INT NOT NULL,
    batch_id INT NOT NULL,
    quantity_returned INT NOT NULL,
    FOREIGN KEY (return_id) REFERENCES purchase_returns(return_id),
    FOREIGN KEY (batch_id) REFERENCES stock_batches(batch_id)
);

-- 4. Sales, Invoice & Payment Management
CREATE TABLE customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(15),
    address TEXT
);

CREATE TABLE pets (
    pet_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    pet_name VARCHAR(100) NOT NULL,
    species VARCHAR(50) COMMENT 'Dog, Cat, Bird, etc.',
    breed VARCHAR(100),
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
);

CREATE TABLE sales_invoices (
    invoice_id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    invoice_date DATETIME NOT NULL,
    customer_id INT NOT NULL,
    user_id INT NOT NULL COMMENT 'Staff who processed the sale',
    net_amount DECIMAL(10, 2) NOT NULL,
    payment_status ENUM('Paid', 'Credit', 'Cancelled') NOT NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE invoice_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    batch_id INT NOT NULL COMMENT 'Specific batch sold',
    quantity_sold INT NOT NULL,
    selling_price_at_sale DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (invoice_id) REFERENCES sales_invoices(invoice_id),
    FOREIGN KEY (batch_id) REFERENCES stock_batches(batch_id)
);

CREATE TABLE sales_returns (
    s_return_id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL COMMENT 'Original Sale Invoice',
    return_date DATE NOT NULL,
    refund_amount DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (invoice_id) REFERENCES sales_invoices(invoice_id)
);

CREATE TABLE sales_return_items (
    s_return_item_id INT AUTO_INCREMENT PRIMARY KEY,
    s_return_id INT NOT NULL,
    batch_id INT NOT NULL,
    quantity_returned INT NOT NULL,
    FOREIGN KEY (s_return_id) REFERENCES sales_returns(s_return_id),
    FOREIGN KEY (batch_id) REFERENCES stock_batches(batch_id)
);

CREATE TABLE payments_collection (
    collection_id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    payment_date DATETIME NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('Cash', 'Card', 'UPI', 'Credit Collection') NOT NULL,
    FOREIGN KEY (invoice_id) REFERENCES sales_invoices(invoice_id)
);

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
