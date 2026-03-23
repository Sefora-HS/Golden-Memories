<?php
//import du fichier config
require_once '../../modele/config.php';

//Vérification si un utilisateur est connecté sinon redirection vers la page d'accueil
if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/vue/pages/home.php');
    exit;
}

//Récupération + stockage des infos, id de l'utilisateur et infos de la capsule pour les requêtes
$userId    = $_SESSION['user']['id'];
$capsuleId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

//si auècune capsule associé , redirection vers la page d'accueil
if (!$capsuleId) {
    header('Location: ' . BASE_URL . '/vue/pages/capsules.php');
    exit;
}

// préparer une requête SQL de manière sécurisés pour récupérer les informations de la capsule, titre, type, souvenir, date de création, d'ouverture, contenu, associé à l'utilisateur et l'id
$stmt = $bdd->prepare("
    SELECT tc.*, tc.capsule_title, m.title AS memory_title, m.type, m.file_path, m.content, m.created_at AS memory_created_at
    FROM time_capsules tc
    JOIN memories m ON tc.memory_id = m.id
    WHERE tc.id = :id AND m.user_id = :user_id
");
$stmt->execute([':id' => $capsuleId, ':user_id' => $userId]);
$capsule = $stmt->fetch(PDO::FETCH_ASSOC);

// si aucune capsule appartenant à l'utilisateur , redirection
if (!$capsule) {
    header('Location: ' . BASE_URL . '/vue/pages/capsules.php');
    exit;
}

// variable stockant les dates
$now      = new DateTime();
$unlockAt = new DateTime($capsule['unlock_at']);
$isOpen   = ($capsule['is_open'] == 1 || $now >= $unlockAt);

// si le delai n'est pas encore passé alors redirection
if (!$isOpen) {
    header('Location: ' . BASE_URL . '/vue/pages/capsules.php');
    exit;
}

// si date depassé alors modification de la capsule et marqué comme ouverte
if ($capsule['is_open'] == 0) {
    $bdd->prepare("UPDATE time_capsules SET is_open = 1 WHERE id = :id")
        ->execute([':id' => $capsuleId]);
}

//attribution du type du souvenir à une variable pour l'affichage
$typeLabels = [
    'photo' => 'Photo',
    'video' => 'Vidéo',
    'audio' => 'Audio',
    'note'  => 'Note',
];

//idem pour l'icon en fonction du type
$typeIcons = [
    'photo' => 'image-outline',
    'video' => 'videocam-outline',
    'audio' => 'musical-notes-outline',
    'note'  => 'document-text-outline',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vue/assets/css/app.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <title>Golden Memories</title>
<body>

    <!-- bar du haut , regroupant titre, type et bouton pour revenir en arrière -->
    <div class="ci-topbar">
        <a href="<?= BASE_URL ?>/vue/pages/capsules.php" class="ci-back">
            <ion-icon name="arrow-back-outline"></ion-icon>
        </a>
        <span class="ci-topbar-title">Capsule</span>
        <span class="ci-type-tag">
            <ion-icon name="<?= $typeIcons[$capsule['type']] ?>"></ion-icon>
            <?= $typeLabels[$capsule['type']] ?>
        </span>
    </div>


    <!-- contenu de la capsule -->
    <div class="ci-body">
        <!-- titre de la capsule -->
        <h1 class="ci-title">
            <?= htmlspecialchars($capsule['capsule_title'] ?? $capsule['memory_title'] ?? 'Sans titre') ?>
        </h1>
        <div class="ci-title-underline"></div>

        <!-- affichage des dates d'ouverture et de création -->
        <div class="ci-meta">
            <span class="ci-meta-pill">
                <ion-icon name="lock-open-outline"></ion-icon>
                Ouverte le <?= (new DateTime($capsule['unlock_at']))->format('d/m/Y') ?>
            </span>
            <span class="ci-meta-pill">
                <ion-icon name="calendar-outline"></ion-icon>
                Créée le <?= (new DateTime($capsule['created_at']))->format('d/m/Y') ?>
            </span>
        </div>


        <!-- si souvenir est de type photo , affichage souys forme polaroid -->
        <?php if ($capsule['type'] === 'photo' && $capsule['file_path']): ?>

            <div class="ci-polaroid-wrap">
                <div class="ci-tape"></div>
                <!-- visualisation du souvenir en plein ecran -->
                <div class="ci-polaroid" onclick="openModal('<?= BASE_URL ?>/<?= htmlspecialchars($capsule['file_path']) ?>')">
                <!-- image du souvenir -->
                <img class="ci-polaroid-img"
                         src="<?= BASE_URL ?>/<?= htmlspecialchars($capsule['file_path']) ?>"
                         alt="<?= htmlspecialchars($capsule['memory_title'] ?? '') ?>">
                    <!-- titre du souvenir -->
                    <p class="ci-polaroid-caption">
                        <?= htmlspecialchars($capsule['memory_title'] ?: $capsule['capsule_title'] ?: 'Sans titre') ?>
                    </p>
                </div>
            </div>

            <!-- idem si video -->
        <?php elseif ($capsule['type'] === 'video' && $capsule['file_path']): ?>

            <div class="ci-polaroid-wrap">
                <div class="ci-tape"></div>
                <div class="ci-polaroid-video">
                    <video src="<?= BASE_URL ?>/<?= htmlspecialchars($capsule['file_path']) ?>"
                           controls></video>
                    <p class="ci-polaroid-caption">
                        <?= htmlspecialchars($capsule['memory_title'] ?: $capsule['capsule_title'] ?: 'Sans titre') ?>
                    </p>
                </div>
            </div>

            <!-- idem si audio mais avec mise en forme differente -->
        <?php elseif ($capsule['type'] === 'audio' && $capsule['file_path']): ?>

            <div class="ci-audio-wrap">
                <div class="ci-audio-disc">
                    <ion-icon name="musical-notes-outline"></ion-icon>
                </div>
                <?php if ($capsule['memory_title']): ?>
                    <p class="ci-audio-label"><?= htmlspecialchars($capsule['memory_title']) ?></p>
                <?php endif; ?>
                <audio src="<?= BASE_URL ?>/<?= htmlspecialchars($capsule['file_path']) ?>"
                       controls></audio>
            </div>

        <!-- idem si note avec mise en forme differente -->
        <?php elseif ($capsule['type'] === 'note'): ?>

            <div class="ci-note-wrap">
                <span class="ci-note-quote">"</span>
                <p class="ci-note-text"><?= htmlspecialchars($capsule['content'] ?? '') ?></p>
            </div>

        <?php endif; ?>

    </div>

<!-- affichage en plein ecran du souvenir  -->
    <div class="ci-modal" id="modal" onclick="closeModal(event)">
        <button class="ci-modal-x" onclick="closeModal()">
            <ion-icon name="close-outline"></ion-icon>
        </button>
        <div id="modalContent"></div>
    </div>

<!-- inclusion de la navbar -->
<?php include './templates/navbar.php'; ?>

<!-- fichier javascript -->
<script src="<?= BASE_URL ?>/vue/assets/js/app.js?v=<?= time() ?>"></script>
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>