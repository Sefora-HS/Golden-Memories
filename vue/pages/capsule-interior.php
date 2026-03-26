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

//si aucune capsule associée, redirection vers la page capsules
if (!$capsuleId) {
    header('Location: ' . BASE_URL . '/vue/pages/capsules.php');
    exit;
}

// Récupère la capsule si elle appartient à l'utilisateur OU si elle a été partagée avec lui
$stmt = $bdd->prepare("
    SELECT tc.*, tc.capsule_title, m.title AS memory_title, m.type, m.file_path, m.content,
           m.created_at AS memory_created_at, m.user_id AS owner_id
    FROM time_capsules tc
    JOIN memories m ON tc.memory_id = m.id
    WHERE tc.id = :id
      AND (
          m.user_id = :user_id
          OR EXISTS (
              SELECT 1 FROM capsule_shared cs
              WHERE cs.capsule_id = tc.id AND cs.user_id = :user_id2
          )
      )
");
$stmt->execute([':id' => $capsuleId, ':user_id' => $userId, ':user_id2' => $userId]);
$capsule = $stmt->fetch(PDO::FETCH_ASSOC);

// si aucune capsule trouvée, redirection
if (!$capsule) {
    header('Location: ' . BASE_URL . '/vue/pages/capsules.php');
    exit;
}

// timezone explicite pour cohérence local/ligne
$tz       = new DateTimeZone('Europe/Paris');
$now      = new DateTime('now', $tz);
$unlockAt = new DateTime($capsule['unlock_at'], $tz);
$isOpen   = ($capsule['is_open'] == 1 || $now >= $unlockAt);

// si le délai n'est pas encore passé alors redirection
if (!$isOpen) {
    header('Location: ' . BASE_URL . '/vue/pages/capsules.php');
    exit;
}

// si date dépassée alors modification de la capsule et marquée comme ouverte
if ($capsule['is_open'] == 0) {
    $bdd->prepare("UPDATE time_capsules SET is_open = 1 WHERE id = :id")
        ->execute([':id' => $capsuleId]);
}

// L'utilisateur est-il le propriétaire ?
$isOwner = ($capsule['owner_id'] == $userId);

// Récupère les amis n'ayant pas encore reçu cette capsule (seulement si propriétaire)
$shareAmis = [];
if ($isOwner) {
    $stmtShareAmis = $bdd->prepare("
        SELECT u.id, u.username, u.picture
        FROM friends f
        JOIN users u ON f.friend_id = u.id
        WHERE f.user_id = :user_id AND f.status = 'accepted'
          AND NOT EXISTS (
              SELECT 1 FROM capsule_shared cs
              WHERE cs.capsule_id = :capsule_id AND cs.user_id = u.id
          )
        ORDER BY u.username ASC
    ");
    $stmtShareAmis->execute([':user_id' => $userId, ':capsule_id' => $capsuleId]);
    $shareAmis = $stmtShareAmis->fetchAll(PDO::FETCH_ASSOC);
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

// Messages flash
$flashShared        = isset($_GET['shared'])         && $_GET['shared']         == '1';
$flashAlreadyShared = isset($_GET['already_shared']) && $_GET['already_shared'] == '1';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vue/assets/css/app.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <title>Golden Memories</title>
</head>
<body>

    <!-- bar du haut -->
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

    <!-- messages flash -->
    <?php if ($flashShared): ?>
        <div class="share-success" style="margin:0.8rem 1rem;border-radius:12px;">
            <ion-icon name="checkmark-circle-outline"></ion-icon>
            Capsule partagée avec succès !
        </div>
    <?php elseif ($flashAlreadyShared): ?>
        <div class="share-success" style="margin:0.8rem 1rem;border-radius:12px;background:#888;">
            <ion-icon name="information-circle-outline"></ion-icon>
            Déjà partagée avec cet ami.
        </div>
    <?php endif; ?>

    <!-- contenu de la capsule -->
    <div class="ci-body">
        <h1 class="ci-title">
            <?= htmlspecialchars($capsule['capsule_title'] ?? $capsule['memory_title'] ?? 'Sans titre') ?>
        </h1>
        <div class="ci-title-underline"></div>

        <div class="ci-meta">
            <span class="ci-meta-pill">
                <ion-icon name="lock-open-outline"></ion-icon>
                Ouverte le <?= (new DateTime($capsule['unlock_at'], $tz))->format('d/m/Y') ?>
            </span>
            <span class="ci-meta-pill">
                <ion-icon name="calendar-outline"></ion-icon>
                Créée le <?= (new DateTime($capsule['created_at'], $tz))->format('d/m/Y') ?>
            </span>
        </div>

        <?php if ($capsule['type'] === 'photo' && $capsule['file_path']): ?>
            <div class="ci-polaroid-wrap">
                <div class="ci-tape"></div>
                <div class="ci-polaroid" onclick="openModal('<?= BASE_URL ?>/<?= htmlspecialchars($capsule['file_path']) ?>')">
                    <img class="ci-polaroid-img"
                         src="<?= BASE_URL ?>/<?= htmlspecialchars($capsule['file_path']) ?>"
                         alt="<?= htmlspecialchars($capsule['memory_title'] ?? '') ?>">
                    <p class="ci-polaroid-caption">
                        <?= htmlspecialchars($capsule['memory_title'] ?: $capsule['capsule_title'] ?: 'Sans titre') ?>
                    </p>
                </div>
            </div>

        <?php elseif ($capsule['type'] === 'video' && $capsule['file_path']): ?>
            <div class="ci-polaroid-wrap">
                <div class="ci-tape"></div>
                <div class="ci-polaroid-video">
                    <video src="<?= BASE_URL ?>/<?= htmlspecialchars($capsule['file_path']) ?>" controls></video>
                    <p class="ci-polaroid-caption">
                        <?= htmlspecialchars($capsule['memory_title'] ?: $capsule['capsule_title'] ?: 'Sans titre') ?>
                    </p>
                </div>
            </div>

        <?php elseif ($capsule['type'] === 'audio' && $capsule['file_path']): ?>
            <div class="ci-audio-wrap">
                <div class="ci-audio-disc">
                    <ion-icon name="musical-notes-outline"></ion-icon>
                </div>
                <?php if ($capsule['memory_title']): ?>
                    <p class="ci-audio-label"><?= htmlspecialchars($capsule['memory_title']) ?></p>
                <?php endif; ?>
                <audio src="<?= BASE_URL ?>/<?= htmlspecialchars($capsule['file_path']) ?>" controls></audio>
            </div>

        <?php elseif ($capsule['type'] === 'note'): ?>
            <div class="ci-note-wrap">
                <span class="ci-note-quote">"</span>
                <p class="ci-note-text"><?= htmlspecialchars($capsule['content'] ?? '') ?></p>
            </div>
        <?php endif; ?>

        <!-- Bouton partager — uniquement pour le propriétaire -->
        <?php if ($isOwner): ?>
            <button class="ci-share-btn" onclick="toggleShare()">
                <ion-icon name="share-social-outline"></ion-icon>
                Partager cette capsule
            </button>
        <?php endif; ?>

    </div>

    <!-- modal plein écran -->
    <div class="ci-modal" id="modal" onclick="closeModal(event)">
        <button class="ci-modal-x" onclick="closeModal()">
            <ion-icon name="close-outline"></ion-icon>
        </button>
        <div id="modalContent"></div>
    </div>

    <!-- Panel de partage (uniquement pour le propriétaire) -->
    <?php if ($isOwner): ?>
    <div class="share-overlay" id="share-overlay" onclick="closeShare(event)">
        <div class="share-panel">
            <div class="share-handle"></div>
            <h3 class="share-title">Partager avec un ami</h3>
            <div class="share-search">
                <input type="text" id="share-search-input" placeholder="Rechercher un ami..." oninput="filterAmis()">
            </div>
            <div class="share-amis" id="share-amis">
                <?php if (empty($shareAmis)): ?>
                    <p class="share-empty">Tous tes amis ont déjà reçu cette capsule 🎉</p>
                <?php else: ?>
                    <?php foreach ($shareAmis as $ami): ?>
                        <div class="share-ami-item" data-username="<?= htmlspecialchars($ami['username']) ?>">
                            <div class="share-ami-avatar">
                                <img src="<?= BASE_URL ?>/vue/assets/images/<?= htmlspecialchars($ami['picture'] ?? 'default.jpg') ?>" alt="">
                            </div>
                            <span class="share-ami-username">@<?= htmlspecialchars($ami['username']) ?></span>
                            <a href="<?= BASE_URL ?>/controler/share_capsule.php?capsule_id=<?= $capsuleId ?>&to=<?= $ami['id'] ?>" class="share-ami-btn">
                                Envoyer
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

<?php include './templates/navbar.php'; ?>

<script>
    if ("serviceWorker" in navigator) {
        navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js');
    }
    function toggleShare() {
        document.getElementById('share-overlay').classList.toggle('visible');
    }
    function closeShare(e) {
        if (e.target === document.getElementById('share-overlay')) {
            document.getElementById('share-overlay').classList.remove('visible');
        }
    }
    function filterAmis() {
        const query = document.getElementById('share-search-input').value.toLowerCase();
        document.querySelectorAll('.share-ami-item').forEach(item => {
            const username = item.dataset.username.toLowerCase();
            item.style.display = username.includes(query) ? 'flex' : 'none';
        });
    }
</script>
<script src="<?= BASE_URL ?>/vue/assets/js/app.js?v=<?= time() ?>"></script>
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>
