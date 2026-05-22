<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/helpers.php';
requireLogin(); verifyCsrf();
$cid=(int)($_POST['catalog_id']??0); if(!$cid){echo json_encode(['ok'=>false]);exit;}
$db=getDB(); $uid=currentUser()['id'];
$ex=$db->prepare("SELECT id FROM wishlists WHERE user_id=? AND catalog_id=?"); $ex->execute([$uid,$cid]);
if($ex->fetchColumn()){$db->prepare("DELETE FROM wishlists WHERE user_id=? AND catalog_id=?")->execute([$uid,$cid]);$saved=false;}
else{$db->prepare("INSERT IGNORE INTO wishlists (user_id,catalog_id) VALUES (?,?)")->execute([$uid,$cid]);$saved=true;}
echo json_encode(['ok'=>true,'saved'=>$saved]);
