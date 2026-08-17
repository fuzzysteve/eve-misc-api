<?php
header('Access-Control-Allow-Origin: *');
require_once('db.inc.php');

$format='json';

if (array_key_exists('format', $_GET)) {
    if ($_GET['format'] == 'xml') {
        $format='xml';
    }
}

if ($format == 'xml') {
    header('Content-Type:text/xml');
    echo "<?xml version='1.0' encoding='UTF-8'?>
<eveapi version=\"2\">
<result>
<rowset name=\"typeids\" key=\"typeID\" columns=\"typeName,TypeID\">
";
}

$types=array();
if (array_key_exists('typename', $_GET)) {


    $ids=explode('|', $_GET['typename']);

    foreach ($ids as $bpid) {

        $sql='select typename,typeid from invTypes where lower(typename)=lower(?)';
        $stmt = $dbh->prepare($sql);
        $stmt->execute(array($bpid));

        if ($row = $stmt->fetchObject()) {
            $itemname=$row->typename;
            $itemid=$row->typeid;
        } else {
            $itemname="bad item";
            $itemid=0;
        }



        if (is_numeric($itemid)) {
            if ($format == 'xml') {
                    echo "<row typeName=\"".$itemname."\" typeID=\"".$itemid."\" />";
            }
            

            if ($format == 'json') {
                $types[]=array("typeID"=>$itemid,"typeName"=>$itemname);
            }

        }
    }
}



if ($format == 'xml') {
    echo "</rowset></result></eveapi>";
}
if ($format=='json') {
    echo json_encode($types);
}
