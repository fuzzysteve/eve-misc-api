<?php

require_once('db.inc.php');

$sql="select tosolarsystemid,solarsystemname,distance 
    from evesupport.jumpRange join eve.mapSolarSystems on tosolarsystemid=solarsystemid 
    where fromsystemid=:fromid
    and distance<=:distance";


if (isset($_GET['fromsolarsystemid']) and is_numeric($_GET['fromsolarsystemid'])) {
    $solarsystemid=$_GET['fromsolarsystemid'];
} else {
    header("HTTP/1.1 500 Internal Server Error");
    exit;
}


if (isset($_GET['distance']) and is_numeric($_GET['distance'])) {
    $distance=$_GET['distance'];
} else {
    $distance=31;
}

if (isset($_GET['tosolarsystemid']) and is_numeric($_GET['tosolarsystemid'])) {
    $tosolarsystemid=$_GET['tosolarsystemid'];
    $sql="select tosolarsystemid,solarsystemname,distance
    from evesupport.jumpRange join eve.mapSolarSystems on tosolarsystemid=solarsystemid
    where fromsystemid=:fromid
    and tosolarsystemid=:tosolarsystemid";
}



$stmt = $dbh->prepare($sql);
if (isset($tosolarsystemid)) {
    $stmt->execute(array(":fromid"=>$solarsystemid,":tosolarsystemid"=>$tosolarsystemid));
} else {
    $stmt->execute(array(":fromid"=>$solarsystemid,":distance"=>$distance));
}

$systems=[];
while ($row = $stmt->fetchObject()) {
    $name=$row->solarsystemname;
    $id=(int)$row->tosolarsystemid;
    $distance=floatval($row->distance);
    $systems[]=array("systemName"=>$name,"systemID"=>$id,"distance"=>$distance);
}
echo json_encode($systems);
