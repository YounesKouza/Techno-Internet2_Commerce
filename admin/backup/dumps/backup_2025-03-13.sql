-- ============================================
-- Script de création de la base de données
-- pour le marketplace de mobilier rénové
-- ============================================

-- Suppression des tables existantes (attention : cascade supprime aussi les dépendances)
DROP TABLE IF EXISTS payments CASCADE;
DROP TABLE IF EXISTS order_lines CASCADE;
DROP TABLE IF EXISTS orders CASCADE;
DROP TABLE IF EXISTS images_products CASCADE;
DROP TABLE IF EXISTS products CASCADE;
DROP TABLE IF EXISTS categories CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- =========================
-- 1. Création de la table users
-- =========================
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'client',  -- Valeurs possibles : 'client' ou 'admin'
    adresse TEXT,
    telephone VARCHAR(20),
    date_inscription TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- =========================
-- 2. Création de la table categories
-- =========================
CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT
);

-- =========================
-- 3. Création de la table products
-- =========================
CREATE TABLE products (
    id SERIAL PRIMARY KEY,
    titre VARCHAR(150) NOT NULL,
    description TEXT,
    prix NUMERIC(10,2) NOT NULL CHECK (prix > 0),
    stock INTEGER NOT NULL DEFAULT 0,
    categorie_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
    image_principale VARCHAR(255),
    actif BOOLEAN NOT NULL DEFAULT TRUE,
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- =========================
-- 4. Création de la table images_products (pour plusieurs images par produit)
-- =========================
CREATE TABLE images_products (
    id SERIAL PRIMARY KEY,
    produit_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    url_image VARCHAR(255) NOT NULL
);

-- =========================
-- 5. Création de la table orders (commandes)
-- =========================
CREATE TABLE orders (
    id SERIAL PRIMARY KEY,
    utilisateur_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    date_commande TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    montant_total NUMERIC(10,2) NOT NULL,
    statut VARCHAR(50) NOT NULL DEFAULT 'en cours'  -- Ex: 'en cours', 'expédiée', 'livrée', 'annulée'
);

-- =========================
-- 6. Création de la table order_lines (détail des commandes)
-- =========================
CREATE TABLE order_lines (
    id SERIAL PRIMARY KEY,
    order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    produit_id INTEGER NOT NULL REFERENCES products(id),
    quantite INTEGER NOT NULL CHECK (quantite > 0),
    prix_unitaire NUMERIC(10,2) NOT NULL
);

-- =========================
-- 7. Création de la table payments (facultatif, pour gérer les paiements)
-- =========================
CREATE TABLE payments (
    id SERIAL PRIMARY KEY,
    order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    mode_paiement VARCHAR(50) NOT NULL,
    reference_transaction VARCHAR(100),
    statut VARCHAR(50) NOT NULL DEFAULT 'en attente',  -- Ex: 'en attente', 'réussi', 'échoué'
    date_paiement TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- Fonctions PL/pgSQL pour les opérations métiers
-- ============================================

-- Fonction pour mettre à jour le stock d'un produit lors d'une commande
CREATE OR REPLACE FUNCTION update_product_stock(p_produit_id INTEGER, p_quantite INTEGER)
RETURNS VOID AS $$
BEGIN
    UPDATE products
    SET stock = stock - p_quantite
    WHERE id = p_produit_id AND stock >= p_quantite;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Stock insuffisant pour le produit %', p_produit_id;
    END IF;
END;
$$ LANGUAGE plpgsql;

-- Fonction pour créer une commande et retourner son ID
CREATE OR REPLACE FUNCTION create_order(p_utilisateur_id INTEGER, p_montant_total NUMERIC)
RETURNS INTEGER AS $$
DECLARE
    new_order_id INTEGER;
BEGIN
    INSERT INTO orders(utilisateur_id, montant_total)
    VALUES (p_utilisateur_id, p_montant_total)
    RETURNING id INTO new_order_id;
    RETURN new_order_id;
END;
$$ LANGUAGE plpgsql;

-- Fonction pour ajouter une ligne de commande et mettre à jour le stock du produit
CREATE OR REPLACE FUNCTION add_order_line(p_order_id INTEGER, p_produit_id INTEGER, p_quantite INTEGER, p_prix_unitaire NUMERIC)
RETURNS VOID AS $$
BEGIN
    INSERT INTO order_lines(order_id, produit_id, quantite, prix_unitaire)
    VALUES (p_order_id, p_produit_id, p_quantite, p_prix_unitaire);
    
    -- Mise à jour du stock du produit
    PERFORM update_product_stock(p_produit_id, p_quantite);
END;
$$ LANGUAGE plpgsql;

-- Exemple d'utilisation :
-- Pour créer une commande, vous pouvez exécuter :
--   SELECT create_order(1, 250.00);
-- Puis pour chaque article de la commande, appeler :
--   SELECT add_order_line(<order_id>, <produit_id>, <quantite>, <prix_unitaire>);

-- ============================================
-- Fin du script
-- ============================================
