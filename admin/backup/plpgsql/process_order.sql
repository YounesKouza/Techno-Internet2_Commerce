CREATE OR REPLACE FUNCTION process_order(
    p_utilisateur_id INTEGER,
    p_montant_total NUMERIC,
    p_statut VARCHAR DEFAULT 'pending',
    p_items JSONB
)
RETURNS INTEGER AS $$
DECLARE
    v_order_id INTEGER;
    v_item JSONB;
BEGIN
    -- Commencer une transaction
    BEGIN
        -- Créer la commande
        INSERT INTO orders (utilisateur_id, montant_total, statut)
        VALUES (p_utilisateur_id, p_montant_total, p_statut)
        RETURNING id INTO v_order_id;
        
        -- Ajouter les articles à la commande
        FOR v_item IN SELECT * FROM jsonb_array_elements(p_items)
        LOOP
            -- Ajouter chaque article à la commande
            INSERT INTO order_lines (order_id, produit_id, quantite, prix_unitaire)
            VALUES (
                v_order_id,
                (v_item->>'id')::INTEGER,
                (v_item->>'quantity')::INTEGER,
                (v_item->>'prix')::NUMERIC
            );
            
            -- Mettre à jour le stock du produit
            UPDATE products 
            SET stock = stock - (v_item->>'quantity')::INTEGER
            WHERE id = (v_item->>'id')::INTEGER;
        END LOOP;
        
        RETURN v_order_id;
    -- Gérer les erreurs
    EXCEPTION WHEN OTHERS THEN
        RAISE EXCEPTION 'Erreur lors du traitement de la commande: %', SQLERRM;
    END;
END;
$$ LANGUAGE plpgsql; 