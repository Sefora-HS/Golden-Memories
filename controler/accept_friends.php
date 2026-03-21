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

// Accepte la demande
$stmt = $bdd->prepare("UPDATE friends SET status = 'accepted' WHERE user_id = :from_id AND friend_id = :user_id");
$stmt->execute([':from_id' => $fromId, ':user_id' => $userId]);

// Ajoute l'amitié dans l'autre sens
$check = $bdd->prepare("SELECT id FROM friends WHERE user_id = :user_id AND friend_id = :from_id");
$check->execute([':user_id' => $userId, ':from_id' => $fromId]);
if ($check->fetch()) {
    $bdd->prepare("UPDATE friends SET status = 'accepted' WHERE user_id = :user_id AND friend_id = :from_id")
        ->execute([':user_id' => $userId, ':from_id' => $fromId]);
} else {
    $bdd->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (:user_id, :from_id, 'accepted')")
        ->execute([':user_id' => $userId, ':from_id' => $fromId]);
}

// Récupère les usernames
$stmtMe = $bdd->prepare("SELECT username FROM users WHERE id = :id");
$stmtMe->execute([':id' => $userId]);
$userMe = $stmtMe->fetch(PDO::FETCH_ASSOC);

$stmtFrom = $bdd->prepare("SELECT username FROM users WHERE id = :id");
$stmtFrom->execute([':id' => $fromId]);
$userFrom = $stmtFrom->fetch(PDO::FETCH_ASSOC);

// Supprime la notif de demande d'ami sur le compte ipo (plus utile)
if ($notifId) {
    $bdd->prepare("DELETE FROM notifications WHERE id = :id")
        ->execute([':id' => $notifId]);
}

// Crée une notif 1
$bdd->prepare("INSERT INTO notifications (user_id, type, content, from_user_id) VALUES (:user_id, 'new_memory', :content, :from_user_id)")
    ->execute([
        ':user_id' => $userId,
        ':content' => '@' . $userFrom['username'] . ' est devenu(e) votre ami(e) !',
        ':from_user_id' => $fromId
    ]);

// Crée une notif 2
$bdd->prepare("INSERT INTO notifications (user_id, type, content, from_user_id) VALUES (:user_id, 'new_memory', :content, :from_user_id)")
    ->execute([
        ':user_id' => $fromId,
        ':content' => '@' . $userMe['username'] . ' est devenu(e) votre ami(e) !',
        ':from_user_id' => $userId
    ]);

header('Location: ' . BASE_URL . '/vue/pages/notifications.php');
exit;