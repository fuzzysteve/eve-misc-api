<?php

            $ch = curl_init("https://api.eveonline.com/map/Kills.xml.aspx");

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);

            // $response contains the XML response string from the API call
            $response = curl_exec($ch);

            // If curl_exec() fails/throws an error, the function will return false
            if($response === false)
            {
                    // Could add some 404 headers here
                    echo 'Curl error: ' . curl_error($ch);
            }
            else
            {
                    $kills = new SimpleXMLElement($response);
                    $expires=strtotime($kills->cachedUntil)+3600;
                    header("Expires: ".gmdate('D, d M Y H:i:s',$expires).' GMT');
                    foreach ($kills->result->rowset->row as $row)
                    {
                        echo $row->attributes()->solarSystemID.",". $row->attributes()->shipKills.',',$row->attributes()->factionKills.','.$row->attributes()->podKills."\n";
                    }


            }

            // close curl resource to free up system resources
            curl_close($ch);
?>
