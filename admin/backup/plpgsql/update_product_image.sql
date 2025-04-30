CREATE OR REPLACE FUNCTION update_product_image(
    p_id INTEGER,
    p_produit_id INTEGER,
    p_url_image VARCHAR,
    p_ordre INTEGER
)
RETURNS BOOLEAN AS $$
BEGIN
    UPDATE images_products SET 
        produit_id = p_produit_id,
        url_image = p_url_image,
        ordre = p_ordre
    WHERE id = p_id;
    
    RETURN FOUND;
END;
$$ LANGUAGE plpgsql; 