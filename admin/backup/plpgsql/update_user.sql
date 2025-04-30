CREATE OR REPLACE FUNCTION update_user(
    p_id INTEGER,
    p_nom VARCHAR,
    p_email VARCHAR,
    p_role VARCHAR,
    p_adresse TEXT DEFAULT NULL,
    p_telephone VARCHAR DEFAULT NULL
)
RETURNS BOOLEAN AS $$
BEGIN
    UPDATE users SET 
        nom = p_nom,
        email = p_email,
        role = p_role,
        adresse = p_adresse,
        telephone = p_telephone
    WHERE id = p_id;
    
    RETURN FOUND;
END;
$$ LANGUAGE plpgsql; 