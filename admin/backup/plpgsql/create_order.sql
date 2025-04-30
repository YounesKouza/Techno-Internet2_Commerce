-- Fonction: create_order
-- Description: Crée une nouvelle commande et retourne son ID
-- Paramètres:
--   p_utilisateur_id - ID de l'utilisateur qui passe la commande
--   p_montant_total - Montant total de la commande
--   p_statut - Statut de la commande
-- Comportement:
--   - Insère une nouvelle ligne dans la table orders
--   - Retourne l'ID de la commande nouvellement créée
-- Retour: integer (ID de la commande)

CREATE OR REPLACE FUNCTION create_order(
    p_utilisateur_id INTEGER,
    p_montant_total NUMERIC,
    p_statut VARCHAR DEFAULT 'pending'
)
RETURNS INTEGER AS $$
DECLARE
    v_order_id INTEGER;
BEGIN
    INSERT INTO orders (utilisateur_id, montant_total, statut)
    VALUES (p_utilisateur_id, p_montant_total, p_statut)
    RETURNING id INTO v_order_id;
    
    RETURN v_order_id;
END;
$$ LANGUAGE plpgsql;
