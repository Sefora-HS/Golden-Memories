<?php
require_once __DIR__ . '/../modele/config.php';

// Vérification de connexion
if (!isset($_SESSION['user'])) {
    die("Accès refusé");
}

$userId = $_SESSION['user']['id'];

// 1. Récupérer les infos de base de l'utilisateur
$stmtUser = $bdd->prepare("SELECT username, email, created_at FROM users WHERE id = :id");
$stmtUser->execute([':id' => $userId]);
$userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

// 2. Récupérer ses souvenirs (Memories)
$stmtMemories = $bdd->prepare("SELECT title, type, content, created_at FROM memories WHERE user_id = :id");
$stmtMemories->execute([':id' => $userId]);
$userMemories = $stmtMemories->fetchAll(PDO::FETCH_ASSOC);

// 3. Récupérer ses albums
$stmtAlbums = $bdd->prepare("SELECT title, created_at FROM albums WHERE user_id = :id");
$stmtAlbums->execute([':id' => $userId]);
$userAlbums = $stmtAlbums->fetchAll(PDO::FETCH_ASSOC);

// Construction du tableau final
$export = [
    "genere_le" => date('d-m-Y H:i:s'),
    "utilisateur" => $userData,
    "souvenirs" => $userMemories,
    "albums_crees" => $userAlbums,
    "message" => "Ceci est une copie de vos données personnelles sur Golden Memories."
];

// Transformation en JSON
$jsonData = json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// Forcer le téléchargement du fichier
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="mes_donnees_golden_memories.json"');
header('Pragma: no-cache');

echo $jsonData;
exit;