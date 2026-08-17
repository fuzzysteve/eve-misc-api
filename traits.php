<?php
header('Access-Control-Allow-Origin: *');

require_once('db.inc.php');


$sql=<<<EOS
select coalesce(typename,"Role") typename,coalesce(bonus,"") bonus,bonusText,coalesce(displayName,'') displayName
from invTraits join eveUnits on invTraits.unitID=eveUnits.unitid 
left join invTypes on skillid=invTypes.typeid 
where invTraits.typeid=:typeid
EOS;

$binds=array();

if (array_key_exists('typeid', $_GET)) {
    $itemid=$_GET['typeid'];
    $binds[":typeid"]=$itemid;
}

$stmt = $dbh->prepare($sql);
$stmt->execute($binds);

$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json; charset=UTF-8');
print json_encode($result);
