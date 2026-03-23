<?php
include_once '../../modele/config.php';

// Vérification session — utilise $_SESSION['user'] comme partout ailleurs
if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/vue/pages/login.php');
    exit;
}

// Récupère l'id depuis la bonne variable de session
$user_id = $_SESSION['user']['id'];

// ── Action : ouvrir une capsule ──
if (isset($_GET['open']) && is_numeric($_GET['open'])) {
    $capsule_id = (int) $_GET['open'];

    $stmt = $bdd->prepare("
        UPDATE time_capsules tc
        JOIN memories m ON m.id = tc.memory_id
        SET tc.is_open = 1
        WHERE tc.id = :capsule_id
          AND m.user_id = :user_id
          AND tc.unlock_at <= NOW()
          AND tc.is_open = 0
    ");
    $stmt->execute([':capsule_id' => $capsule_id, ':user_id' => $user_id]);
    header('Location: ' . BASE_URL . '/vue/pages/capsules.php');
    exit;
}

// ── Récupérer toutes les capsules ──
$stmt = $bdd->prepare("
    SELECT 
        tc.id        AS capsule_id,
        tc.capsule_title,
        tc.unlock_at,
        tc.is_open,
        tc.created_at AS capsule_created,
        m.id         AS memory_id,
        m.title      AS memory_title,
        m.type,
        m.file_path,
        m.content,
        TIMESTAMPDIFF(SECOND, NOW(), tc.unlock_at) AS seconds_left
    FROM time_capsules tc
    JOIN memories m ON m.id = tc.memory_id
    WHERE m.user_id = :user_id
    ORDER BY tc.unlock_at ASC
");
$stmt->execute([':user_id' => $user_id]);
$capsules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Séparer en 4 groupes ──
$capsules_ready  = [];
$capsules_soon   = [];
$capsules_locked = [];
$capsules_open   = [];

$seuil_bientot = 7 * 24 * 3600;

foreach ($capsules as $c) {
    if ($c['is_open']) {
        $capsules_open[] = $c;
    } elseif ($c['seconds_left'] <= 0) {
        $capsules_ready[] = $c;
    } elseif ($c['seconds_left'] <= $seuil_bientot) {
        $capsules_soon[] = $c;
    } else {
        $capsules_locked[] = $c;
    }
}

function formatCountdown(int $seconds): string {
    if ($seconds <= 0) return 'Maintenant';
    $days    = floor($seconds / 86400);
    $hours   = floor(($seconds % 86400) / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    if ($days > 0)  return "Dans $days jour" . ($days > 1 ? 's' : '');
    if ($hours > 0) return "Dans $hours heure" . ($hours > 1 ? 's' : '');
    return "Dans $minutes minute" . ($minutes > 1 ? 's' : '');
}