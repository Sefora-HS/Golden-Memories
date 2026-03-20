<?php
// Récupère les infos de l'utilisateur connecté depuis la db
include_once '../../modele/config.php';
include_once '../../modele/User.php';

$userConnecte = null;
$nbSouvenirs = 0;
$nbAlbums = 0;
$nbAmis = 0;
$photos = [];

if (isset($_SESSION['user']['id'])) {
    $userId = $_SESSION['user']['id'];
    $userConnecte = User::getById($bdd, $userId);

    // Compte le nombre de souvenirs de l'utilisateur
    $stmtSouvenirs = $bdd->prepare("SELECT COUNT(*) FROM memories WHERE user_id = :id");
    $stmtSouvenirs->execute([':id' => $userId]);
    $nbSouvenirs = $stmtSouvenirs->fetchColumn();

    // Compte le nombre d'albums de l'utilisateur
    $stmtAlbums = $bdd->prepare("SELECT COUNT(*) FROM albums WHERE user_id = :id");
    $stmtAlbums->execute([':id' => $userId]);
    $nbAlbums = $stmtAlbums->fetchColumn();

    // Compte le nombre d'amis de l'utilisateur
    $stmtAmis = $bdd->prepare("SELECT COUNT(*) FROM friends WHERE user_id = :id");
    $stmtAmis->execute([':id' => $userId]);
    $nbAmis = $stmtAmis->fetchColumn();

    // Récupère tous les souvenirs de l'utilisateur depuis la db
    $stmtPhotos = $bdd->prepare("SELECT id, file_path, type FROM memories WHERE user_id = :id ORDER BY created_at ASC");
    $stmtPhotos->execute([':id' => $userId]);
    $photos = $stmtPhotos->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vue/assets/css/app.css?v=<?= time() ?>">
    <title>Golden Memories — Profil</title>
</head>
<body>

<div class="profil-page">

    <!-- Header navigation -->
    <div class="profil-top">
        <a href="<?= BASE_URL ?>" class="form-back">
            <ion-icon name="arrow-back-outline"></ion-icon>
        </a>
        <!-- Affiche le @ de l'utilisateur connecté -->
        <p class="profil-arobase">@<?= htmlspecialchars($userConnecte['username'] ?? 'utilisateur') ?></p>
        <a href="./parametres.php" class="parametre">
            <ion-icon name="cog-outline"></ion-icon>
        </a>
    </div>

    <!-- Photo de profil + nom -->
    <div class="profil-header">
        <div class="profil-avatar">
            <img src="<?= BASE_URL ?>/vue/assets/images/<?= htmlspecialchars($userConnecte['picture'] ?? 'default.jpg') ?>" alt="Photo de profil">
        </div>
        <!-- Affiche le nom de l'utilisateur connecté -->
        <h2 class="profil-nom"><?= htmlspecialchars($userConnecte['username'] ?? 'Utilisateur') ?></h2>
        <p class="profil-bio">✨ Collectionneuse de jolis souvenirs</p>
    </div>

    <!-- Stats -->
    <div class="profil-stats">
        <div class="profil-stat">
            <span class="stat-nombre"><?= $nbSouvenirs ?></span>
            <span class="stat-label">Souvenirs</span>
        </div>
        <div class="profil-stat">
            <span class="stat-nombre"><?= $nbAlbums ?></span>
            <span class="stat-label">Albums</span>
        </div>
        <div class="profil-stat">
            <!-- Affiche le nombre d'amis de l'utilisateur -->
            <span class="stat-nombre"><?= $nbAmis ?></span>
            <span class="stat-label">Amis</span>
        </div>
    </div>

    <!-- Grille de tous les souvenirs de l'utilisateur -->
    <div class="profil-grille">
        <?php if (empty($photos)): ?>
            <p class="profil-empty">Aucune photo pour l'instant. Poste ton premier souvenir !</p>
        <?php else: ?>
            <?php foreach ($photos as $photo): ?>
                <a href="<?= BASE_URL ?>/vue/pages/post.php?id=<?= $photo['id'] ?>&from=profil">
                    <?php if ($photo['type'] === 'photo'): ?>
                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($photo['file_path']) ?>" alt="Souvenir">
                    <?php elseif ($photo['type'] === 'video'): ?>
                        <video src="<?= BASE_URL ?>/<?= htmlspecialchars($photo['file_path']) ?>"></video>
                    <?php elseif ($photo['type'] === 'audio'): ?>
                        <div class="profil-audio">
                            <ion-icon name="musical-notes-outline"></ion-icon>
                        </div>
                    <?php elseif ($photo['type'] === 'note'): ?>
                        <div class="profil-note">
                            <ion-icon name="document-text-outline"></ion-icon>
                        </div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>