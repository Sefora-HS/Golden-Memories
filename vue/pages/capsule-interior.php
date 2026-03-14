<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Golden-Memories/modele/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/vue/pages/home.php');
    exit;
}

$userId    = $_SESSION['user']['id'];
$capsuleId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$capsuleId) {
    header('Location: ' . BASE_URL . '/vue/pages/capsules.php');
    exit;
}

$stmt = $bdd->prepare("
    SELECT tc.*, tc.capsule_title, m.title AS memory_title, m.type, m.file_path, m.content, m.created_at AS memory_created_at
    FROM time_capsules tc
    JOIN memories m ON tc.memory_id = m.id
    WHERE tc.id = :id AND m.user_id = :user_id
");
$stmt->execute([':id' => $capsuleId, ':user_id' => $userId]);
$capsule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$capsule) {
    header('Location: ' . BASE_URL . '/vue/pages/capsules.php');
    exit;
}

$now      = new DateTime();
$unlockAt = new DateTime($capsule['unlock_at']);
$isOpen   = ($capsule['is_open'] == 1 || $now >= $unlockAt);

if (!$isOpen) {
    header('Location: ' . BASE_URL . '/vue/pages/capsules.php');
    exit;
}

if ($capsule['is_open'] == 0) {
    $bdd->prepare("UPDATE time_capsules SET is_open = 1 WHERE id = :id")
        ->execute([':id' => $capsuleId]);
}

$typeLabels = [
    'photo' => 'Photo',
    'video' => 'Vidéo',
    'audio' => 'Audio',
    'note'  => 'Note',
];
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

    <!-- Topbar -->
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

    <!-- Corps -->
    <div class="ci-body">

        <!-- Titre -->
        <h1 class="ci-title">
            <?= htmlspecialchars($capsule['capsule_title'] ?? $capsule['memory_title'] ?? 'Sans titre') ?>
        </h1>
        <div class="ci-title-underline"></div>

        <!-- Méta -->
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

        <!-- Contenu -->
        <?php if ($capsule['type'] === 'photo' && $capsule['file_path']): ?>

            <div class="ci-polaroid-wrap">
                <div class="ci-tape"></div>
                <div class="ci-polaroid"
                     onclick="openModal('<?= BASE_URL ?>/<?= htmlspecialchars($capsule['file_path']) ?>')">
                    <img class="ci-polaroid-img"
                         src="<?= BASE_URL ?>/<?= htmlspecialchars($capsule['file_path']) ?>"
                         alt="<?= htmlspecialchars($capsule['memory_title'] ?? '') ?>">
                    <p class="ci-polaroid-caption">
                        <?= htmlspecialchars($capsule['capsule_title'] ?? $capsule['memory_title'] ?? 'Sans titre') ?>
                    </p>
                </div>
            </div>

        <?php elseif ($capsule['type'] === 'video' && $capsule['file_path']): ?>

            <div class="ci-polaroid-wrap">
                <div class="ci-tape"></div>
                <div class="ci-polaroid-video">
                    <video src="<?= BASE_URL ?>/<?= htmlspecialchars($capsule['file_path']) ?>"
                           controls></video>
                    <p class="ci-polaroid-caption" style="font-family:'Caveat',cursive;text-align:center;padding-top:8px;color:#555;">
                        <?= htmlspecialchars($capsule['memory_title'] ?? '') ?>
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
                <audio src="<?= BASE_URL ?>/<?= htmlspecialchars($capsule['file_path']) ?>"
                       controls></audio>
            </div>

        <?php elseif ($capsule['type'] === 'note'): ?>

            <div class="ci-note-wrap">
                <span class="ci-note-quote">"</span>
                <p class="ci-note-text"><?= htmlspecialchars($capsule['content'] ?? '') ?></p>
            </div>

        <?php endif; ?>

    </div>

    <!-- Modale photo plein écran -->
    <div class="ci-modal" id="modal" onclick="closeModal(event)">
        <button class="ci-modal-x" onclick="closeModal()">
            <ion-icon name="close-outline"></ion-icon>
        </button>
        <div id="modalContent"></div>
    </div>

<?php include './templates/navbar.php'; ?>


<script src="<?= BASE_URL ?>/vue/assets/js/app.js?v=<?= time() ?>"></script>
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>