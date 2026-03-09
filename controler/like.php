<?php
require_once __DIR__ . '/../modele/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Non connecté']);
    exit;
}

$userId   = $_SESSION['user']['id'];
$data     = json_decode(file_get_contents('php://input'), true);
$memoryId = (int)($data['memory_id'] ?? 0);

if (!$memoryId) {
    echo json_encode(['error' => 'ID invalide']);
    exit;
}

$check = $bdd->prepare("SELECT id FROM likes WHERE user_id = :uid AND memory_id = :mid");
$check->execute([':uid' => $userId, ':mid' => $memoryId]);

if ($check->fetch()) {
    $bdd->prepare("DELETE FROM likes WHERE user_id = :uid AND memory_id = :mid")
        ->execute([':uid' => $userId, ':mid' => $memoryId]);
} else {
    $bdd->prepare("INSERT INTO likes (user_id, memory_id) VALUES (:uid, :mid)")
        ->execute([':uid' => $userId, ':mid' => $memoryId]);
}

$count = $bdd->prepare("SELECT COUNT(*) FROM likes WHERE memory_id = :mid");
$count->execute([':mid' => $memoryId]);

echo json_encode(['likes_count' => (int)$count->fetchColumn()]);