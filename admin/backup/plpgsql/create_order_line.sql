CREATE OR REPLACE FUNCTION create_order_line(
    p_order_id INTEGER,
    p_produit_id INTEGER,
    p_quantite INTEGER,
    p_prix_unitaire NUMERIC
)
RETURNS INTEGER AS $$
DECLARE
    v_order_line_id INTEGER;
BEGIN
    INSERT INTO order_lines (order_id, produit_id, quantite, prix_unitaire)
    VALUES (p_order_id, p_produit_id, p_quantite, p_prix_unitaire)
    RETURNING id INTO v_order_line_id;
    
    RETURN v_order_line_id;
END;
$$ LANGUAGE plpgsql; 