-- Fonction: create_order
-- Description: Crée une nouvelle commande et retourne son ID
-- Paramètres:
--   p_utilisateur_id - ID de l'utilisateur qui passe la commande
--   p_montant_total - Montant total de la commande
-- Comportement:
--   - Insère une nouvelle ligne dans la table orders
--   - Retourne l'ID de la commande nouvellement créée
-- Retour: integer (ID de la commande)

CREATE OR REPLACE FUNCTION public.create_order(p_utilisateur_id integer, p_montant_total numeric) 
RETURNS integer
LANGUAGE plpgsql
AS $function$
DECLARE
    new_order_id INTEGER;
BEGIN
    INSERT INTO orders(utilisateur_id, montant_total)
    VALUES (p_utilisateur_id, p_montant_total)
    RETURNING id INTO new_order_id;
    RETURN new_order_id;
END;
$function$;
