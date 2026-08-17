<?php

require_once('db.inc.php');

if (isset($_REQUEST['solarsystem'])) {
    $systemname=$_REQUEST['solarsystem'];
    $sql='SELECT solarSystemID,solarSystemName FROM mapSolarSystems WHERE LOWER(solarSystemName)=lower(?)';
} elseif (isset($_REQUEST['solarsystemid'])) {
    $systemname=$_REQUEST['solarsystemid'];
    $sql='SELECT solarSystemID,solarSystemName FROM mapSolarSystems WHERE solarsystemid=?';
} else {
    header("HTTP/1.1 500 Internal Server Error");
    exit;
}



$stmt = $dbh->prepare($sql);
$stmt->execute(array($systemname));

if ($row = $stmt->fetchObject()) {
    $solarsystemid=(int)$row->solarSystemID;
    $solarsystemname=$row->solarSystemName;
} else {
    header("HTTP/1.1 500 Internal Server Error");
    exit;
}

$sql="SELECT activityID,costIndex,date(archivetime) archivetime FROM evesupport.costIndexArchive WHERE solarSystemID=?";

$stmt = $dbh->prepare($sql);
$stmt->execute(array($solarsystemid));

$activity=array();
while ($row = $stmt->fetchObject()) {
    
    if (!isset($activity[(int)$row->activityID])) {
        $activity[(int)$row->activityID]=array();
    }
    $activity[(int)$row->activityID][$row->archivetime]=$row->costIndex;
}
header('Content-Type: application/json');

$return=array('solarSystemName'=>$solarsystemname,'solarSystemID'=>$solarsystemid,'costIndexes'=>$activity);
echo json_encode($return, JSON_NUMERIC_CHECK);
