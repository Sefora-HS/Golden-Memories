<?php
require_once '../modele/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/vue/pages/home.php');
    exit;
}

$userId = $_SESSION['user']['id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ' . BASE_URL . '/vue/pages/albums.php');
    exit;
}

$albumId = (int)$_GET['id'];
$redirect = isset($_GET['redirect']) && $_GET['redirect'] === 'partage'
    ? BASE_URL . '/vue/pages/partage.php'
    : BASE_URL . '/vue/pages/albums.php';

// Vérifie que l'utilisateur est bien le propriétaire
$stmtCheck = $bdd->prepare("SELECT id, user_id FROM albums WHERE id = :id LIMIT 1");
$stmtCheck->execute([':id' => $albumId]);
$album = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if (!$album || $album['user_id'] != $userId) {
    header('Location: ' . $redirect);
    exit;
}

// Récupère les fichiers des souvenirs pour les supprimer du disque
$stmtFiles = $bdd->prepare("SELECT file_path FROM memories WHERE album_id = :album_id AND file_path IS NOT NULL");
$stmtFiles->execute([':album_id' => $albumId]);
$files = $stmtFiles->fetchAll(PDO::FETCH_COLUMN);

// Suppression en base (les FK en CASCADE gèrent les membres et souvenirs liés)
$stmtDel = $bdd->prepare("DELETE FROM albums WHERE id = :id AND user_id = :user_id");
$stmtDel->execute([':id' => $albumId, ':user_id' => $userId]);

// Suppression des fichiers physiques
foreach ($files as $filePath) {
    $fullPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim($filePath, '/');
    if (file_exists($fullPath)) {
        unlink($fullPath);
    }
}

header('Location: ' . $redirect . '?deleted=1');
exit;