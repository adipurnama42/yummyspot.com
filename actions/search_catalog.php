<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/helpers.php';

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) { echo json_encode([]); exit; }

$db   = getDB();
$stmt = $db->prepare("
    SELECT c.id, c.name, c.slug, c.city, c.thumbnail,
           cat.name AS cat_name, cat.icon AS cat_icon
    FROM catalogs c
    JOIN categories cat ON c.category_id = cat.id
    WHERE c.verification_status = 'approved'
      AND (c.name LIKE ? OR c.city LIKE ?)
    ORDER BY c.total_likes DESC, c.avg_rating DESC
    LIMIT 6
");
$stmt->execute(["%$q%", "%$q%"]);
$results = $stmt->fetchAll();

echo json_encode($results);
