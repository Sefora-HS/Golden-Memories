<?php
require_once '../modele/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/vue/pages/home.php');
    exit;
}

$userId  = $_SESSION['user']['id'];
$albumId = isset($_GET['album_id']) && is_numeric($_GET['album_id']) ? (int)$_GET['album_id'] : null;
$invUserId = isset($_GET['user_id']) && is_numeric($_GET['user_id']) ? (int)$_GET['user_id'] : null;

if (!$albumId || !$invUserId) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Vérifie que l'album existe et que le demandeur est membre ou propriétaire
$stmtCheck = $bdd->prepare("
    SELECT a.user_id FROM albums a
    LEFT JOIN album_members am ON am.album_id = a.id AND am.user_id = :uid
    WHERE a.id = :album_id AND (a.user_id = :uid2 OR am.user_id IS NOT NULL)
    LIMIT 1
");
$stmtCheck->execute([':uid' => $userId, ':album_id' => $albumId, ':uid2' => $userId]);
$album = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if (!$album) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Vérifie que la personne invitée est bien un ami
$stmtAmi = $bdd->prepare("
    SELECT id FROM friends
    WHERE user_id = :uid AND friend_id = :fid AND status = 'accepted'
");
$stmtAmi->execute([':uid' => $userId, ':fid' => $invUserId]);
if (!$stmtAmi->fetch()) {
    header('Location: ' . BASE_URL . '/vue/pages/share-album.php?id=' . $albumId);
    exit;
}

// Vérifie que l'utilisateur n'est pas déjà membre
$stmtExists = $bdd->prepare("
    SELECT id FROM album_members WHERE album_id = :album_id AND user_id = :user_id
");
$stmtExists->execute([':album_id' => $albumId, ':user_id' => $invUserId]);
if ($stmtExists->fetch()) {
    header('Location: ' . BASE_URL . '/vue/pages/share-album.php?id=' . $albumId . '&invited=1');
    exit;
}

// Ajoute le membre
$stmtInsert = $bdd->prepare("
    INSERT INTO album_members (album_id, user_id, role) VALUES (:album_id, :user_id, 'member')
");
$stmtInsert->execute([':album_id' => $albumId, ':user_id' => $invUserId]);

// Récupère le username de l'inviteur
$stmtInviter = $bdd->prepare("SELECT username FROM users WHERE id = :id");
$stmtInviter->execute([':id' => $userId]);
$inviter = $stmtInviter->fetch(PDO::FETCH_ASSOC);

// Récupère le titre de l'album
$stmtAlbumTitle = $bdd->prepare("SELECT title FROM albums WHERE id = :id");
$stmtAlbumTitle->execute([':id' => $albumId]);
$albumData = $stmtAlbumTitle->fetch(PDO::FETCH_ASSOC);

// Crée une notification pour la personne invitée
$stmtNotif = $bdd->prepare("
    INSERT INTO notifications (user_id, type, content, from_user_id, reference_id)
    VALUES (:user_id, 'album_invite', :content, :from_user_id, :reference_id)
");
$stmtNotif->execute([
    ':user_id'      => $invUserId,
    ':content'      => '@' . $inviter['username'] . ' t\'a invité dans l\'album "' . $albumData['title'] . '"',
    ':from_user_id' => $userId,
    ':reference_id' => $albumId
]);

header('Location: ' . BASE_URL . '/vue/pages/share-album.php?id=' . $albumId . '&invited=1');
exit;