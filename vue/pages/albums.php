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

//préparer une requête SQL de manière sécurisés pour récupérer l'id, le titre, la date de création et le nombre de souvenirs que contient un album
$stmt = $bdd->prepare("
    SELECT 
        a.id, a.title, a.created_at,
        COUNT(m.id) AS nbr_memories,
        (SELECT m2.file_path FROM memories m2 WHERE m2.album_id = a.id AND m2.type = 'photo' AND m2.file_path IS NOT NULL ORDER BY m2.created_at ASC LIMIT 1) AS cover_image
    FROM albums a
    LEFT JOIN memories m ON m.album_id = a.id
    WHERE a.user_id = :uid AND a.is_shared = 0
    GROUP BY a.id
    ORDER BY a.created_at DESC
");
$stmt->execute([':uid' => $userId]);
$albums = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vue/assets/css/app.css?v=<?= time() ?>">
    <title>Golden Memories</title>
</head>
<body>

<?php include './templates/header.php'; ?>

<h1 class="title">Mes Albums</h1>
<p class="soustitre">"Organisez tous vos souvenirs ici !"</p>

<div class="albums-grid">
    <?php if (empty($albums)): ?>
        <div class="albums-empty">
            <ion-icon name="images-outline"></ion-icon>
            <p>Aucun album pour l'instant.</p>
        </div>
    <?php else: ?>
        <?php foreach ($albums as $album): ?>
        <div class="album-card">

            <?php if ($album['cover_image']): ?>
                <img
                    class="album-card-cover"
                    src="<?= BASE_URL . '/' . htmlspecialchars($album['cover_image']) ?>"
                    alt="Couverture de l'album"
                >
            <?php else: ?>
                <div class="album-card-cover--placeholder">
                    <ion-icon name="images-outline"></ion-icon>
                </div>
            <?php endif; ?>

            <div class="album-card-body">

                <h2 class="album-card-title">
                    <?= htmlspecialchars($album['title']) ?>
                </h2>

                <div class="album-card-meta">
                    <span>
                        <ion-icon name="person-outline"></ion-icon>
                        @<?= htmlspecialchars($userConnecte['username']) ?>
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
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include './templates/navbar.php'; ?>

<script type="module" src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>