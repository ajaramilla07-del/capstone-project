CREATE DATABASE IF NOT EXISTS lloyd_frontera;
USE lloyd_frontera;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff', 'rider', 'customer') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    UNIQUE KEY uq_categories_name (name)
);

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE IF NOT EXISTS delivery_coverages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    area_name VARCHAR(120) NOT NULL,
    rider_id INT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    FOREIGN KEY (rider_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    delivery_coverage_id INT NULL,
    delivery_address VARCHAR(255) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Pending',
    rider_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (delivery_coverage_id) REFERENCES delivery_coverages(id),
    FOREIGN KEY (rider_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS order_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

INSERT INTO users (name, email, password, role)
VALUES
('Admin User', 'admin@example.com', '$2y$12$nxDRjge13ZwLIcYnx/v0e.4CIdPqfHxRnsQk5ZhRIwUKCpzmFFNjC', 'admin'),
('Staff User', 'staff@example.com', '$2y$12$nxDRjge13ZwLIcYnx/v0e.4CIdPqfHxRnsQk5ZhRIwUKCpzmFFNjC', 'staff'),
('Rider User', 'rider@example.com', '$2y$12$nxDRjge13ZwLIcYnx/v0e.4CIdPqfHxRnsQk5ZhRIwUKCpzmFFNjC', 'rider'),
('Customer User', 'customer@example.com', '$2y$12$nxDRjge13ZwLIcYnx/v0e.4CIdPqfHxRnsQk5ZhRIwUKCpzmFFNjC', 'customer')
ON DUPLICATE KEY UPDATE email = email;

INSERT INTO categories (name)
VALUES ('Pizza'), ('Seafood'), ('Burger'), ('Drinks')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO products (category_id, name, description, price, image, status)
VALUES
(1, 'Pizza', 'Classic pizza', 1000.00, 'https://images.unsplash.com/photo-1513104890138-7c749659a591', 'active'),
(2, 'Salad', 'Fresh salad', 800.00, 'https://images.unsplash.com/photo-1546793665-c74683f339c1', 'active'),
(3, 'Burger', 'Tasty burger', 500.00, 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd', 'active'),
(4, 'Sushi', 'Fresh sushi platter', 1500.00, 'https://images.unsplash.com/photo-1579871494447-9811cf80d66c', 'active')
ON DUPLICATE KEY UPDATE name = name;

INSERT INTO delivery_coverages (area_name, rider_id, status)
VALUES
('Parklands', 3, 'active'),
('Bocaue', 3, 'active'),
('Meycauayan', 3, 'active')
ON DUPLICATE KEY UPDATE area_name = area_name;
