<?php
// pages/deconnexion.php – Déconnexion client
session_start();
session_destroy();
header("Location: index.php");
exit;
?>
