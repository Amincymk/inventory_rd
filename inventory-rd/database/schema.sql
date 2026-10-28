
CREATE DATABASE IF NOT EXISTS inventory_rd
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE inventory_rd;

-- 1. Users
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Manager', 'Staff')
        NOT NULL DEFAULT 'Staff',
    status ENUM('active', 'inactive')
        NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Categories
CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(120) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. Products
CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(80) NOT NULL UNIQUE,
    product_name VARCHAR(180) NOT NULL,
    description TEXT NULL,
    category_id INT UNSIGNED NULL,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    cost_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    reorder_level INT UNSIGNED NOT NULL DEFAULT 5,
    status ENUM('active', 'inactive')
        NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id)
        REFERENCES categories(id)
        ON DELETE SET NULL,
    INDEX idx_products_name (product_name),
    INDEX idx_products_status (status)
) ENGINE=InnoDB;

-- 4. Suppliers
CREATE TABLE suppliers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(180) NOT NULL,
    email VARCHAR(190) NULL,
    phone VARCHAR(40) NULL,
    address TEXT NULL,
    status ENUM('active', 'inactive')
        NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_suppliers_name (supplier_name)
) ENGINE=InnoDB;

-- 5. Warehouses
CREATE TABLE warehouses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    warehouse_name VARCHAR(150) NOT NULL UNIQUE,
    location VARCHAR(255) NULL,
    status ENUM('active', 'inactive')
        NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 6. Stock per product per warehouse
CREATE TABLE warehouse_stock (
    product_id INT UNSIGNED NOT NULL,
    warehouse_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (product_id, warehouse_id),
    CONSTRAINT fk_ws_product
        FOREIGN KEY (product_id) REFERENCES products(id),
    CONSTRAINT fk_ws_warehouse
        FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
) ENGINE=InnoDB;

-- 7. Purchases
CREATE TABLE purchases (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_reference VARCHAR(60) NOT NULL UNIQUE,
    supplier_id INT UNSIGNED NOT NULL,
    warehouse_id INT UNSIGNED NOT NULL,
    total_cost DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending', 'received', 'cancelled')
        NOT NULL DEFAULT 'pending',
    created_by INT UNSIGNED NULL,
    purchase_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    received_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_purchase_supplier
        FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    CONSTRAINT fk_purchase_warehouse
        FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    CONSTRAINT fk_purchase_user
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL,
    INDEX idx_purchase_date (purchase_date)
) ENGINE=InnoDB;

-- 8. Purchase items
CREATE TABLE purchase_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    unit_cost DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(14,2)
        GENERATED ALWAYS AS (quantity * unit_cost) STORED,
    CONSTRAINT fk_pi_purchase
        FOREIGN KEY (purchase_id) REFERENCES purchases(id),
    CONSTRAINT fk_pi_product
        FOREIGN KEY (product_id) REFERENCES products(id),
    UNIQUE KEY uq_purchase_product (purchase_id, product_id)
) ENGINE=InnoDB;

-- 9. Sales
CREATE TABLE sales (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_reference VARCHAR(60) NOT NULL UNIQUE,
    total_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    status ENUM('completed', 'cancelled')
        NOT NULL DEFAULT 'completed',
    created_by INT UNSIGNED NULL,
    sale_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cancelled_at DATETIME NULL,
    CONSTRAINT fk_sale_user
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL,
    INDEX idx_sale_date (sale_date),
    INDEX idx_sale_status (status)
) ENGINE=InnoDB;

-- 10. Sale items
CREATE TABLE sale_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    warehouse_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(14,2)
        GENERATED ALWAYS AS (quantity * unit_price) STORED,
    CONSTRAINT fk_si_sale
        FOREIGN KEY (sale_id) REFERENCES sales(id),
    CONSTRAINT fk_si_product
        FOREIGN KEY (product_id) REFERENCES products(id),
    CONSTRAINT fk_si_warehouse
        FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    INDEX idx_sale_items_product (product_id)
) ENGINE=InnoDB;

-- 11. Stock movement history
CREATE TABLE stock_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    warehouse_id INT UNSIGNED NOT NULL,
    movement_type ENUM(
        'purchase',
        'sale',
        'transfer_in',
        'transfer_out',
        'adjustment_in',
        'adjustment_out',
        'sale_reversal',
        'purchase_reversal'
    ) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    reference_type VARCHAR(40) NULL,
    reference_id INT UNSIGNED NULL,
    created_by INT UNSIGNED NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sm_product
        FOREIGN KEY (product_id) REFERENCES products(id),
    CONSTRAINT fk_sm_warehouse
        FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    CONSTRAINT fk_sm_user
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL,
    INDEX idx_movement_product_date (product_id, created_at),
    INDEX idx_movement_reference (reference_type, reference_id)
) ENGINE=InnoDB;

-- 12. Activity logs
CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    INDEX idx_activity_date (created_at)
) ENGINE=InnoDB;
