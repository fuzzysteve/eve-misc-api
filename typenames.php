<?php
header('Access-Control-Allow-Origin: *');
require_once('db.inc.php');

$types=array();

$sql='select typename,typeid from invTypes';
$stmt = $dbh->prepare($sql);
$stmt->execute();


while ($row = $stmt->fetchObject()) {
    $itemname=$row->typename;
    $itemid=$row->typeid;
    $types[$itemid]=utf8_encode($itemname);
}



print json_encode($types);
