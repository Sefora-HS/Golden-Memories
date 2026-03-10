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

        <!-- Affichage des souvenirs -->
        <?php if (empty($souvenirs)): ?>
            <!-- Si aucun souvenirs on affiche : -->
            <div class="bento-empty">
                <ion-icon name="images-outline"></ion-icon>
                <p>Aucun souvenir pour l'instant...</p>
            </div>

        <?php else: ?>

            <?php
            // Tableau d'icon ionicons pour afficher en fonction du type de fichier
            $icons = [
                'photo' => 'image-outline',
                'video' => 'videocam-outline',
                'audio' => 'musical-notes-outline',
                'note'  => 'document-text-outline'
            ];
            ?>

            <!-- boucle foreach pour afficher les souvenirs -->
            <?php foreach ($souvenirs as $index => $souvenir): ?>

                <!-- Création d'une div pour afficher le souvenir avec une taille dynamique (1 / 5 est grande) + type mise en forme differement avec css  -->
                <div class="bento-card <?= ($index % 5 === 0) ? 'bento-large' : 'bento-small' ?> type-<?= $souvenir['type'] ?>">

                <!-- Si le souvenir est une photo et possède un chemin "file_path afficher une image avec information correspondante -->
                    <?php if ($souvenir['type'] === 'photo' && $souvenir['file_path']): ?>
                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($souvenir['file_path']) ?>"
                             alt="<?= htmlspecialchars($souvenir['title'] ?? 'Photo') ?>">

                <!-- Si le souvenir est une video et possède un chemin "file_path afficher une video avec information correspondante + controls pour gérer la lecture -->
                    <?php elseif ($souvenir['type'] === 'video' && $souvenir['file_path']): ?>
                        <video src="<?= BASE_URL ?>/<?= htmlspecialchars($souvenir['file_path']) ?>" controls></video>

                <!-- Si le souvenir est une note création d'une div qui contient et mets en forme le texte + un icon document texte -->
                    <?php elseif ($souvenir['type'] === 'note'): ?>
                        <div class="note-content">
                            <ion-icon name="document-text-outline"></ion-icon>
                            <!-- Protection contre attaque XSS avec htmlspecialchars + nl2br qui transforme les retour à la ligne en balise br -->
                            <p><?= nl2br(htmlspecialchars($souvenir['content'] ?? '')) ?></p>
                        </div>

                <!-- Si le souvenir est un fichier audio création d'une div qui contient un icon note de musique + un lecteur avec controls -->
                    <?php elseif ($souvenir['type'] === 'audio' && $souvenir['file_path']): ?>
                        <div class="audio-content">
                            <ion-icon name="musical-notes-outline"></ion-icon>
                            <audio src="<?= BASE_URL ?>/<?= htmlspecialchars($souvenir['file_path']) ?>" controls></audio>
                        </div>
                    <?php endif; ?>

                    <!-- Div qui contient les informations du souvenir -->
                    <div class="bento-overlay">
                        <span class="bento-type">
                            <!-- On récupère ici l'icon du type dans le tableau précedemment crée -->
                            <ion-icon name="<?= $icons[$souvenir['type']] ?>"></ion-icon>
                        </span>

                        <!-- Si le souvenir possède un titre on l'affiche -->
                        <?php if ($souvenir['title']): ?>
                            <h3><?= htmlspecialchars($souvenir['title']) ?></h3>
                        <?php endif; ?>

                        <!-- Affichage de la date de création du souvenir "created_at" -->
                        <span class="bento-date">
                            <ion-icon name="calendar-outline"></ion-icon>
                            <?= date('d/m/Y', strtotime($souvenir['created_at'])) ?>
                        </span>

                        <!-- Si le souvenir appartient à un album on affiche le nom ici -->
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

<!-- Inclusion de la navbar -->
<?php include './vue/pages/templates/navbar.php'; ?>

<!-- Script liens -->
<script src="<?= BASE_URL ?>/vue/assets/js/app.js?v=<?= time() ?>"></script>
<script type="module" src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>