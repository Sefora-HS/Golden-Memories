<?php
//Import du fichier config et de la class User
require_once '../../modele/config.php';
require_once '../../modele/User.php';

//Vérification si un utilisateur est connecté sinon redirection vers la page d'accueil
if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/vue/pages/home.php');
    exit;
}

//Récupération + stockage des informations et id de l'utilisateur pour les requêtes
$userConnecte = $_SESSION['user'];
$userId = $userConnecte['id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ' . BASE_URL . '/vue/pages/albums.php');
    exit;
}
$albumId = (int)$_GET['id'];

$stmtAlbum = $bdd->prepare("
    SELECT a.*
    FROM albums a
    LEFT JOIN album_members am ON am.album_id = a.id AND am.user_id = :uid
    WHERE a.id = :album_id
      AND (a.user_id = :uid2 OR am.user_id IS NOT NULL)
    LIMIT 1
");
$stmtAlbum->execute([
    ':uid'      => $userId,
    ':album_id' => $albumId,
    ':uid2'     => $userId,
]);
$album = $stmtAlbum->fetch(PDO::FETCH_ASSOC);

// Redirection si l'album n'existe pas ou n'appartient pas à l'utilisateur
if (!$album) {
    header('Location: ' . BASE_URL . '/vue/pages/albums.php');
    exit;
}

$stmtMemories = $bdd->prepare("
    SELECT id, type, title, file_path, content, created_at
    FROM memories
    WHERE album_id = :album_id
    ORDER BY created_at ASC
");
$stmtMemories->execute([':album_id' => $albumId]);
$memories = $stmtMemories->fetchAll(PDO::FETCH_ASSOC);

// Image de couverture = première photo de l'album
$coverImage = null;
foreach ($memories as $m) {
    if ($m['type'] === 'photo' && $m['file_path']) {
        $coverImage = $m['file_path'];
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vue/assets/css/app.css?v=<?= time() ?>">
    <title><?= htmlspecialchars($album['title']) ?> — Golden Memories</title>
</head>
<body>

<div class="partages-pages">

    <!-- Hero : image de couverture de l'album -->
    <div class="album-hero">
        <?php if ($coverImage): ?>
            <img src="<?= BASE_URL . '/' . htmlspecialchars($coverImage) ?>" alt="Couverture de l'album" class="hero-img">
        <?php else: ?>
            <div class="hero-img hero-img--placeholder">
                <ion-icon name="images-outline"></ion-icon>
            </div>
        <?php endif; ?>

        <div class="hero-overlay">
            <a href="<?= BASE_URL ?>/vue/pages/albums.php" class="hero-btn-back">
                <ion-icon name="arrow-back-outline"></ion-icon>
                <span>Mes albums</span>
            </a>
        </div>
    </div>

    <!-- Titre de l'album -->
    <div class="album-header-info">
        <h1 class="album-main-title"><?= htmlspecialchars($album['title']) ?></h1>
        <p class="album-meta-info">
            <ion-icon name="calendar-outline"></ion-icon>
            <?= date('d/m/Y', strtotime($album['created_at'])) ?>
            &nbsp;·&nbsp;
            <ion-icon name="sparkles-outline"></ion-icon>
            <?= count($memories) ?> souvenir<?= count($memories) > 1 ? 's' : '' ?>
        </p>
    </div>

</div><!-- fin .partages-pages -->

<!-- Grille des souvenirs -->
<?php if (empty($memories)): ?>
    <div class="albums-empty">
        <ion-icon name="images-outline"></ion-icon>
        <p>Aucun souvenir dans cet album pour l'instant.</p>
    </div>
<?php else: ?>
    <div class="bento-grid">
        <?php foreach ($memories as $memory): ?>

            <?php if ($memory['type'] === 'photo' && $memory['file_path']): ?>
                <!-- Souvenir photo -->
                <div class="bento-item">
                    <img
                        src="<?= BASE_URL . '/' . htmlspecialchars($memory['file_path']) ?>"
                        alt="<?= htmlspecialchars($memory['title'] ?? 'Photo') ?>"
                        title="<?= htmlspecialchars($memory['title'] ?? '') ?>"
                    >
                </div>

            <?php elseif ($memory['type'] === 'video' && $memory['file_path']): ?>
                <!-- Souvenir vidéo -->
                <div class="bento-item item-large">
                    <video controls>
                        <source src="<?= BASE_URL . '/' . htmlspecialchars($memory['file_path']) ?>" type="video/mp4">
                        Votre navigateur ne supporte pas la vidéo.
                    </video>
                    <?php if ($memory['title']): ?>
                        <p class="bento-item-label"><?= htmlspecialchars($memory['title']) ?></p>
                    <?php endif; ?>
                </div>

            <?php elseif ($memory['type'] === 'audio' && $memory['file_path']): ?>
                <!-- Souvenir audio -->
                <div class="bento-item item-note">
                    <h3><ion-icon name="musical-notes-outline"></ion-icon> <?= htmlspecialchars($memory['title'] ?? 'Audio') ?></h3>
                    <audio controls style="width:100%; margin-top:0.5rem;">
                        <source src="<?= BASE_URL . '/' . htmlspecialchars($memory['file_path']) ?>" type="audio/mpeg">
                        Votre navigateur ne supporte pas l'audio.
                    </audio>
                </div>

            <?php elseif ($memory['type'] === 'note'): ?>
                <!-- Souvenir note -->
                <div class="bento-item item-note">
                    <?php if ($memory['title']): ?>
                        <h3><?= htmlspecialchars($memory['title']) ?></h3>
                    <?php else: ?>
                        <h3>NOTES :</h3>
                    <?php endif; ?>
                    <p><?= nl2br(htmlspecialchars($memory['content'] ?? '')) ?></p>
                    <small><?= date('d/m/Y', strtotime($memory['created_at'])) ?></small>
                </div>

            <?php endif; ?>

        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include './templates/navbar.php'; ?>

<script src="<?= BASE_URL ?>/vue/assets/js/app.js?v=<?= time() ?>"></script>
<script type="module" src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>