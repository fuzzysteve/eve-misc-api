<?php
require_once('db.inc.php');

if (isset($_GET['parent'])) {
    $sql="select marketgroupid,marketgroupname from invMarketGroups where parentgroupid = :parent";
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array(":parent"=>$_GET['parent']));
} else {
    $sql="select marketgroupid,marketgroupname from invMarketGroups where parentgroupid is null";
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
}


$json=array("groups"=>array(),"types"=>array());
$result = $stmt->fetchAll(PDO::FETCH_OBJ);
foreach ($result as $row) {
    array_push($json["groups"], array("groupid"=>$row->marketgroupid,"name"=>$row->marketgroupname));
}



if (isset($_GET['parent'])) {
    $sql="select typeid,typename from invTypes where marketGroupId=:marketgroup";
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array(":marketgroup"=>$_GET['parent']));
    $result = $stmt->fetchAll(PDO::FETCH_OBJ);
    foreach ($result as $row) {
        array_push($json["types"], array("typeid"=>$row->typeid,"name"=>$row->typename));
    }
}
print json_encode($json);
