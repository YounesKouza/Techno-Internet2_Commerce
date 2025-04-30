CREATE OR REPLACE FUNCTION create_product(
    p_titre VARCHAR,
    p_description TEXT,
    p_prix NUMERIC,
    p_stock INTEGER,
    p_categorie_id INTEGER DEFAULT NULL,
    p_image_principale VARCHAR DEFAULT NULL,
    p_actif BOOLEAN DEFAULT TRUE
)
RETURNS INTEGER AS $$
DECLARE
    v_product_id INTEGER;
BEGIN
    INSERT INTO products (titre, description, prix, stock, categorie_id, image_principale, actif)
    VALUES (p_titre, p_description, p_prix, p_stock, p_categorie_id, p_image_principale, p_actif)
    RETURNING id INTO v_product_id;
    
    RETURN v_product_id;
END;
$$ LANGUAGE plpgsql; 