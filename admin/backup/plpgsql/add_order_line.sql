-- Fonction: add_order_line
-- Description: Ajoute une ligne à une commande et met à jour le stock du produit
-- Paramètres:
--   p_order_id - ID de la commande
--   p_produit_id - ID du produit
--   p_quantite - Quantité commandée
--   p_prix_unitaire - Prix unitaire du produit
-- Comportement:
--   - Insère une nouvelle ligne dans la table order_lines
--   - Appelle la fonction update_product_stock pour mettre à jour le stock du produit
-- Retour: void (aucun)

CREATE OR REPLACE FUNCTION public.add_order_line(
    p_order_id integer,
    p_produit_id integer,
    p_quantite integer,
    p_prix_unitaire numeric
) 
RETURNS void
LANGUAGE plpgsql
AS $function$
BEGIN
    INSERT INTO order_lines(order_id, produit_id, quantite, prix_unitaire)
    VALUES (p_order_id, p_produit_id, p_quantite, p_prix_unitaire);
    
    -- Mise à jour du stock du produit
    PERFORM update_product_stock(p_produit_id, p_quantite);
END;
$function$;
