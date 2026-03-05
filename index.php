<?php
// index.php — Point d'entrée : redirige selon le rôle
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['role'] === 'admin') {
    require 'admin.php';
} else {
    require 'user.php';
}
