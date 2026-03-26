<?php
require_once '../modele/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/vue/pages/home.php');
    exit;
}

$userId    = $_SESSION['user']['id'];
$capsuleId = isset($_GET['capsule_id']) && is_numeric($_GET['capsule_id']) ? (int)$_GET['capsule_id'] : 0;
$toId      = isset($_GET['to'])         && is_numeric($_GET['to'])         ? (int)$_GET['to']         : 0;

if (!$capsuleId || !$toId) {
    header('Location: ' . BASE_URL . '/vue/pages/capsules.php');
    exit;
}

// Vérifie que la capsule appartient à l'utilisateur ET qu'elle est ouverte
$stmtCapsule = $bdd->prepare("
    SELECT tc.id, tc.capsule_title, tc.is_open, tc.unlock_at, m.title AS memory_title
    FROM time_capsules tc
    JOIN memories m ON m.id = tc.memory_id
    WHERE tc.id = :capsule_id AND m.user_id = :user_id
");
$stmtCapsule->execute([':capsule_id' => $capsuleId, ':user_id' => $userId]);
$capsule = $stmtCapsule->fetch(PDO::FETCH_ASSOC);

if (!$capsule) {
    header('Location: ' . BASE_URL . '/vue/pages/capsules.php');
    exit;
}

// Vérif que la capsule est bien ouverte
$tz       = new DateTimeZone('Europe/Paris');
$now      = new DateTime('now', $tz);
$unlockAt = new DateTime($capsule['unlock_at'], $tz);
if (!$capsule['is_open'] && $now < $unlockAt) {
    header('Location: ' . BASE_URL . '/vue/pages/capsules.php');
    exit;
}

// Vérifie que le destinataire est bien un ami
$stmtAmi = $bdd->prepare("SELECT id FROM friends WHERE user_id = :user_id AND friend_id = :friend_id AND status = 'accepted'");
$stmtAmi->execute([':user_id' => $userId, ':friend_id' => $toId]);
if (!$stmtAmi->fetch()) {
    header('Location: ' . BASE_URL . '/vue/pages/capsules.php');
    exit;
}

// Vérifie qu'on n'a pas déjà partagé cette capsule avec cet ami
$stmtCheck = $bdd->prepare("SELECT id FROM capsule_shared WHERE capsule_id = :capsule_id AND user_id = :user_id");
$stmtCheck->execute([':capsule_id' => $capsuleId, ':user_id' => $toId]);
if ($stmtCheck->fetch()) {
    // Déjà partagé, on redirige avec un message
    header('Location: ' . BASE_URL . '/vue/pages/capsule-interior.php?id=' . $capsuleId . '&already_shared=1');
    exit;
}

// Insère dans capsule_shared
$stmtInsert = $bdd->prepare("INSERT INTO capsule_shared (capsule_id, user_id) VALUES (:capsule_id, :user_id)");
$stmtInsert->execute([':capsule_id' => $capsuleId, ':user_id' => $toId]);

// Récupère le username de celui qui partage
$stmtUser = $bdd->prepare("SELECT username FROM users WHERE id = :id");
$stmtUser->execute([':id' => $userId]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

// Envoie une notification au destinataire
$titre = $capsule['capsule_title'] ?: $capsule['memory_title'] ?: 'une capsule';
$stmtNotif = $bdd->prepare("INSERT INTO notifications (user_id, type, content, from_user_id, reference_id) VALUES (:user_id, 'new_memory', :content, :from_user_id, :reference_id)");
$stmtNotif->execute([
    ':user_id'       => $toId,
    ':content'       => '@' . $user['username'] . ' a partagé la capsule "' . $titre . '" avec vous !',
    ':from_user_id'  => $userId,
    ':reference_id'  => $capsuleId
]);

header('Location: ' . BASE_URL . '/vue/pages/capsule-interior.php?id=' . $capsuleId . '&shared=1');
exit;
