CREATE OR REPLACE FUNCTION update_image_order(
    p_id INTEGER,
    p_ordre INTEGER
)
RETURNS BOOLEAN AS $$
BEGIN
    UPDATE images_products SET 
        ordre = p_ordre
    WHERE id = p_id;
    
    RETURN FOUND;
END;
$$ LANGUAGE plpgsql; 