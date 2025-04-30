CREATE OR REPLACE FUNCTION delete_with_dependencies(
    p_id INTEGER
)
RETURNS BOOLEAN AS $$
DECLARE
    v_success BOOLEAN := FALSE;
BEGIN
    -- Supprimer les lignes de commandes
    DELETE FROM order_lines
    WHERE order_id = p_id;
    
    -- Supprimer les paiements
    DELETE FROM payments
    WHERE order_id = p_id;
    
    -- Supprimer la commande
    DELETE FROM orders
    WHERE id = p_id;
    
    v_success := FOUND;
    
    RETURN v_success;
END;
$$ LANGUAGE plpgsql; 