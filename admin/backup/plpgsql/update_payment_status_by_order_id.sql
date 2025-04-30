CREATE OR REPLACE FUNCTION update_payment_status_by_order_id(
    p_order_id INTEGER,
    p_statut VARCHAR
)
RETURNS BOOLEAN AS $$
DECLARE
    v_affected BOOLEAN := FALSE;
BEGIN
    UPDATE payments SET 
        statut = p_statut
    WHERE order_id = p_order_id;
    
    v_affected := FOUND;
    
    RETURN v_affected;
END;
$$ LANGUAGE plpgsql; 