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

CREATE TABLE stock_adjustment_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    user_id INT NOT NULL,
    adjustment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    old_qty INT NOT NULL,
    new_qty INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    FOREIGN KEY (batch_id) REFERENCES stock_batches(batch_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- 5. Basic Accounts Handling
-- ... (rest of the schema is the same)
