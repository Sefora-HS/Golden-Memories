<?php
include_once __DIR__ . '/../../../modele/config.php';
include_once __DIR__ . '/../../../modele/User.php';

$userConnecte = null;
if (isset($_SESSION['user']['id'])) {
    $userId = $_SESSION['user']['id'];
    $userConnecte = User::getById($bdd, $userId);
}
?>

<header class="header">
    <div class="parametre"><ion-icon name="cog-outline"></ion-icon></div>
    <h1><a href="<?= BASE_URL ?>" class="logo-title">Golden Memories</a></h1>
    <div class="notif"><ion-icon name="notifications-outline"></ion-icon></div>
    <div class="pp">
    <img src="<?= BASE_URL ?>/vue/assets/images/<?= htmlspecialchars($userConnecte['picture'] ?? 'default.jpg') ?>" alt="Photo de profil">
</header>
