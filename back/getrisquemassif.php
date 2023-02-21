<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: X-Requested-With');


//Le paramètre massif correspond à l'id du massif ex: 07 pour la Chartreuse
if (isset($_GET["massif"])) {
    // VARIABLES GLOBALES
    $hgt_value_size = 2;
    $hgt_line_records = 3600;
    $hgt_step = 1 / $hgt_line_records;
    $hgt_line_size = $hgt_value_size * ($hgt_line_records + 1);
    $filespath = "hgt/massifs/";
    $filenumber = htmlspecialchars($_GET["massif"]);

    //Cache
    if(file_exists("cache/getrisquemassif/".$filenumber.".json")) {
        readfile("cache/getrisquemassif/".$filenumber.".json");
    } else {
        // Si le fichier binaire du massif existe
        if (file_exists($filespath . $filenumber . '.hgt')) {
            $fileext = '.hgt';
            $hgt_line_records = 3600;
        } else
            die("Erreur : ".$filenumber.$fileext." n'existe pas");

        if (!$fp = fopen($filespath . $filenumber . $fileext, "rb"))
            die("Erreur : N'a pas pu ouvrir le fichier d'altitude ". $filenumber . $fileext);
        else {

            //Variables globales stockées dans le fichier
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


            //Fichier risque de météofrance en fonction du massif.
            $xml = (array) simplexml_load_string(file_get_contents("http://api.meteofrance.com/files/mountain/bulletins/BRA" . $filenumber . ".xml"));

            if (isset($xml["CARTOUCHERISQUE"]->{"RISQUE"})) {
                    //variables risque
                    $risque = $xml["CARTOUCHERISQUE"]->{"RISQUE"};
                    $risque1 = (int) $risque["RISQUE1"]; // =-1 quand risque non chiffré
                    $evolurisque1 = (int) $risque["EVOLURISQUE1"];
                    $loc1 = substr($risque["LOC1"], 0, 1);
                    $altitude = (int) $risque["ALTITUDE"];
                    $risque2 = (int) $risque["RISQUE2"];
                    $evolurisque2 = (int) $risque["EVOLURISQUE2"];
                    $loc2 = substr($risque["LOC2"], 0, 1);
                    $risquemaxi = (int) $risque["RISQUEMAXI"];

                    $array = array("width" => $width, "height" => $height, "bg" => [$bglat, $bglon], "hd" => [$hdlat, $hdlon], "tile" => null);
                    $tile = array();
                    //génération de la tuile du massif
                    for ($j = 0; $j < $height; $j+=1) {
                        $line = array();
                        for ($i = 0; $i < $width; $i+=1) {
                            fseek($fp, 20 + ($i) * $hgt_value_size + ($j) * $width * $hgt_value_size);
                            $val = fread($fp, 2);
                            $alt = @unpack('n', $val)[1];
                            //génération du code risque en fonction de l'altitude et des données météofrance
                            if ($alt > 0) {
                                if ($risque1 == -1) {
                                    array_push($line, 0);
                                } else if ($loc1 == "<" && $alt < $altitude) {
                                    $val = $evolurisque1 != 0 ? intval($risque1 . $evolurisque1) : $risque1;
                                    array_push($line, $val);
                                } else if ($loc1 == ">" && $alt > $altitude) {
                                    $val = $evolurisque1 != 0 ? intval($risque1 . $evolurisque1) : $risque1;
                                    array_push($line, $val);
                                } else if ($loc2 == "<" && $alt <= $altitude) {
                                    $val = $evolurisque2 != 0 ? intval($risque2 . $evolurisque2) : $risque2;
                                    array_push($line, $val);
                                } else if ($loc2 == ">" && $alt >= $altitude) {
                                    $val = $evolurisque2 != 0 ? intval($risque2 . $evolurisque2) : $risque2;
                                    array_push($line, $val);
                                } else if ($loc1 == "W" && false) {
                                    $val = $evolurisque1 != 0 ? intval($risque1 . $evolurisque1) : $risque1;
                                    array_push($line, $val);
                                } else if ($loc1 == "N" && false) {
                                    $val = $evolurisque1 != 0 ? intval($risque1 . $evolurisque1) : $risque1;
                                    array_push($line, $val);
                                } else if ($loc1 == "E" && false) {
                                    $val = $evolurisque1 != 0 ? intval($risque1 . $evolurisque1) : $risque1;
                                    array_push($line, $val);
                                } else if ($loc1 == "S" && false) {
                                    $val = $evolurisque1 != 0 ? intval($risque1 . $evolurisque1) : $risque1;
                                    array_push($line, $val);
                                } else if ($loc2 == "W" && false) {
                                    $val = $evolurisque2 != 0 ? intval($risque2 . $evolurisque2) : $risque2;
                                    array_push($line, $val);
                                } else if ($loc2 == "N" && false) {
                                    $val = $evolurisque2 != 0 ? intval($risque2 . $evolurisque2) : $risque2;
                                    array_push($line, $val);
                                } else if ($loc2 == "E" && false) {
                                    $val = $evolurisque2 != 0 ? intval($risque2 . $evolurisque2) : $risque2;
                                    array_push($line, $val);
                                } else if ($loc2 == "S" && false) {
                                    $val = $evolurisque2 != 0 ? intval($risque2 . $evolurisque2) : $risque2;
                                    array_push($line, $val);
                                } else {
                                    array_push($line, intval($risque1));
                                }
                            } else {
                                array_push($line, 0);
                            }
                        }
                        array_push($tile, $line);
                    }
                    $array["tile"] = $tile;

                    //Affichage de la réponse + stockage dans le cache

                    ob_start();
                    echo json_encode($array);
                    $page = ob_get_contents();
                    ob_end_clean();
                    file_put_contents("cache/getrisquemassif/" . $filenumber . ".json", $page);
                    echo $page;
            }
            else {
                die("Erreur : Il y a une erreur lors du chargement des données de météofrance");
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