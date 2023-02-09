<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: X-Requested-With');

if (isset($_GET["massif"])) {
    // VARIABLES GLOBALES
    $hgt_value_size = 2;
    $hgt_line_records = 3600;
    $hgt_step = 1 / $hgt_line_records;
    $hgt_line_size = $hgt_value_size * ($hgt_line_records + 1);
    $filespath = "hgt/massifs/";
    $filenumber = htmlspecialchars($_GET["massif"]);

    if (file_exists($filespath . $filenumber . '.hgt')) { // SRTM3 V2
        $fileext = '.hgt';
        $hgt_line_records = 3600;
    } else
        return;

    if (!$fp = fopen($filespath . $filenumber . $fileext, "rb"))
        die("Erreur : N'a pas pu ouvrir le fichier d'altitude");
    else {
        fseek($fp, 0);
        $val = fread($fp, 2);
        $width = @unpack('n', $val)[1];
        fseek($fp, 2);
        $val = fread($fp, 2);
        $height = @unpack('n', $val)[1];
        fseek($fp, 4);
        $val = fread($fp, 4);
        $bglat = @unpack('f', $val)[1];
        fseek($fp, 8);
        $val = fread($fp, 4);
        $bglon = @unpack('f', $val)[1];
        fseek($fp, 12);
        $val = fread($fp, 4);
        $hdlat = @unpack('f', $val)[1];
        fseek($fp, 16);
        $val = fread($fp, 4);
        $hdlon = @unpack('f', $val)[1];


        $xml = (array) simplexml_load_string(file_get_contents("http://api.meteofrance.com/files/mountain/bulletins/BRA".$filenumber.".xml"));

        if (isset($xml["CARTOUCHERISQUE"])) {
            if (isset($xml["CARTOUCHERISQUE"]->{"RISQUE"})) {
                $risque = $xml["CARTOUCHERISQUE"]->{"RISQUE"};
                $risque1 = $risque["RISQUE1"]; // -1 quand risque non chiffré
                $evolurisque1 = $risque["EVOLURISQUE1"];
                $loc1 = $risque["LOC1"];
                $altitude = $risque["ALTITUDE"];
                $risque2 = $risque["RISQUE2"];
                $evolurisque2 = $risque["EVOLURISQUE2"];
                $loc2 = $risque["LOC2"];
                $risquemaxi = $risque["RISQUEMAXI"];

                if (isset($loc1))
                    $loc1 = substr($loc1, 0, 1);
                if (isset($loc1))
                    $loc2 = substr($loc2, 0, 1);

                $array = array("width" => $width, "height" => $height, "bg" => [$bglat, $bglon], "hd" => [$hdlat, $hdlon], "tile" => null);
                $tile = array();
                for ($j = 0; $j < $height; $j++) {
                    $line = array();
                    for ($i = 0; $i < $width; $i++) {
                        fseek($fp, 20 + ($i) * $hgt_value_size + ($j) * $width * $hgt_value_size);
                        $val = fread($fp, 2);
                        $alt = @unpack('n', $val)[1];
                        if ($alt > 0) {
                            if (isset($risque1) && intval($risque1) == -1) {
                                array_push($line, 0);
                            } else if (isset($loc1) && $loc1 == "<" && $alt < $altitude) {
                                $val = isset($evolurisque1) && $evolurisque1 != "" ? intval(intval($risque1) . intval($evolurisque1)) : intval($risque1);
                                array_push($line, $val);
                            } else if (isset($loc1) && $loc1 == ">" && $alt > $altitude) {
                                $val = isset($evolurisque1) && $evolurisque1 != "" ? intval(intval($risque1) . intval($evolurisque1)) : intval($risque1);
                                array_push($line, $val);
                            } else if (isset($loc2) && $loc2 == "<" && $alt <= $altitude) {
                                $val = isset($evolurisque2) && $evolurisque2 != "" ? intval(intval($risque2) . intval($evolurisque2)) : intval($risque2);
                                array_push($line, $val);
                            } else if (isset($loc2) && $loc2 == ">" && $alt >= $altitude) {
                                $val = isset($evolurisque2) && $evolurisque2 != "" ? intval(intval($risque2) . intval($evolurisque2)) : intval($risque2);
                                array_push($line, $val);
                            } else if (isset($loc1) && $loc1 == "W" && false) {
                                $val = isset($evolurisque1) && $evolurisque1 != "" ? intval(intval($risque1) . intval($evolurisque1)) : intval($risque1);
                                array_push($line, $val);
                            } else if (isset($loc1) && $loc1 == "N" && false) {
                                $val = isset($evolurisque1) && $evolurisque1 != "" ? intval(intval($risque1) . intval($evolurisque1)) : intval($risque1);
                                array_push($line, $val);
                            } else if (isset($loc1) && $loc1 == "E" && false) {
                                $val = isset($evolurisque1) && $evolurisque1 != "" ? intval(intval($risque1) . intval($evolurisque1)) : intval($risque1);
                                array_push($line, $val);
                            } else if (isset($loc1) && $loc1 == "S" && false) {
                                $val = isset($evolurisque1) && $evolurisque1 != "" ? intval(intval($risque1) . intval($evolurisque1)) : intval($risque1);
                                array_push($line, $val);
                            } else if (isset($loc2) && $loc2 == "W" && false) {
                                $val = isset($evolurisque2) && $evolurisque2 != "" ? intval(intval($risque2) . intval($evolurisque2)) : intval($risque2);
                                array_push($line, $val);
                            } else if (isset($loc2) && $loc2 == "N" && false) {
                                $val = isset($evolurisque2) && $evolurisque2 != "" ? intval(intval($risque2) . intval($evolurisque2)) : intval($risque2);
                                array_push($line, $val);
                            } else if (isset($loc2) && $loc2 == "E" && false) {
                                $val = isset($evolurisque2) && $evolurisque2 != "" ? intval(intval($risque2) . intval($evolurisque2)) : intval($risque2);
                                array_push($line, $val);
                            } else if (isset($loc2) && $loc2 == "S" && false) {
                                $val = isset($evolurisque2) && $evolurisque2 != "" ? intval(intval($risque2) . intval($evolurisque2)) : intval($risque2);
                                array_push($line, $val);
                            } else {
                                array_push($line, intval($risque1));
                            }
                        }
                        else {
                            array_push($line, 0);
                        }
                    }
                    array_push($tile, $line);
                }
                $array["tile"] = $tile;
                echo json_encode($array);
            }
        }
    }
} else {
    die("Merci d'indiquer le paramètre 'massif'");
}


// Récupérer le numéro du fichier à partir d'un point (latitude,longitude)
function getfilenumber($latitude, $longitude)
{
    $lat = abs(floor($latitude));
    $lon = abs(floor($longitude));

    $filenumber = "";
    if ($latitude >= 0)
        $filenumber .= "N";
    else
        $filenumber .= "S";
    if (strlen($lat) == 1)
        $filenumber .= "0";
    $filenumber .= $lat;

    if ($longitude >= 0)
        $filenumber .= "E";
    else
        $filenumber .= "W";
    if (strlen($lon) == 1)
        $filenumber .= "00";
    else if (strlen($lon) == 2)
        $filenumber .= "0";
    $filenumber .= $lon;

    return $filenumber;
}
?>