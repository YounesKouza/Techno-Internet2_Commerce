CREATE OR REPLACE FUNCTION update_order(
    p_id INTEGER,
    p_utilisateur_id INTEGER,
    p_montant_total NUMERIC,
    p_statut VARCHAR,
    p_date_commande TIMESTAMP DEFAULT NULL
)
RETURNS BOOLEAN AS $$
BEGIN
    UPDATE orders SET 
        utilisateur_id = p_utilisateur_id,
        montant_total = p_montant_total,
        statut = p_statut,
        date_commande = COALESCE(p_date_commande, date_commande)
    WHERE id = p_id;
    
    RETURN FOUND;
END;
$$ LANGUAGE plpgsql; 