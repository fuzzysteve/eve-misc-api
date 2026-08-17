<?php

require_once('db.inc.php');
header('Access-Control-Allow-Origin: *');
if (array_key_exists('text', $_POST)) {
    $text=$_POST['text'];
    $sql='select typename,typeid from invTypes where (lower(typename) like lower(?) or typeid=?) and typeid!=0 order by length(typename)';
} else {
    die("no text");
    exit;
}


$stmt = $dbh->prepare($sql);
$stmt->execute(array('%'.$text.'%',$text));
$x=0;
while ($row = $stmt->fetchObject()) {
    $itemname=$row->typename;
    $itemid=$row->typeid;
    echo "Type id: ".$itemid." Type name: ".$itemname."\n";
    $x++;
    if ($x>5) {
        break;
    }
}
