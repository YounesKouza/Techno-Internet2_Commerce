CREATE OR REPLACE FUNCTION create_category(
    p_nom VARCHAR,
    p_description TEXT DEFAULT NULL
)
RETURNS INTEGER AS $$
DECLARE
    v_category_id INTEGER;
BEGIN
    INSERT INTO categories (nom, description)
    VALUES (p_nom, p_description)
    RETURNING id INTO v_category_id;
    
    RETURN v_category_id;
END;
$$ LANGUAGE plpgsql; 