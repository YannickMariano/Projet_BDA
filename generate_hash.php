<?php
// generate_hash.php — À exécuter UNE SEULE FOIS pour obtenir les vrais hash
// Accéder via : http://localhost:8000/generate_hash.php
// Ensuite, copier les hash dans schema.sql et supprimer ce fichier

$password = 'password'; // Mot de passe commun pour les tests

echo "<pre>";
echo "Mot de passe : $password\n\n";
echo "Hash à utiliser dans schema.sql :\n";
echo password_hash($password, PASSWORD_BCRYPT) . "\n\n";
echo "⚠️  COPIEZ ce hash, mettez-le dans schema.sql à la place de '\$2y\$10\$...'";
echo "\nPuis supprimez ce fichier.";
echo "</pre>";
