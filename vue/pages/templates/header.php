<?php
include_once __DIR__ . '/../../../modele/config.php';
include_once __DIR__ . '/../../../modele/User.php';

$userConnecte = null;
$notifsNonLues = 0;

if (isset($_SESSION['user']['id'])) {
    $userId = $_SESSION['user']['id'];
    $userConnecte = User::getById($bdd, $userId);

    // Rafraîchit la session avec les données à jour
    $_SESSION['user'] = array_merge($_SESSION['user'], $userConnecte);

    // Compte les notifications non lues
    $stmtCount = $bdd->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0");
    $stmtCount->execute([':uid' => $userId]);
    $notifsNonLues = (int) $stmtCount->fetchColumn();
}
?>

<header class="header">
    <h1><a href="<?= BASE_URL ?>" class="logo-title">Golden Memories</a></h1>
    <div class="header-right">
        <a href="<?= BASE_URL ?>/vue/pages/notifications.php" class="notif">
            <ion-icon name="<?= $notifsNonLues > 0 ? 'notifications' : 'notifications-outline' ?>"></ion-icon>
            <?php if ($notifsNonLues > 0): ?>
                <span class="notif-badge"><?= $notifsNonLues > 99 ? '99+' : $notifsNonLues ?></span>
            <?php endif; ?>
        </a>
        <div class="pp">
            <a href="<?= BASE_URL ?>/vue/pages/profil.php">
                <img src="<?= BASE_URL ?>/vue/assets/images/<?= htmlspecialchars($userConnecte['picture'] ?? 'default.jpg') ?>" alt="Photo de profil">
            </a>
        </div>
    </div>
</header>