<?php
// admin/logout.php – Déconnexion Admin
include '../src/php/utils/check_connection.php';
session_destroy();
header("Location: ../pages/connexion.php");
exit;
?>
