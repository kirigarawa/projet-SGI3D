<?php
// Détruit la session PHP et redirige vers la page de connexion
session_start();
session_destroy();
header('Location: login.php');
exit;
