<?php
require_once '../../modele/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/vue/pages/home.php');
    exit;
}

$userId = $_SESSION['user']['id'];

// Récupère toutes les notifications de l'utilisateur connecté
$stmt = $bdd->prepare("
    SELECT n.*, u.username, u.picture 
    FROM notifications n
    LEFT JOIN users u ON n.from_user_id = u.id
    WHERE n.user_id = :user_id
    ORDER BY n.created_at DESC
");
$stmt->execute([':user_id' => $userId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Icônes par type de notification
$icons = [
    'friend_request' => 'person-add-outline',
    'new_memory'     => 'image-outline',
    'capsule_ready'  => 'time-outline',
    'album_invite'   => 'people-outline',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vue/assets/css/app.css?v=<?= time() ?>">
    <title>Golden Memories — Notifications</title>
</head>
<body>

<div class="form-page">

    <div class="form-top">
        <a href="<?= BASE_URL ?>" class="form-back">
            <ion-icon name="arrow-back-outline"></ion-icon>
        </a>
        <h1 class="form-title">Notifications</h1>
    </div>

    <div class="notif-list">

        <?php if (empty($notifications)): ?>
            <p class="amis-empty">Aucune notification pour l'instant 🔔</p>

        <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
                <div class="notif-item <?= $notif['is_read'] ? '' : 'notif-unread' ?>">

                    <div class="notif-icon">
                        <ion-icon name="<?= $icons[$notif['type']] ?? 'notifications-outline' ?>"></ion-icon>
                    </div>

                    <div class="notif-content">
                        <p class="notif-text"><?= htmlspecialchars($notif['content']) ?></p>
                        <span class="notif-time"><?= date('d/m/Y à H:i', strtotime($notif['created_at'])) ?></span>

                        <!-- Boutons accepter/refuser pour les demandes d'amis -->
                        <?php if ($notif['type'] === 'friend_request'): ?>
                            <div class="notif-actions">
                                <a href="<?= BASE_URL ?>/controler/accept_friends.php?from=<?= $notif['from_user_id'] ?>&notif=<?= $notif['id'] ?>" class="notif-accept">
                                    Accepter
                                </a>
                                <a href="<?= BASE_URL ?>/controler/decline_friends.php?from=<?= $notif['from_user_id'] ?>&notif=<?= $notif['id'] ?>" class="notif-refuse">
                                    Refuser
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!$notif['is_read']): ?>
                        <div class="notif-dot"></div>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

</div>

<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>