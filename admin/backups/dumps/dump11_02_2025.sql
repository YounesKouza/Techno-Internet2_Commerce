CREATE TABLE departement(
   id_departement SERIAL,
   nom_departement TEXT NOT NULL,
   PRIMARY KEY(id_departement),
   UNIQUE(nom_departement)
);

CREATE TABLE mission(
   id_mission SERIAL,
   nom_mission TEXT NOT NULL,
   date_debut DATE NOT NULL,
   date_fin DATE NOT NULL,
   description TEXT NOT NULL,
   rapport_fin TEXT,
   photo_personnel TEXT,
   PRIMARY KEY(id_mission),
   UNIQUE(nom_mission)
);

CREATE TABLE pays(
   id_pays SERIAL,
   nom_pays TEXT NOT NULL,
   PRIMARY KEY(id_pays),
   UNIQUE(nom_pays)
);

CREATE TABLE materiel(
   id_materiel SERIAL,
   nom_materiel TEXT NOT NULL,
   stock INTEGER,
   en_utilisation BOOLEAN NOT NULL,
   photo_materiel TEXT,
   PRIMARY KEY(id_materiel),
   UNIQUE(nom_materiel)
);

CREATE TABLE ville(
   id_ville SERIAL,
   nom_ville TEXT NOT NULL,
   id_pays INTEGER NOT NULL,
   PRIMARY KEY(id_ville),
   FOREIGN KEY(id_pays) REFERENCES pays(id_pays)
);

CREATE TABLE personnel(
   id_personnel SERIAL,
   nom_personnel TEXT NOT NULL,
   prenom_personnel TEXT NOT NULL,
   email TEXT NOT NULL,
   password TEXT,
   fonction TEXT,
   competences TEXT,
   photo_personnel TEXT,
   disponible BOOLEAN NOT NULL,
   mobile TEXT,
   id_ville INTEGER NOT NULL,
   id_departement INTEGER NOT NULL,
   PRIMARY KEY(id_personnel),
   UNIQUE(email),
   FOREIGN KEY(id_ville) REFERENCES ville(id_ville),
   FOREIGN KEY(id_departement) REFERENCES departement(id_departement)
);

CREATE TABLE galerie(
   id_galerie SERIAL,
   lien TEXT NOT NULL,
   date_photo DATE,
   visible BOOLEAN NOT NULL,
   id_mission INTEGER NOT NULL,
   id_personnel INTEGER NOT NULL,
   PRIMARY KEY(id_galerie),
   FOREIGN KEY(id_mission) REFERENCES mission(id_mission),
   FOREIGN KEY(id_personnel) REFERENCES personnel(id_personnel)
);

CREATE TABLE mission_personnel(
   id_personnel INTEGER,
   id_mission INTEGER,
   present_sur_site BOOLEAN,
   date_aller DATE,
   date_retour DATE,
   PRIMARY KEY(id_personnel, id_mission),
   FOREIGN KEY(id_personnel) REFERENCES personnel(id_personnel),
   FOREIGN KEY(id_mission) REFERENCES mission(id_mission)
);

CREATE TABLE mission_materiel(
   id_mission INTEGER,
   id_materiel INTEGER,
   quantite INTEGER,
   PRIMARY KEY(id_mission, id_materiel),
   FOREIGN KEY(id_mission) REFERENCES mission(id_mission),
   FOREIGN KEY(id_materiel) REFERENCES materiel(id_materiel)
);
