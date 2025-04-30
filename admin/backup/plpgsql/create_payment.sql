CREATE OR REPLACE FUNCTION create_payment(
    p_order_id INTEGER,
    p_mode_paiement VARCHAR,
    p_reference_transaction VARCHAR DEFAULT NULL,
    p_statut VARCHAR DEFAULT 'pending'
)
RETURNS INTEGER AS $$
DECLARE
    v_payment_id INTEGER;
BEGIN
    INSERT INTO payments (order_id, mode_paiement, reference_transaction, statut)
    VALUES (p_order_id, p_mode_paiement, p_reference_transaction, p_statut)
    RETURNING id INTO v_payment_id;
    
    RETURN v_payment_id;
END;
$$ LANGUAGE plpgsql; 