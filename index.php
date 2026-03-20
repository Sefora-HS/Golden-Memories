<?php
//import du fichier config
require_once 'modele/config.php';

//Vérification si un utilisateur est connecté sinon redirection vers la page d'accueil
if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/vue/pages/home.php');
    exit;
}

//Récupération + stockage des informations et id de l'utilisateur pour les requêtes
$userConnecte = $_SESSION['user'];
$userId = $userConnecte['id'];

//préparer une requête SQL de manière sécurisés pour récupérer tous les souvenirs d'un utilisateur et stocker dans $souvenirs
$stmt = $bdd->prepare("
    SELECT m.*, a.title as album_title 
    FROM memories m
    LEFT JOIN albums a ON m.album_id = a.id
    LEFT JOIN time_capsules tc ON tc.memory_id = m.id
    WHERE m.user_id = :user_id
      AND (tc.id IS NULL OR tc.is_open = 1)
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
    <link rel="stylesheet" href="<?= BASE_URL ?>/vue/assets/css/app.css?v=<?= time() ?>">
    <title>Golden Memories</title>
</head>
<body>

<!-- Inclusion du header -->
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

                <a href="<?= BASE_URL ?>/vue/pages/post.php?id=<?= $souvenir['id'] ?>&from=index" class="bento-card <?= ($index % 5 === 0) ? 'bento-large' : 'bento-small' ?> type-<?= $souvenir['type'] ?>">

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

                </a>
            <?php endforeach; ?>

        <?php endif; ?>

    </div>
</div>

<!-- Inclusion de la navbar -->
<?php include './vue/pages/templates/navbar.php'; ?>

<script src="<?= BASE_URL ?>/vue/assets/js/app.js?v=<?= time() ?>"></script>
<script type="module" src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>