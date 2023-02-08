<?php

// VARIABLES GLOBALES
$hgt_value_size = 2;
$hgt_line_records = 3600;
$hgt_step = 1 / $hgt_line_records;
$hgt_line_size = $hgt_value_size * ($hgt_line_records + 1);
$img_size = 277;

// 
if (
    isset($_GET['y']) &&
    isset($_GET['x']) &&
    isset($_GET['n']) &&
    isset($_GET['e'])
) {
    $y = intval(htmlspecialchars($_GET['y'])); // Numéro de ligne dans la tuile
    $x = intval(htmlspecialchars($_GET['x'])); // Numéro de colonne dans la tuile
    $north = intval(htmlspecialchars($_GET['n'])); // Dégré nord
    $east = intval(htmlspecialchars($_GET['e'])); // Degré est

    if (is_int($y) && is_int($x) && $y >= 0 && $y <= 12 && $x >= 0 && $x <= 12) {
        $y = 12 - $y;
        $filespath = "hgt/";
        $filenumber = getfilenumber($north, $east);

        if (file_exists($filespath . $filenumber . '.hgt')) { // SRTM3 V2
            $fileext = '.hgt';
            $hgt_line_records = 3600;
        } else
            return;

        if (!$fp = fopen($filespath . $filenumber . $fileext, "rb"))
            die("Erreur : N'a pas pu ouvrir le fichier d'altitude");
        else {
            getmountainurl();
            $xml = (array) simplexml_load_string(file_get_contents("http://api.meteofrance.com/files/mountain/bulletins/BRA40.xml"));

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
                    $tile = array();
                    for ($j = 0; $j < $img_size; $j++) {
                        $line = array();
                        for ($i = 0; $i < $img_size; $i++) {
                            fseek($fp, ($i + $x * $img_size) * $hgt_value_size + ($j + $y * $img_size) * $hgt_line_size);
                            $val = fread($fp, 2);
                            $alt = @unpack('n', $val)[1];

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
                                array_push($line, 0);
                            }
                        }
                        array_push($tile, $line);
                    }
                    echo json_encode($tile);
                }
            }
        }
    } else
        die("y et x doivent être entre 0 et 12");
} else
    die("Indiquer les paramètres");

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
function getmountainurl()
{
    $json = file_get_contents('geojson/massifs.geojson');

    $json_data = json_decode($json);
    echo $json_data;
}
?>