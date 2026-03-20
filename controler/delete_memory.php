<?php
// Import du fichier config
require_once '../modele/config.php';

// Vérification si un utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/vue/pages/home.php');
    exit;
}

$userId = $_SESSION['user']['id'];

// Récupère l'id du souvenir à supprimer depuis l'url
$memoryId = $_GET['id'] ?? null;

if ($memoryId) {
    // Vérifie que le souvenir appartient bien à l'utilisateur connecté avant de supprimer
    $stmt = $bdd->prepare("SELECT file_path FROM memories WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $memoryId, ':user_id' => $userId]);
    $memory = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($memory) {
        // Supprime le fichier physique si il existe
        if ($memory['file_path']) {
            $filePath = __DIR__ . '/../' . $memory['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // Supprime le souvenir de la base de données
        $delete = $bdd->prepare("DELETE FROM memories WHERE id = :id AND user_id = :user_id");
        $delete->execute([':id' => $memoryId, ':user_id' => $userId]);
    }
}

// Redirige vers l'index après suppression
header('Location: ' . BASE_URL . '/index.php');
exit;