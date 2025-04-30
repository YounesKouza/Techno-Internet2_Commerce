CREATE OR REPLACE FUNCTION update_stock(
    p_id INTEGER,
    p_quantity INTEGER
)
RETURNS BOOLEAN AS $$
BEGIN
    UPDATE products SET 
        stock = stock + p_quantity
    WHERE id = p_id;
    
    RETURN FOUND;
END;
$$ LANGUAGE plpgsql; 