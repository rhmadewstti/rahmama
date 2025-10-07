<?php
require 'includes/db.php';
require 'includes/auth.php';

header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename=backup_pos_atk_'.date('Ymd_His').'.sql');

$tables = ['users','products','customers','orders','order_items','settings'];
echo "-- POS ATK backup generated ".date('c')."\n\n";
foreach($tables as $t){
  $res = mysqli_query($conn, "SHOW CREATE TABLE `$t`");
  $row = mysqli_fetch_assoc($res);
  echo $row['Create Table'].";\n\n";
  $data = mysqli_query($conn, "SELECT * FROM `$t`");
  while($r = mysqli_fetch_assoc($data)){
    $cols = array_keys($r);
    $vals = array_map(function($v) use ($conn){ return "'".mysqli_real_escape_string($conn,$v)."'"; }, array_values($r));
    echo "INSERT INTO `$t` (`".implode('`,`',$cols)."`) VALUES (".implode(',',$vals).");\n";
  }
  echo "\n";
}
exit;
