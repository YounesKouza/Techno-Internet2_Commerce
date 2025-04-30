CREATE OR REPLACE FUNCTION delete_category(
    p_id INTEGER
)
RETURNS BOOLEAN AS $$
BEGIN
    DELETE FROM categories
    WHERE id = p_id;
    
    RETURN FOUND;
END;
$$ LANGUAGE plpgsql; 