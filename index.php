<?php
require_once 'modele/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/vue/pages/home.php');
    exit;
}

$userConnecte = $_SESSION['user'];
$userId = $userConnecte['id'];

$stmt = $bdd->prepare("
    SELECT m.*, a.title as album_title 
    FROM memories m
    LEFT JOIN albums a ON m.album_id = a.id
    WHERE m.user_id = :user_id
    ORDER BY m.created_at DESC
");
$stmt->execute([':user_id' => $userId]);
$souvenirs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./vue/assets/css/app.css">
    <title>Golden Memories</title>
</head>
<body>

<?php include './vue/pages/templates/header.php'; ?>

<h1 class="title">Mes souvenirs</h1>
<p class="soustitre">"Vos moments précieux, tous au même endroit"</p>

<div class="contenu">
    <div class="bento-grid">

        <?php if (empty($souvenirs)): ?>
            <div class="bento-empty">
                <ion-icon name="images-outline"></ion-icon>
                <p>Aucun souvenir pour l'instant...</p>
            </div>

        <?php else: ?>
            <?php
            $icons = [
                'photo' => 'image-outline',
                'video' => 'videocam-outline',
                'audio' => 'musical-notes-outline',
                'note'  => 'document-text-outline'
            ];
            ?>
            <?php foreach ($souvenirs as $index => $souvenir): ?>
                <div class="bento-card <?= ($index % 5 === 0) ? 'bento-large' : 'bento-small' ?> type-<?= $souvenir['type'] ?>">

                    <?php if ($souvenir['type'] === 'photo' && $souvenir['file_path']): ?>
                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($souvenir['file_path']) ?>"
                             alt="<?= htmlspecialchars($souvenir['title'] ?? 'Photo') ?>">

                    <?php elseif ($souvenir['type'] === 'video' && $souvenir['file_path']): ?>
                        <video src="<?= BASE_URL ?>/<?= htmlspecialchars($souvenir['file_path']) ?>" controls></video>

                    <?php elseif ($souvenir['type'] === 'note'): ?>
                        <div class="note-content">
                            <ion-icon name="document-text-outline"></ion-icon>
                            <p><?= nl2br(htmlspecialchars($souvenir['content'] ?? '')) ?></p>
                        </div>

                    <?php elseif ($souvenir['type'] === 'audio' && $souvenir['file_path']): ?>
                        <div class="audio-content">
                            <ion-icon name="musical-notes-outline"></ion-icon>
                            <audio src="<?= BASE_URL ?>/<?= htmlspecialchars($souvenir['file_path']) ?>" controls></audio>
                        </div>
                    <?php endif; ?>

                    <div class="bento-overlay">
                        <span class="bento-type">
                            <ion-icon name="<?= $icons[$souvenir['type']] ?>"></ion-icon>
                        </span>
                        <?php if ($souvenir['title']): ?>
                            <h3><?= htmlspecialchars($souvenir['title']) ?></h3>
                        <?php endif; ?>
                        <span class="bento-date">
                            <ion-icon name="calendar-outline"></ion-icon>
                            <?= date('d/m/Y', strtotime($souvenir['created_at'])) ?>
                        </span>
                        <?php if ($souvenir['album_title']): ?>
                            <span class="bento-album">
                                <ion-icon name="albums-outline"></ion-icon>
                                <?= htmlspecialchars($souvenir['album_title']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endforeach; ?>

        <?php endif; ?>

    </div>
</div>

<?php include './vue/pages/templates/navbar.php'; ?>

<script src="./vue/assets/js/app.js"></script>
<script type="module" src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>