<?php
require_once '../modele/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/vue/pages/home.php');
    exit;
}

$userId = $_SESSION['user']['id'];
$friendId = $_GET['id'] ?? null;
$recherche = $_GET['q'] ?? '';

if (!$friendId || $friendId == $userId) {
    header('Location: ' . BASE_URL . '/vue/pages/amis.php');
    exit;
}

// Vérifie que la demande n'existe pas déjà
$check = $bdd->prepare("SELECT id FROM friends WHERE user_id = :user_id AND friend_id = :friend_id");
$check->execute([':user_id' => $userId, ':friend_id' => $friendId]);

if (!$check->fetch()) {
    // Ajoute la demande en statut pending
    $stmt = $bdd->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (:user_id, :friend_id, 'pending')");
    $stmt->execute([':user_id' => $userId, ':friend_id' => $friendId]);

    // Récupère le username de celui qui envoie la demande
    $stmtUser = $bdd->prepare("SELECT username FROM users WHERE id = :id");
    $stmtUser->execute([':id' => $userId]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    // Crée une notification pour le destinataire
    $stmtNotif = $bdd->prepare("INSERT INTO notifications (user_id, type, content, from_user_id) VALUES (:user_id, 'friend_request', :content, :from_user_id)");
    $stmtNotif->execute([
        ':user_id' => $friendId,
        ':content' => '@' . $user['username'] . ' vous a envoyé une demande d\'ami',
        ':from_user_id' => $userId
    ]);
}

// Redirige vers la recherche avec les résultats déjà affichés
header('Location: ' . BASE_URL . '/vue/pages/amis.php?q=' . urlencode($recherche));
exit;