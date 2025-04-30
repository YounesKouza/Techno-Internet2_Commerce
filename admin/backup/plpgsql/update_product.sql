CREATE OR REPLACE FUNCTION update_product(
    p_id INTEGER,
    p_titre VARCHAR,
    p_description TEXT,
    p_prix NUMERIC,
    p_stock INTEGER,
    p_categorie_id INTEGER DEFAULT NULL,
    p_image_principale VARCHAR DEFAULT NULL,
    p_actif BOOLEAN DEFAULT TRUE
)
RETURNS BOOLEAN AS $$
BEGIN
    UPDATE products SET 
        titre = p_titre,
        description = p_description,
        prix = p_prix,
        stock = p_stock,
        categorie_id = p_categorie_id,
        image_principale = p_image_principale,
        actif = p_actif
    WHERE id = p_id;
    
    RETURN FOUND;
END;
$$ LANGUAGE plpgsql; 