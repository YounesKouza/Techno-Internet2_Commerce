<?php
$pays = new PaysDAO($cnx);
$liste = $pays->getPays();

// Vérifier si la liste n'est pas vide
if (!empty($liste)) {
    print "Liste des pays :<br>";

    foreach ($liste as $pays) {
        print "- " . $pays->nom_pays . "<br>";
    }
} else {
    print "Aucun pays trouvé.";
}

print'<pre>';
//var_dump($liste);
print'</pre>';