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

//préparer une requête SQL pour récupérer les albums partagés créés par l'utilisateur
//ou auxquels il appartient en tant que membre
$stmt = $bdd->prepare("
    SELECT
        a.id, a.title, a.created_at, a.user_id,
        COUNT(DISTINCT m.id) AS nbr_memories,
        (SELECT m2.file_path FROM memories m2 WHERE m2.album_id = a.id AND m2.type = 'photo' AND m2.file_path IS NOT NULL ORDER BY m2.created_at ASC LIMIT 1) AS cover_image,
        (SELECT COUNT(*) FROM album_members am2 WHERE am2.album_id = a.id) + 1 AS nbr_membres,
        CASE WHEN a.user_id = :uid THEN 'owner' ELSE 'member' END AS role
    FROM albums a
    LEFT JOIN memories m ON m.album_id = a.id
    WHERE a.is_shared = 1
      AND (
          a.user_id = :uid2
          OR EXISTS (SELECT 1 FROM album_members am WHERE am.album_id = a.id AND am.user_id = :uid3)
      )
    GROUP BY a.id
    ORDER BY a.created_at DESC
");
$stmt->execute([':uid' => $userId, ':uid2' => $userId, ':uid3' => $userId]);
$albums = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vue/assets/css/app.css?v=<?= time() ?>">
    <link rel="icon" type="image/png" sizes="60x60" href="<?= BASE_URL ?>/vue/assets/images/favicon.png">
    <title>Golden Memories</title>
</head>
<body>

<!-- inclusion du header -->
<?php include './templates/header.php'; ?>

<h1 class="title">Albums partagés</h1>
<p class="soustitre">"Vos souvenirs à plusieurs, au même endroit !"</p>

<!-- affichage des albums partagés : créés par l'utilisateur ou dont il est membre -->
<div class="albums-grid">
    <?php if (empty($albums)): ?>
        <div class="albums-empty">
            <ion-icon name="people-outline"></ion-icon>
            <p>Aucun album partagé pour l'instant.</p>
        </div>
    <?php else: ?>
        <?php foreach ($albums as $album): ?>
        <a href="<?= BASE_URL ?>/vue/pages/share-album.php?id=<?= (int)$album['id'] ?>" class="album-card">

            <?php if ($album['cover_image']): ?>
                <img
                    class="album-card-cover"
                    src="<?= BASE_URL . '/' . htmlspecialchars($album['cover_image']) ?>"
                    alt="Couverture de l'album"
                >
            <?php else: ?>
                <div class="album-card-cover--placeholder">
                    <ion-icon name="people-outline"></ion-icon>
                </div>
            <?php endif; ?>

            <!-- badge admin ou membre -->
            <span class="album-card-role-badge <?= $album['role'] === 'owner' ? 'badge-owner' : 'badge-member' ?>">
                <?php if ($album['role'] === 'owner'): ?>
                    <ion-icon name="shield-checkmark-outline"></ion-icon> Admin
                <?php else: ?>
                    <ion-icon name="person-outline"></ion-icon> Membre
                <?php endif; ?>
            </span>

            <div class="album-card-body">

                <h2 class="album-card-title">
                    <?= htmlspecialchars($album['title']) ?>
                </h2>

                <div class="album-card-meta">
                    <span>
                        <ion-icon name="people-outline"></ion-icon>
                        <?= $album['nbr_membres'] ?> membre<?= $album['nbr_membres'] > 1 ? 's' : '' ?>
                    </span>
                    <span>
                        <ion-icon name="calendar-outline"></ion-icon>
                        <?= date('d/m/Y', strtotime($album['created_at'])) ?>
                    </span>
                    <span>
                        <ion-icon name="sparkles-outline"></ion-icon>
                        <?= $album['nbr_memories'] ?> souvenir<?= $album['nbr_memories'] > 1 ? 's' : '' ?>
                    </span>
                </div>

            </div>
        </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include './templates/navbar.php'; ?>

<script src="<?= BASE_URL ?>/vue/assets/js/app.js?v=<?= time() ?>"></script>
<script type="module" src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>