/* Fonctions PostgreSQL pour le e-commerce de meubles */

-- Fonction pour créer une commande
CREATE OR REPLACE FUNCTION create_order(
    p_user_id INT,
    p_shipping_address TEXT,
    p_shipping_city VARCHAR,
    p_shipping_postal_code VARCHAR,
    p_shipping_method VARCHAR,
    p_payment_method VARCHAR
) RETURNS INT AS $$
DECLARE
    v_order_id INT;
BEGIN
    -- Insertion de la commande avec montant initial à 0
    INSERT INTO orders (
        user_id, 
        total_amount, 
        shipping_address, 
        shipping_city, 
        shipping_postal_code, 
        shipping_method, 
        payment_method
    ) VALUES (
        p_user_id, 
        0, 
        p_shipping_address, 
        p_shipping_city, 
        p_shipping_postal_code, 
        p_shipping_method, 
        p_payment_method
    ) RETURNING id INTO v_order_id;
    
    RETURN v_order_id;
END;
$$ LANGUAGE plpgsql;

-- Fonction pour ajouter une ligne de commande
CREATE OR REPLACE FUNCTION add_order_line(
    p_order_id INT,
    p_product_id INT,
    p_quantity INT
) RETURNS BOOLEAN AS $$
DECLARE
    v_price DECIMAL(10, 2);
    v_subtotal DECIMAL(10, 2);
    v_total DECIMAL(10, 2) := 0;
    v_stock INT;
BEGIN
    -- Vérification du stock
    SELECT stock, price INTO v_stock, v_price FROM products WHERE id = p_product_id;
    
    IF v_stock < p_quantity THEN
        RAISE EXCEPTION 'Stock insuffisant pour le produit %', p_product_id;
        RETURN false;
    END IF;
    
    -- Calcul du sous-total
    v_subtotal := v_price * p_quantity;
    
    -- Ajout de la ligne de commande
    INSERT INTO order_lines (order_id, product_id, quantity, unit_price, subtotal)
    VALUES (p_order_id, p_product_id, p_quantity, v_price, v_subtotal);
    
    -- Mise à jour du stock
    UPDATE products SET stock = stock - p_quantity WHERE id = p_product_id;
    
    -- Mise à jour du montant total de la commande
    SELECT SUM(subtotal) INTO v_total FROM order_lines WHERE order_id = p_order_id;
    UPDATE orders SET total_amount = v_total WHERE id = p_order_id;
    
    RETURN true;
END;
$$ LANGUAGE plpgsql;

-- Fonction pour mettre à jour le stock d'un produit
CREATE OR REPLACE FUNCTION update_product_stock(
    p_product_id INT,
    p_quantity INT
) RETURNS BOOLEAN AS $$
BEGIN
    UPDATE products SET stock = stock + p_quantity WHERE id = p_product_id;
    RETURN true;
END;
$$ LANGUAGE plpgsql;

-- Fonction pour obtenir le prix total d'une commande
CREATE OR REPLACE FUNCTION get_order_total(
    p_order_id INT
) RETURNS DECIMAL AS $$
DECLARE
    v_total DECIMAL(10, 2);
BEGIN
    SELECT SUM(subtotal) INTO v_total FROM order_lines WHERE order_id = p_order_id;
    RETURN COALESCE(v_total, 0);
END;
$$ LANGUAGE plpgsql;

-- Fonction pour obtenir les articles les plus vendus
CREATE OR REPLACE FUNCTION get_best_selling_products(
    p_limit INT DEFAULT 5
) RETURNS TABLE (
    product_id INT,
    product_name VARCHAR,
    total_sales INT
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        p.id AS product_id,
        p.name AS product_name,
        COALESCE(SUM(ol.quantity), 0)::INT AS total_sales
    FROM products p
    LEFT JOIN order_lines ol ON p.id = ol.product_id
    LEFT JOIN orders o ON ol.order_id = o.id AND o.status != 'cancelled'
    WHERE p.deleted = false
    GROUP BY p.id, p.name
    ORDER BY total_sales DESC
    LIMIT p_limit;
END;
$$ LANGUAGE plpgsql; 