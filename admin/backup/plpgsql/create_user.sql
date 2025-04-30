CREATE OR REPLACE FUNCTION create_user(
    p_nom VARCHAR,
    p_email VARCHAR,
    p_mot_de_passe VARCHAR,
    p_role VARCHAR DEFAULT 'client',
    p_adresse TEXT DEFAULT NULL,
    p_telephone VARCHAR DEFAULT NULL
)
RETURNS INTEGER AS $$
DECLARE
    v_user_id INTEGER;
BEGIN
    INSERT INTO users (nom, email, mot_de_passe, role, adresse, telephone)
    VALUES (p_nom, p_email, p_mot_de_passe, p_role, p_adresse, p_telephone)
    RETURNING id INTO v_user_id;
    
    RETURN v_user_id;
END;
$$ LANGUAGE plpgsql; 