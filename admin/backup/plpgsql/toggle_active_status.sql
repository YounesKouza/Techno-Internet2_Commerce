CREATE OR REPLACE FUNCTION toggle_active_status(
    p_id INTEGER,
    p_actif BOOLEAN
)
RETURNS BOOLEAN AS $$
BEGIN
    UPDATE products SET 
        actif = p_actif
    WHERE id = p_id;
    
    RETURN FOUND;
END;
$$ LANGUAGE plpgsql; 