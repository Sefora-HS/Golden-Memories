<?php
require_once '../modele/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$notifId = (int)($data['id'] ?? 0);
$action = $data['action'] ?? '';
$userId = $_SESSION['user']['id'];

if (!$notifId || !$action) {
    echo json_encode(['success' => false]);
    exit;
}

if ($action === 'mark_as_read') {
    $stmt = $bdd->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid");
    $stmt->execute([':id' => $notifId, ':uid' => $userId]);
    echo json_encode(['success' => true]);
}

if ($action === 'delete') {
    $stmt = $bdd->prepare("DELETE FROM notifications WHERE id = :id AND user_id = :uid");
    $stmt->execute([':id' => $notifId, ':uid' => $userId]);
    echo json_encode(['success' => true]);
}