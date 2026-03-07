<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$user = 'administrateur';
$mdp = 'JclpG_Mpl';

try {
    $bdd = new PDO(
        'mysql:host=localhost;dbname=golden_memories;charset=utf8',
        $user,
        $mdp,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
        ]
    );
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

define('BASE_URL', 'https://rebekah-nonorganic-kane.ngrok-free.dev/Golden-Memories/'); 

//define('BASE_URL', 'http://localhost/Golden-Memories/');
?>