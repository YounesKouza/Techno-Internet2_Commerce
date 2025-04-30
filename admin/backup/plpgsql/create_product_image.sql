CREATE OR REPLACE FUNCTION create_product_image(
    p_produit_id INTEGER,
    p_url_image VARCHAR,
    p_ordre INTEGER DEFAULT 0
)
RETURNS INTEGER AS $$
DECLARE
    v_image_id INTEGER;
BEGIN
    INSERT INTO images_products (produit_id, url_image, ordre)
    VALUES (p_produit_id, p_url_image, p_ordre)
    RETURNING id INTO v_image_id;
    
    RETURN v_image_id;
END;
$$ LANGUAGE plpgsql; 