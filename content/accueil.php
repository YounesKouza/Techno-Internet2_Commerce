<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        header {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 1rem;
        }
        .container {
            width: 80%;
            margin: 0 auto;
            padding: 2rem 0;
        }
        footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 1rem;
            position: fixed;
            bottom: 0;
            width: 100%;
        }
    </style>
</head>
<body>
    <header>
        <h1>Bienvenue sur notre site</h1>
        <nav>
            <a href="accueil.php">Accueil</a> |
            <a href="services.php">Services</a> |
            <a href="contact.php">Contact</a>
        </nav>
    </header>

    <div class="container">
        <h2>Accueil</h2>
        <p>Bienvenue sur la page d'accueil de notre site web. Nous sommes heureux de vous accueillir.</p>
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam euismod, nisi vel consectetur euismod, 
           nisi nisl consectetur nisi, euismod nisl nisi vel consectetur euismod.</p>
        
        <?php
            // Vous pouvez ajouter du code PHP dynamique ici
            $date = date("d/m/Y");
            echo "<p>Nous sommes le $date</p>";
        ?>
    </div>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> - Tous droits réservés</p>
    </footer>
</body>
</html></body>