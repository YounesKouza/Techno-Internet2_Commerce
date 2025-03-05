create function ajout_pays (text) returns integer as
'
	DECLARE p_nom_pays ALIAS FOR $1;
	DECLARE id integer;
	
BEGIN
	INSERT INTO pays (nom_pays) VALUES (p_nom_pays) 
	ON CONFLICT (nom_pays) DO NOTHING;
	SELECT INTO ID id_pays FROM pays WHERE nom_pays = p_nom_pays;
	
	IF id IS NULL
	THEN
	  return -1;
	ELSE
	  return id;
	END IF;
END;
'
LANGUAGE 'plpgsql';
