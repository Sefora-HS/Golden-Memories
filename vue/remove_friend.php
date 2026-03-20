<?php
require_once '../modele/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/vue/pages/home.php');
    exit;
}

$userId = $_SESSION['user']['id'];
$amiId = $_GET['id'] ?? null;

if (!$amiId) {
    header('Location: ' . BASE_URL . '/vue/pages/liste_amis.php');
    exit;
}

// Supprime l'amitié dans les deux sens
$stmt = $bdd->prepare("DELETE FROM friends WHERE (user_id = :user_id AND friend_id = :ami_id) OR (user_id = :ami_id2 AND friend_id = :user_id2)");
$stmt->execute([
    ':user_id' => $userId,
    ':ami_id' => $amiId,
    ':ami_id2' => $amiId,
    ':user_id2' => $userId
]);

header('Location: ' . BASE_URL . '/vue/pages/liste_amis.php');
exit;