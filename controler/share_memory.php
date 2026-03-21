<?php
require_once '../modele/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/vue/pages/home.php');
    exit;
}

$userId = $_SESSION['user']['id'];
$memoryId = $_GET['memory_id'] ?? null;
$toId = $_GET['to'] ?? null;

if (!$memoryId || !$toId) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Vérifie que le destinataire est bien un ami
$checkAmi = $bdd->prepare("SELECT id FROM friends WHERE user_id = :user_id AND friend_id = :friend_id AND status = 'accepted'");
$checkAmi->execute([':user_id' => $userId, ':friend_id' => $toId]);
if (!$checkAmi->fetch()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Récupère le souvenir
$stmtMemory = $bdd->prepare("SELECT title, type FROM memories WHERE id = :id");
$stmtMemory->execute([':id' => $memoryId]);
$memory = $stmtMemory->fetch(PDO::FETCH_ASSOC);

if (!$memory) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Récupère le username de celui qui partage
$stmtUser = $bdd->prepare("SELECT username FROM users WHERE id = :id");
$stmtUser->execute([':id' => $userId]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

// Crée la notification pour le destinataire
$titre = $memory['title'] ? '"' . $memory['title'] . '"' : 'un souvenir';
$stmtNotif = $bdd->prepare("INSERT INTO notifications (user_id, type, content, from_user_id, reference_id) VALUES (:user_id, 'new_memory', :content, :from_user_id, :reference_id)");
$stmtNotif->execute([
    ':user_id' => $toId,
    ':content' => '@' . $user['username'] . ' a partagé ' . $titre . ' avec vous !',
    ':from_user_id' => $userId,
    ':reference_id' => $memoryId
]);

// Redirige vers le post avec un message de confirmation
header('Location: ' . BASE_URL . '/vue/pages/post.php?id=' . $memoryId . '&shared=1');
exit;