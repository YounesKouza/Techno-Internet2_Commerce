CREATE OR REPLACE FUNCTION delete_user(
    p_id INTEGER
)
RETURNS BOOLEAN AS $$
BEGIN
    DELETE FROM users
    WHERE id = p_id;
    
    RETURN FOUND;
END;
$$ LANGUAGE plpgsql; 