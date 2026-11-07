<?php
// FoodBridge — notification helpers (see the notifications table).

function notify_user(PDO $pdo, int $userId, string $type, string $message, ?string $link = null): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $type, $message, $link]);
}

function unread_notification_count(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function latest_notifications(PDO $pdo, int $userId, int $limit = 5): array
{
    $stmt = $pdo->prepare(
        'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?'
    );
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
