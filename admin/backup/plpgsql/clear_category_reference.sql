CREATE OR REPLACE FUNCTION clear_category_reference(
    p_categorie_id INTEGER
)
RETURNS BOOLEAN AS $$
BEGIN
    UPDATE products SET 
        categorie_id = NULL
    WHERE categorie_id = p_categorie_id;
    
    RETURN FOUND;
END;
$$ LANGUAGE plpgsql; 