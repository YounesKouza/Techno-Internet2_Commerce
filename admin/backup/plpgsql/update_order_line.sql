CREATE OR REPLACE FUNCTION update_order_line(
    p_id INTEGER,
    p_order_id INTEGER,
    p_produit_id INTEGER,
    p_quantite INTEGER,
    p_prix_unitaire NUMERIC
)
RETURNS BOOLEAN AS $$
BEGIN
    UPDATE order_lines SET 
        order_id = p_order_id,
        produit_id = p_produit_id,
        quantite = p_quantite,
        prix_unitaire = p_prix_unitaire
    WHERE id = p_id;
    
    RETURN FOUND;
END;
$$ LANGUAGE plpgsql; 