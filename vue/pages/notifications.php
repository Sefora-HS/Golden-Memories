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

// Récupère les aperçus des souvenirs partagés
$apercus = [];
foreach ($notifications as $notif) {
    if ($notif['type'] === 'new_memory' && $notif['reference_id']) {
        $stmtApercu = $bdd->prepare("SELECT type, file_path, content FROM memories WHERE id = :id");
        $stmtApercu->execute([':id' => $notif['reference_id']]);
        $apercus[$notif['reference_id']] = $stmtApercu->fetch(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vue/assets/css/app.css?v=<?= time() ?>">
    <link rel="icon" type="image/png" sizes="60x60" href="<?= BASE_URL ?>/vue/assets/images/favicon.png">
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
            <p class="amis-empty">Aucune notification pour l'instant</p>

        <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
                <div class="notif-item <?= $notif['is_read'] ? '' : 'notif-unread' ?>" data-id="<?= $notif['id'] ?>">

                    <button class="notif-delete-btn" onclick="updateNotif(<?= $notif['id'] ?>, 'delete')">
                        <ion-icon name="close-outline"></ion-icon>
                    </button>

                    <div class="notif-icon">
                        <?php if (!empty($notif['picture'])): ?>
                            <img src="<?= BASE_URL ?>/vue/assets/images/<?= htmlspecialchars($notif['picture']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                        <?php else: ?>
                            <img src="<?= BASE_URL ?>/vue/assets/images/default.jpg" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                        <?php endif; ?>
                    </div>

                    <div class="notif-content">
                        <?php if ($notif['type'] === 'new_memory' && $notif['reference_id']): ?>
                            <a href="<?= BASE_URL ?>/vue/pages/post.php?id=<?= $notif['reference_id'] ?>" 
                               class="notif-link" 
                               onclick="updateNotif(<?= $notif['id'] ?>, 'mark_as_read')">
                                <p class="notif-text"><?= htmlspecialchars($notif['content']) ?></p>
                                <?php if (isset($apercus[$notif['reference_id']])): ?>
                                    <?php $apercu = $apercus[$notif['reference_id']]; ?>
                                    <div class="notif-apercu">
                                        <?php if ($apercu['type'] === 'photo' && $apercu['file_path']): ?>
                                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($apercu['file_path']) ?>" alt="">
                                        <?php elseif ($apercu['type'] === 'video'): ?>
                                            <div class="notif-apercu-icon"><ion-icon name="videocam-outline"></ion-icon></div>
                                        <?php elseif ($apercu['type'] === 'audio'): ?>
                                            <div class="notif-apercu-icon"><ion-icon name="musical-notes-outline"></ion-icon></div>
                                        <?php elseif ($apercu['type'] === 'note'): ?>
                                            <div class="notif-apercu-note"><?= htmlspecialchars(substr($apercu['content'] ?? '', 0, 50)) ?>...</div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </a>
                        <?php else: ?>
                            <p class="notif-text"><?= htmlspecialchars($notif['content']) ?></p>
                        <?php endif; ?>

                        <span class="notif-time"><?= date('d/m/Y à H:i', strtotime($notif['created_at'])) ?></span>

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

<script>
async function updateNotif(id, action) {
    // Si c'est un lien (mark_as_read), on n'empêche pas la navigation, on lance juste l'appel
    const response = await fetch('../../controler/notif_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, action: action })
    });
    
    if (response.ok && action === 'delete') {
        const item = document.querySelector(`.notif-item[data-id="${id}"]`);
        if (item) {
            item.style.transition = '0.3s';
            item.style.opacity = '0';
            item.style.transform = 'translateX(20px)';
            setTimeout(() => item.remove(), 300);
        }
    }
}
</script>

</body>
</html>