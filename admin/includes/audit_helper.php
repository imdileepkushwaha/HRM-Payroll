<?php

function log_admin_action($conn, string $action, string $entity_type = '', string $entity_id = '', ?string $detail = null): void
{
    if (payroll_is_mssql()) {
        return;
    }
    $admin = $_SESSION['admin_username'] ?? 'system';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmt = $conn->prepare('INSERT INTO admin_audit_log (admin_username, action, entity_type, entity_id, detail, ip_address) VALUES (?,?,?,?,?,?)');
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('ssssss', $admin, $action, $entity_type, $entity_id, $detail, $ip);
    $stmt->execute();
}

function get_admin_audit_logs($conn, int $limit = 200, ?string $entity_type = null): array
{
    $sql = 'SELECT * FROM admin_audit_log WHERE 1=1';
    $types = '';
    $params = [];
    if ($entity_type !== null && $entity_type !== '') {
        $sql .= ' AND entity_type = ?';
        $types .= 's';
        $params[] = $entity_type;
    }
    $sql .= ' ORDER BY created_at DESC LIMIT ' . max(1, min(500, $limit));
    if ($types === '') {
        $res = $conn->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
