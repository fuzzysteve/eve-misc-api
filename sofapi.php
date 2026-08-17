<?php
    //error_reporting(E_ALL);
    //ini_set("display_errors", 1);
     include("db.inc.php");
     // Add proper JSON MIME type in header
     header("Content-type: application/json");
     // Add CORS header in header so API can be used with web apps on other servers
     header("Access-Control-Allow-Origin: *");
     // Create out database connection
     // Get out endpoint
     $endpoint = $_GET['endpoint'];
     switch ($endpoint) {
         case "BASICDATA":
            $id = $_GET["typeid"];
            $SQL=<<<EOS
                SELECT
                   invTypes.typeID,
                   invTypes.typeName,
                   invTypes.iconID,
                   invTypes.groupID,
                   invTypes.raceID,
                   chrRaces.raceName,
                   invGroups.groupName,
                   invGroups.categoryID,
                   invCategories.categoryName,
                   substring(invTypes.description,1,5000) as description,
                   eveGraphics.graphicID,
                   eveGraphics.graphicFile,
                   eveGraphics.sofHullName,
                   eveGraphics.sofRaceName,
                   eveGraphics.sofFactionName
                FROM invTypes
                JOIN chrRaces on chrRaces.raceid=invTypes.raceID
                JOIN invGroups on invGroups.groupid=invTypes.groupid
                JOIN invCategories on invGroups.categoryid=invCategories.categoryid
                JOIN eveGraphics on invTypes.graphicID=eveGraphics.graphicid
                WHERE invTypes.typeid=:typeid
EOS;
            break;
         default:
             die("Unrecognised Endpoint");
    }

    $stmt = $dbh->prepare($SQL);
    $stmt->execute(array(":typeid"=>$id));
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print (json_encode($result));
