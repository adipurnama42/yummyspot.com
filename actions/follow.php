<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/helpers.php';
requireLogin(); verifyCsrf();
$tid=(int)($_POST['user_id']??0); $me=currentUser();
if(!$tid||$tid==$me['id']){echo json_encode(['ok'=>false]);exit;}
$db=getDB();
$ex=$db->prepare("SELECT id FROM follows WHERE follower_id=? AND following_id=?"); $ex->execute([$me['id'],$tid]);
if($ex->fetchColumn()){$db->prepare("DELETE FROM follows WHERE follower_id=? AND following_id=?")->execute([$me['id'],$tid]);$f=false;}
else{$db->prepare("INSERT IGNORE INTO follows (follower_id,following_id) VALUES (?,?)")->execute([$me['id'],$tid]);$f=true;}
$fc=$db->prepare("SELECT COUNT(*) FROM follows WHERE following_id=?"); $fc->execute([$tid]);
echo json_encode(['ok'=>true,'following'=>$f,'followers'=>fmtNum((int)$fc->fetchColumn())]);
