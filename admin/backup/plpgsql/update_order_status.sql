CREATE OR REPLACE FUNCTION update_order_status(
    p_id INTEGER,
    p_statut VARCHAR
)
RETURNS BOOLEAN AS $$
BEGIN
    UPDATE orders SET 
        statut = p_statut
    WHERE id = p_id;
    
    RETURN FOUND;
END;
$$ LANGUAGE plpgsql; 