<?php
final class Audit {
    public static function log(PDO $pdo, string $entityType, ?int $entityId, string $action, ?array $before = null, ?array $after = null): void {
        $stmt = $pdo->prepare('INSERT INTO audit_log (entity_type, entity_id, action, before_json, after_json, actor, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $entityType,
            $entityId,
            $action,
            $before ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
            $after ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
            $_SESSION['actor'] ?? 'local',
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}
