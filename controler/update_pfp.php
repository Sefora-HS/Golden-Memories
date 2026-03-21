<?php
require_once '../modele/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/vue/pages/home.php');
    exit;
}

$userId = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['picture'])) {
    $file = $_FILES['picture'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($ext, $allowed)) {
        header('Location: ' . BASE_URL . '/vue/pages/profil.php?error=format');
        exit;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        header('Location: ' . BASE_URL . '/vue/pages/profil.php?error=size');
        exit;
    }

    // Nouveau nom de fichier unique
    $newName = 'pfp_' . $userId . '_' . uniqid() . '.' . $ext;
    $uploadDir = __DIR__ . '/../vue/assets/images/';
    $destPath = $uploadDir . $newName;

    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        // Met à jour la BDD
        $stmt = $bdd->prepare("UPDATE users SET picture = :picture WHERE id = :id");
        $stmt->execute([':picture' => $newName, ':id' => $userId]);

        // Met à jour la session
        $_SESSION['user']['picture'] = $newName;
        $_SESSION['user']['id'] = $userId;
    } else {
        die('Erreur upload. Chemin tenté : ' . $destPath);
    }
}

header('Location: ' . BASE_URL . '/vue/pages/profil.php');
exit;