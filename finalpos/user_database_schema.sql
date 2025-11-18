-- 1. User Management
-- ... (user tables remain the same)

-- 2. Product & Stock Management
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE `products` (
  `Mid` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(200) NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  `UnitPrice` DECIMAL(10, 2) NOT NULL,
  `taxExcluded_price` DECIMAL(10, 2) NOT NULL,
  `taxAmount` DECIMAL(10, 2) NOT NULL,
  `hsn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `taxable` varchar(10) NOT NULL DEFAULT 'yes',
  `Itax` varchar(10) NOT NULL,
  `cess` varchar(10) NOT NULL,
  `submittedby` varchar(100) NOT NULL,
  `submitteddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(100) NOT NULL DEFAULT 'available'
);

CREATE TABLE stock_batches (
    batch_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    batch_number VARCHAR(50) NOT NULL,
    purchase_price DECIMAL(10, 2) NOT NULL,
    selling_price DECIMAL(10, 2) NOT NULL,
    current_qty INT NOT NULL DEFAULT 0,
    expiry_date DATE NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(Mid),
    UNIQUE KEY unique_batch_product (product_id, batch_number)
);

CREATE TABLE reorder_levels (
    reorder_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL UNIQUE,
    reorder_level INT NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(Mid)
);

-- ... (rest of the schema remains the same, with foreign keys pointing to products.Mid)
