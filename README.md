# Furniture - Site E-commerce de Meubles

Un site e-commerce pour la vente de meubles en ligne, développé avec PHP, architecture MVC, et base de données PostgreSQL.

## 📋 Prérequis

- PHP 7.4 ou supérieur
- PostgreSQL 12 ou supérieur
- Serveur web Apache (ou alternative)
- Composer pour la gestion des dépendances

## 🚀 Installation

### 1. Cloner le dépôt

```bash
git clone https://github.com/votre-utilisateur/Techno-internet2_commerce.git
cd Techno-internet2_commerce
```

### 2. Installer les dépendances avec Composer

```bash
composer install
```

### 3. Configuration de la base de données

1. Créez une base de données PostgreSQL nommée `ProjetCommerce`
2. Créez un utilisateur nommé `anonyme` avec le mot de passe `anonyme` (ou modifiez les paramètres de connexion dans `admin/src/php/db/connexion.php`)
3. Exécutez le script d'initialisation de la base de données :

```
http://localhost/Techno-internet2_commerce/admin/src/php/db/setup_database.php
```

Assurez-vous d'adapter l'URL selon votre configuration locale.

Ou via psql :

```bash
psql -U postgres -c "CREATE DATABASE \"ProjetCommerce\" WITH ENCODING 'UTF8'"
psql -U postgres -d ProjetCommerce -f admin/src/php/db/database_schema.sql
psql -U postgres -d ProjetCommerce -f admin/src/php/db/database_functions.sql
```

## 🔧 Configuration

- **Connexion à la base de données** : Modifiez le fichier `admin/src/php/db/connexion.php` avec vos paramètres de connexion PostgreSQL.
- **Dossier d'uploads** : Assurez-vous que le dossier `admin/public/uploads` possède les droits d'écriture nécessaires.

```bash
chmod 755 admin/public/uploads
```

## 📂 Structure du projet

```
/Techno-internet2_commerce
├── index_.php                   # Entrée publique (routage MVC)
├── composer.json               # Dépendances PHP
│
├── /pages                      # Pages accessibles sans connexion
│   ├── accueil.php
│   ├── catalogue.php
│   ├── produit_details.php
│   ├── panier.php
│   ├── commande.php
│   ├── compte.php
│   ├── login.php
│   ├── inscription.php
│   └── page404.php
│
├── /admin
│   ├── /pages                  # Interfaces d'administration
│   ├── /src/php
│   │   ├── /classes            # Classes métiers + DAO
│   │   ├── /db                 # Connexion + scripts SQL
│   │   ├── /utils              # Fonctions annexes
│   │   └── /ajax               # Requêtes AJAX
│   │
│   └── /public                 # Assets publics
│       ├── /css 
│       ├── /js 
│       ├── /images 
│       └── /uploads            # Uploads produits
```

## 🔐 Accès à l'administration

- URL : `http://localhost/Techno-internet2_commerce/admin/pages/accueil_admin.php`
- Identifiants par défaut :
  - Utilisateur : `admin`
  - Mot de passe : `admin123`

## 📚 Documentation

Pour en savoir plus sur les fonctionnalités et l'architecture du projet, consultez le dossier `docs/`.

## 📝 Contribution

1. Forkez le projet
2. Créez votre branche (`git checkout -b feature/amazing-feature`)
3. Committez vos changements (`git commit -m 'Add amazing feature'`)
4. Pushez vers la branche (`git push origin feature/amazing-feature`)
5. Ouvrez une Pull Request

## 📄 Licence

Ce projet est distribué sous la licence MIT. Voir le fichier `LICENSE` pour plus d'informations.

## 📧 Contact

Pour toute question ou suggestion, contactez [votre-email@example.com](mailto:votre-email@example.com) 