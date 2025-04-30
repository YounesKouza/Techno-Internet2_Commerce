CREATE OR REPLACE FUNCTION delete_all_by_user_id(
    p_utilisateur_id INTEGER
)
RETURNS INTEGER AS $$
DECLARE
    v_deleted_count INTEGER := 0;
    v_order_ids INTEGER[];
BEGIN
    -- Récupérer tous les IDs des commandes de l'utilisateur
    SELECT ARRAY_AGG(id) INTO v_order_ids 
    FROM orders 
    WHERE utilisateur_id = p_utilisateur_id;
    
    -- Si l'utilisateur a des commandes
    IF v_order_ids IS NOT NULL THEN
        -- Pour chaque commande, supprimer les lignes de commande
        DELETE FROM order_lines
        WHERE order_id = ANY(v_order_ids);
        
        -- Pour chaque commande, supprimer les paiements
        DELETE FROM payments
        WHERE order_id = ANY(v_order_ids);
        
        -- Supprimer les commandes
        DELETE FROM orders
        WHERE utilisateur_id = p_utilisateur_id;
        
        -- Récupérer le nombre de commandes supprimées
        GET DIAGNOSTICS v_deleted_count = ROW_COUNT;
    END IF;
    
    RETURN v_deleted_count;
END;
$$ LANGUAGE plpgsql; 