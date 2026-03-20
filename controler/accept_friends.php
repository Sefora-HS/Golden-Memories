<?php
require_once '../modele/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/vue/pages/home.php');
    exit;
}

$userId = $_SESSION['user']['id'];
$fromId = $_GET['from'] ?? null;
$notifId = $_GET['notif'] ?? null;

if (!$fromId) {
    header('Location: ' . BASE_URL . '/vue/pages/notifications.php');
    exit;
}

// Accepte la demande en mettant le status à accepted
$stmt = $bdd->prepare("UPDATE friends SET status = 'accepted' WHERE user_id = :from_id AND friend_id = :user_id");
$stmt->execute([':from_id' => $fromId, ':user_id' => $userId]);

// Ajoute aussi l'amitié dans l'autre sens
$check = $bdd->prepare("SELECT id FROM friends WHERE user_id = :user_id AND friend_id = :from_id");
$check->execute([':user_id' => $userId, ':from_id' => $fromId]);
if (!$check->fetch()) {
    $insert = $bdd->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (:user_id, :from_id, 'accepted')");
    $insert->execute([':user_id' => $userId, ':from_id' => $fromId]);
}

// Récupère le username de celui qui accepte
$stmtUser = $bdd->prepare("SELECT username FROM users WHERE id = :id");
$stmtUser->execute([':id' => $userId]);
$userAccepte = $stmtUser->fetch(PDO::FETCH_ASSOC);

// Met à jour la notification avec le nouveau message et change le type
if ($notifId) {
    $bdd->prepare("UPDATE notifications SET is_read = 1, content = :content, type = 'new_memory' WHERE id = :id")
        ->execute([
            ':content' => '@' . $userAccepte['username'] . ' est devenu(e) votre ami(e) !',
            ':id' => $notifId
        ]);
}

header('Location: ' . BASE_URL . '/vue/pages/notifications.php');
exit;