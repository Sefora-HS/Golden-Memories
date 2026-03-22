<?php include_once '../../controler/capsules.php'; ?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vue/assets/css/app.css?v=<?= time() ?>">
    <title>Golden Memories — Capsules</title>
</head>
<body>

<div class="capsules-page">

    <!-- En-tête -->
    <div class="form-top">
        <a href="<?= BASE_URL ?>" class="form-back">
            <ion-icon name="arrow-back-outline"></ion-icon>
        </a>
        <h1 class="form-title">Capsules</h1>
    </div>

    <!-- Hero -->
    <div class="capsule-hero">
        <p class="capsule-hero-label">✦ Vos souvenirs temporels</p>
        <p class="capsule-hero-sub">Des moments scellés dans le temps, qui s'ouvrent quand le moment est venu.</p>
    </div>

    <!-- État vide -->
    <?php if (empty($capsules)): ?>
        <div class="capsule-empty">
            <ion-icon name="hourglass-outline"></ion-icon>
            Aucune capsule pour l'instant.<br>Créez un souvenir et programmez son ouverture !
        </div>
    <?php endif; ?>


    <!-- ── À ouvrir (date passée, non encore ouvertes) ── -->
    <?php if (!empty($capsules_ready)): ?>
        <p class="capsule-section-title">🔓 Prêtes à ouvrir</p>
        <?php foreach ($capsules_ready as $c): ?>
            <div class="capsule-card capsule-ready">
                <div class="capsule-icon">
                    <ion-icon name="gift-outline"></ion-icon>
                </div>
                <div class="capsule-info">
                    <h3 class="capsule-title"><?= htmlspecialchars($c['title'] ?? 'Sans titre') ?></h3>
                    <p class="capsule-date">Disponible depuis le <?= date('d/m/Y', strtotime($c['unlock_at'])) ?></p>
                    <a href="?open=<?= $c['capsule_id'] ?>" class="capsule-open-btn">
                        <ion-icon name="lock-open-outline"></ion-icon> Ouvrir maintenant
                    </a>
                </div>
                <div class="capsule-badge capsule-badge-ready">À ouvrir</div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>


    <!-- ── Bientôt ── -->
    <?php if (!empty($capsules_soon)): ?>
        <p class="capsule-section-title">⏳ Bientôt disponibles</p>
        <?php foreach ($capsules_soon as $c): ?>
            <div class="capsule-card capsule-soon">
                <div class="capsule-icon">
                    <ion-icon name="time-outline"></ion-icon>
                </div>
                <div class="capsule-info">
                    <h3 class="capsule-title"><?= htmlspecialchars($c['title'] ?? 'Sans titre') ?></h3>
                    <p class="capsule-date"><?= formatCountdown((int) $c['seconds_left']) ?></p>
                </div>
                <div class="capsule-badge capsule-badge-soon">Bientôt</div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>


    <!-- ── Verrouillées ── -->
    <?php if (!empty($capsules_locked)): ?>
        <p class="capsule-section-title">🔒 Verrouillées</p>
        <?php foreach ($capsules_locked as $c): ?>
            <div class="capsule-card capsule-locked">
                <div class="capsule-icon">
                    <ion-icon name="lock-closed-outline"></ion-icon>
                </div>
                <div class="capsule-info">
                    <h3 class="capsule-title"><?= htmlspecialchars($c['title'] ?? 'Sans titre') ?></h3>
                    <p class="capsule-date">S'ouvre le <?= date('d/m/Y', strtotime($c['unlock_at'])) ?></p>
                </div>
                <div class="capsule-badge capsule-badge-locked">Verrouillée</div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>


    <!-- ── Ouvertes ── -->
    <?php if (!empty($capsules_open)): ?>
        <p class="capsule-section-title">✨ Déjà ouvertes</p>
        <?php foreach ($capsules_open as $c): ?>
            <div class="capsule-card capsule-open">
                <div class="capsule-icon">
                    <ion-icon name="star-outline"></ion-icon>
                </div>
                <div class="capsule-info">
                    <h3 class="capsule-title"><?= htmlspecialchars($c['title'] ?? 'Sans titre') ?></h3>
                    <p class="capsule-date">Ouverte depuis le <?= date('d/m/Y', strtotime($c['unlock_at'])) ?></p>
                </div>
                <div class="capsule-badge capsule-badge-open">Ouverte</div>
            </div>

            <!-- Contenu selon le type -->
            <?php if ($c['type'] === 'photo' && $c['file_path']): ?>
                <div class="capsule-photos">
                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($c['file_path']) ?>" alt="<?= htmlspecialchars($c['title']) ?>">
                </div>

            <?php elseif ($c['type'] === 'note' && $c['content']): ?>
                <div class="capsule-note-content">
                    <?= nl2br(htmlspecialchars($c['content'])) ?>
                </div>

            <?php elseif ($c['type'] === 'video' && $c['file_path']): ?>
                <div class="capsule-media">
                    <video controls>
                        <source src="<?= BASE_URL ?>/<?= htmlspecialchars($c['file_path']) ?>">
                    </video>
                </div>

            <?php elseif ($c['type'] === 'audio' && $c['file_path']): ?>
                <div class="capsule-media">
                    <audio controls>
                        <source src="<?= BASE_URL ?>/<?= htmlspecialchars($c['file_path']) ?>">
                    </audio>
                </div>
            <?php endif; ?>

        <?php endforeach; ?>
    <?php endif; ?>

</div>

<?php include './templates/navbar.php'; ?>

<script src="<?= BASE_URL ?>/vue/assets/js/app.js?v=<?= time() ?>"></script>
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>