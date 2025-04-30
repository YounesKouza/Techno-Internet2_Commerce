CREATE OR REPLACE FUNCTION delete_product(
    p_id INTEGER
)
RETURNS BOOLEAN AS $$
BEGIN
    DELETE FROM products
    WHERE id = p_id;
    
    RETURN FOUND;
END;
$$ LANGUAGE plpgsql; 