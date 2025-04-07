/* Script de création des tables pour le e-commerce de meubles */

-- Création des tables
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    address TEXT,
    postal_code VARCHAR(10),
    city VARCHAR(50),
    phone VARCHAR(20),
    role VARCHAR(20) NOT NULL DEFAULT 'client',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    image_path VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    category_id INT NOT NULL REFERENCES categories(id),
    image_path VARCHAR(255),
    dimensions VARCHAR(100),
    weight DECIMAL(10, 2),
    material VARCHAR(100),
    color VARCHAR(50),
    featured BOOLEAN DEFAULT false,
    discount_percent INT DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP,
    deleted BOOLEAN DEFAULT false
);

CREATE TABLE IF NOT EXISTS images_products (
    id SERIAL PRIMARY KEY,
    product_id INT NOT NULL REFERENCES products(id),
    image_path VARCHAR(255) NOT NULL,
    is_main BOOLEAN DEFAULT false,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id),
    total_amount DECIMAL(10, 2) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    shipping_city VARCHAR(50) NOT NULL,
    shipping_postal_code VARCHAR(10) NOT NULL,
    shipping_method VARCHAR(50) NOT NULL DEFAULT 'standard',
    payment_method VARCHAR(50),
    paid BOOLEAN DEFAULT false,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE IF NOT EXISTS order_lines (
    id SERIAL PRIMARY KEY,
    order_id INT NOT NULL REFERENCES orders(id),
    product_id INT NOT NULL REFERENCES products(id),
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS payments (
    id SERIAL PRIMARY KEY,
    order_id INT NOT NULL REFERENCES orders(id),
    amount DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(100),
    status VARCHAR(20) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Insertion de données initiales
-- Admin
INSERT INTO users (username, email, password, first_name, last_name, role)
VALUES ('admin', 'admin@furniture.be', '$2y$10$8MmZkVg5b7VqZQTEP8JX2eA93nYJZ9Uqm5nKJ7O5pVDldZw4cZPpe', 'Admin', 'Système', 'admin');

-- Catégories
INSERT INTO categories (name, description)
VALUES ('Salon', 'Meubles pour le salon et le séjour'),
       ('Chambre', 'Lits, armoires et tables de chevet'),
       ('Cuisine', 'Tables, chaises et meubles de rangement pour la cuisine'),
       ('Bureau', 'Bureaux et chaises ergonomiques pour le travail'),
       ('Jardin', 'Mobilier d''extérieur pour terrasse et jardin'); 