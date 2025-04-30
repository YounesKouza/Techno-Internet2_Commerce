CREATE OR REPLACE FUNCTION set_as_main_image(
    p_image_id INTEGER,
    p_product_id INTEGER
)
RETURNS BOOLEAN AS $$
DECLARE
    v_success BOOLEAN := FALSE;
    v_url_image VARCHAR(255);
BEGIN
    -- Récupérer l'URL de l'image
    SELECT url_image INTO v_url_image
    FROM images_products 
    WHERE id = p_image_id 
    AND produit_id = p_product_id;
    
    -- Si l'image existe, la définir comme image principale du produit
    IF v_url_image IS NOT NULL THEN
        UPDATE products 
        SET image_principale = v_url_image
        WHERE id = p_product_id;
        
        v_success := FOUND;
    END IF;
    
    RETURN v_success;
END;
$$ LANGUAGE plpgsql; 