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
    UNIQUE KEY uq_products_category_name (category_id, name),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE IF NOT EXISTS delivery_coverages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    area_name VARCHAR(120) NOT NULL,
    rider_id INT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    UNIQUE KEY uq_delivery_coverages_area (area_name),
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
('Admin User', 'admin@example.com', '$2y$12$kg1/VMoX8K4LXqad/nUlo.9M8uo023DoBLSB.IbpcUs.ky9NXPAFW', 'admin'),
('Staff User', 'staff@example.com', '$2y$12$LvsipCcMR3V1i0DyLTz7CunxFC.thlggvw7GnT6..7XDDZaqnubwW', 'staff'),
('Rider User', 'rider@example.com', '$2y$12$nWGsDIBA9.no10i.68wezO45Be4bunoYDrdqXOxJB6FkykI/gSbSW', 'rider'),
('Customer User', 'customer@example.com', '$2y$12$riyt5ikqJe6SSa6JLsrg5.OapS5jH3x7ZrIT8bhOk4..i9hoaYyWS', 'customer')
ON DUPLICATE KEY UPDATE name = VALUES(name), password = VALUES(password), role = VALUES(role);

INSERT INTO categories (name)
VALUES ('Pizza'), ('Seafood'), ('Burger'), ('Drinks')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO products (category_id, name, description, price, image, status)
VALUES
(1, 'Classic Pizza', 'Loaded with mozzarella, tomato sauce, and herbs.', 450.00, 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=800&q=80', 'active'),
(1, 'Margherita Pizza', 'Classic mozzarella and basil pizza.', 470.00, 'https://images.unsplash.com/photo-1548365328-9f547fb9587c?auto=format&fit=crop&w=800&q=80', 'active'),
(1, 'BBQ Chicken Pizza', 'Smoky BBQ chicken with peppers and cheese.', 520.00, 'https://images.unsplash.com/photo-1552539618-7eec9b4d9f5a?auto=format&fit=crop&w=800&q=80', 'active'),
(2, 'Tuna Seafood Bowl', 'Fresh seafood bowl with rice, greens, and savory sauce.', 520.00, 'https://images.unsplash.com/photo-1559847844-5315695dadae?auto=format&fit=crop&w=800&q=80', 'active'),
(2, 'Shrimp Platter', 'Crispy shrimp with side salad and signature sauce.', 560.00, 'https://images.unsplash.com/photo-1562967916-eb82221dfb92?auto=format&fit=crop&w=800&q=80', 'active'),
(2, 'Grilled Salmon', 'Flame-grilled salmon with lemon butter glaze.', 610.00, 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?auto=format&fit=crop&w=800&q=80', 'active'),
(3, 'Beef Burger', 'Juicy beef burger with crisp lettuce and cheese.', 390.00, 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=800&q=80', 'active'),
(3, 'Chicken Burger', 'Crispy chicken burger with cheddar and slaw.', 410.00, 'https://images.unsplash.com/photo-1550547660-d9450f859349?auto=format&fit=crop&w=800&q=80', 'active'),
(3, 'Double Cheeseburger', 'Stacked cheese burger with caramelized onions.', 480.00, 'https://images.unsplash.com/photo-1561758033-d89a9ad46330?auto=format&fit=crop&w=800&q=80', 'active'),
(4, 'Iced Coffee', 'Refreshing iced coffee for a cooling break.', 220.00, 'https://images.unsplash.com/photo-1497636577773-f1231844b336?auto=format&fit=crop&w=800&q=80', 'active'),
(4, 'Fresh Lemonade', 'Fresh homemade lemonade with citrus aroma.', 180.00, 'https://images.unsplash.com/photo-1546173159-315724a31696?auto=format&fit=crop&w=800&q=80', 'active'),
(4, 'Mango Shake', 'Creamy mango shake blended with tropical flavor.', 240.00, 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?auto=format&fit=crop&w=800&q=80', 'active')
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), price = VALUES(price), image = VALUES(image), category_id = VALUES(category_id), status = VALUES(status);

INSERT INTO delivery_coverages (area_name, rider_id, status)
VALUES
('Parklands', 3, 'active'),
('Bocaue', 3, 'active'),
('Meycauayan', 3, 'active')
ON DUPLICATE KEY UPDATE area_name = area_name;
