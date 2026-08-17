<?php

require_once('db.inc.php');

if (array_key_exists('text', $_POST)) {
    $text=$_POST['text'];
    $sql='select typename,typeid from invTypes where typeid=?';
} else {
    die("no text");
    exit;
}


$stmt = $dbh->prepare($sql);
$stmt->execute(array($text));
$x=0;
while ($row = $stmt->fetchObject()) {
    $url="https://market.fuzzwork.co.uk/aggregates/?region=10000002&types=";
    $itemname=$row->typename;
    $itemid=$row->typeid;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url.$itemid);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $pricexml=curl_exec($ch);
    curl_close($ch);
    $json=json_decode($pricexml);
    $item=$json->$itemid;
    $sellprice= (float) $item->sell->percentile;
    $buyprice= (float) $item->buy->percentile;
    echo "Type id: ".$itemid." Type name: ".$itemname." Price Sell: ".number_format($sellprice, 2)." Price Buy: ".number_format($buyprice, 2)."\n";
}
