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

// Supprime la demande d'ami
$stmt = $bdd->prepare("DELETE FROM friends WHERE user_id = :from_id AND friend_id = :user_id");
$stmt->execute([':from_id' => $fromId, ':user_id' => $userId]);

// Supprime la notification
if ($notifId) {
    $bdd->prepare("DELETE FROM notifications WHERE id = :id")
        ->execute([':id' => $notifId]);
}

// Rafraîchit la session avec les données à jour (Bug 6)
$stmtSession = $bdd->prepare("SELECT id, username, picture FROM users WHERE id = :id");
$stmtSession->execute([':id' => $userId]);
$freshUser = $stmtSession->fetch(PDO::FETCH_ASSOC);
if ($freshUser) {
    $_SESSION['user'] = array_merge($_SESSION['user'], $freshUser);
}

header('Location: ' . BASE_URL . '/vue/pages/notifications.php');
exit;