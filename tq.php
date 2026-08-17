<?php

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, "https://api.eveonline.com/server/ServerStatus.xml.aspx ");

curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$content = curl_exec($curl);
$xml = new simpleXMLElement($content);


print "server Open: ".$xml->result->serverOpen." \nPlayers Connected: ".$xml->result->onlinePlayers;
