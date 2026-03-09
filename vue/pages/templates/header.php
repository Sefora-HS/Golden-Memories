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
    <h1><a href="<?= BASE_URL ?>" class="logo-title">Golden Memories</a></h1>
    <div class="header-right">
        <div class="notif"><ion-icon name="notifications-outline"></ion-icon></div>
        <div class="pp">
            <a href="<?= BASE_URL ?>/vue/pages/profil.php">
                <img src="<?= BASE_URL ?>/vue/assets/images/<?= htmlspecialchars($userConnecte['picture'] ?? 'default.jpg') ?>" alt="Photo de profil">
            </a>
        </div>
    </div>
</header>