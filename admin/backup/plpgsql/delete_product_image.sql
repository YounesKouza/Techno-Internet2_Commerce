CREATE OR REPLACE FUNCTION delete_product_image(
    p_id INTEGER
)
RETURNS BOOLEAN AS $$
BEGIN
    DELETE FROM images_products
    WHERE id = p_id;
    
    RETURN FOUND;
END;
$$ LANGUAGE plpgsql; 