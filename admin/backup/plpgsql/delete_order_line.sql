CREATE OR REPLACE FUNCTION delete_order_line(
    p_id INTEGER
)
RETURNS BOOLEAN AS $$
BEGIN
    DELETE FROM order_lines
    WHERE id = p_id;
    
    RETURN FOUND;
END;
$$ LANGUAGE plpgsql; 