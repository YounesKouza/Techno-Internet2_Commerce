-- Fonction: update_product_stock
-- Description: Met à jour le stock d'un produit en soustrayant la quantité spécifiée.
-- Paramètres:
--   p_produit_id - ID du produit à mettre à jour
--   p_quantite - Quantité à soustraire du stock actuel
-- Comportement:
--   - Vérifie si le stock est suffisant avant la mise à jour
--   - Lève une exception si le stock est insuffisant
-- Retour: void (aucun)

CREATE OR REPLACE FUNCTION public.update_product_stock(p_produit_id integer, p_quantite integer) 
RETURNS void
LANGUAGE plpgsql
AS $function$
BEGIN
    UPDATE products
    SET stock = stock - p_quantite
    WHERE id = p_produit_id AND stock >= p_quantite;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Stock insuffisant pour le produit %', p_produit_id;
    END IF;
END;
$function$;
