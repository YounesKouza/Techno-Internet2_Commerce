<?php
// src/php/utils/check_connection.php
session_start();
function isConnected() {
    return isset($_SESSION['user']);
}
function isAdmin() {
    return isConnected() && $_SESSION['user']['role'] === 'admin';
}
?>
