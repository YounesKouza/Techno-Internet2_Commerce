CREATE OR REPLACE FUNCTION update_quantity(
    p_id INTEGER,
    p_quantite INTEGER
)
RETURNS BOOLEAN AS $$
BEGIN
    UPDATE order_lines SET 
        quantite = p_quantite
    WHERE id = p_id;
    
    RETURN FOUND;
END;
$$ LANGUAGE plpgsql; 