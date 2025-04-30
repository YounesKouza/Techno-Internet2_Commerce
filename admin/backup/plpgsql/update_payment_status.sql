CREATE OR REPLACE FUNCTION update_payment_status(
    p_id INTEGER,
    p_statut VARCHAR
)
RETURNS BOOLEAN AS $$
BEGIN
    UPDATE payments SET 
        statut = p_statut
    WHERE id = p_id;
    
    RETURN FOUND;
END;
$$ LANGUAGE plpgsql; 