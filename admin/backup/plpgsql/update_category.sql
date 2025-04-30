CREATE OR REPLACE FUNCTION update_category(
    p_id INTEGER,
    p_nom VARCHAR,
    p_description TEXT DEFAULT NULL
)
RETURNS BOOLEAN AS $$
BEGIN
    UPDATE categories SET 
        nom = p_nom,
        description = p_description
    WHERE id = p_id;
    
    RETURN FOUND;
END;
$$ LANGUAGE plpgsql; 