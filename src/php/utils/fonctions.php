<?php
// src/php/utils/fonctions.php
function sanitize($data) {
    return htmlspecialchars(strip_tags($data));
}
?>
