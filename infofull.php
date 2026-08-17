<?php

require_once('db.inc.php');


$sql=<<<EOS
select typename,typeid 
from invTypes
EOS;

$binds=array();

$stmt = $dbh->prepare($sql);
$stmt->execute();

$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($result as $row) {
print "<a href='https://www.fuzzwork.co.uk/info/?typeid=".$row["typeid"]."'>".$row["typename"]."</a>";



}
