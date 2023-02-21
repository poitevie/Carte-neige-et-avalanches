<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: X-Requested-With');

$files = scandir('hgt/massifs/');
foreach ($files as $file) {
    // VARIABLES GLOBALES

    $pas = 20;
    $hgt_value_size = 2;
    $hgt_line_records = 3600;
    $hgt_step = 1 / $hgt_line_records;
    $hgt_line_size = $hgt_value_size * ($hgt_line_records + 1);
    $filespath = "hgt/massifs/";
    $filenumber = explode(".", $file)[0];
    if ($filenumber != "") {

        // Si le fichier binaire du massif existe
        if (file_exists($filespath . $filenumber . '.hgt')) {
            $fileext = '.hgt';
            $hgt_line_records = 3600;
        } else
            die("Erreur : " . $filenumber . $fileext . " n'existe pas");

        if (!$fp = fopen($filespath . $filenumber . $fileext, "rb"))
            die("Erreur : N'a pas pu ouvrir le fichier d'altitude " . $filenumber . $fileext);
        else {
            if (!file_exists('images')) {
                mkdir('images', 0777, true);
            }
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
                $loc1 = $risque["LOC1"][0];
                $altitude = (int) $risque["ALTITUDE"];
                $risque2 = (int) $risque["RISQUE2"];
                $evolurisque2 = (int) $risque["EVOLURISQUE2"];
                $loc2 = $risque["LOC2"][0];
                $risquemaxi = (int) $risque["RISQUEMAXI"];

                $image = imagecreatetruecolor($width, $height);
                $trans = imagecolorallocatealpha($image, 0, 0, 0, 127);
                $green = imagecolorallocatealpha($image, 44, 176, 81, 0);
                $yellow = imagecolorallocatealpha($image, 254, 240, 53, 0);
                $orange = imagecolorallocatealpha($image, 253, 127, 54, 0);
                $red = imagecolorallocatealpha($image, 236, 11, 24, 0);
                $redhigh = imagecolorallocatealpha($image, 131, 7, 12, 0);
                imagesavealpha($image, true);
                imagefill($image, 0, 0, $trans);
                //génération de la tuile du massif
                for ($j = 0; $j < $height; $j += 1) {
                    for ($i = 0; $i < $width; $i += 1) {
                        fseek($fp, 20 + ($i) * $hgt_value_size + ($j) * $width * $hgt_value_size);
                        $val = fread($fp, 2);
                        $alt = @unpack('n', $val)[1];
                        //génération du code risque en fonction de l'altitude et des données météofrance

                        $risquecolor = 0;

                        if ($alt > 0) {
                            if ($risque1 == -1) {
                                $risquecolor = 0;
                            } else if ($loc1 == "<" && $alt < $altitude) {
                                $risquecolor = $evolurisque1 != 0 ? $risque1 . $evolurisque1 : $risque1;
                            } else if ($loc1 == ">" && $alt > $altitude) {
                                $risquecolor = $evolurisque1 != 0 ? $risque1 . $evolurisque1 : $risque1;
                            } else if ($loc2 == "<" && $alt <= $altitude) {
                                $risquecolor = $evolurisque2 != 0 ? $risque2 . $evolurisque2 : $risque2;
                            } else if ($loc2 == ">" && $alt >= $altitude) {
                                $risquecolor = $evolurisque2 != 0 ? $risque2 . $evolurisque2 : $risque2;
                            } else if ($loc1 == "W" && false) {
                                $risquecolor = $evolurisque1 != 0 ? $risque1 . $evolurisque1 : $risque1;
                            } else if ($loc1 == "N" && false) {
                                $risquecolor = $evolurisque1 != 0 ? $risque1 . $evolurisque1 : $risque1;
                            } else if ($loc1 == "E" && false) {
                                $risquecolor = $evolurisque1 != 0 ? $risque1 . $evolurisque1 : $risque1;
                            } else if ($loc1 == "S" && false) {
                                $risquecolor = $evolurisque1 != 0 ? $risque1 . $evolurisque1 : $risque1;
                            } else if ($loc2 == "W" && false) {
                                $risquecolor = $evolurisque2 != 0 ? $risque2 . $evolurisque2 : $risque2;
                            } else if ($loc2 == "N" && false) {
                                $risquecolor = $evolurisque2 != 0 ? $risque2 . $evolurisque2 : $risque2;
                            } else if ($loc2 == "E" && false) {
                                $risquecolor = $evolurisque2 != 0 ? $risque2 . $evolurisque2 : $risque2;
                            } else if ($loc2 == "S" && false) {
                                $risquecolor = $evolurisque2 != 0 ? $risque2 . $evolurisque2 : $risque2;
                            } else {
                                $risquecolor = $evolurisque1 != 0 ? $risque1 . $evolurisque1 : $risque1;
                            }
                        } else {
                            $risquecolor = 0;
                        }

                        if($risquecolor > 10) {
                            $r1 = floor($risquecolor / 10);
                            $r2 = $risquecolor % 10;
                            $imod = $i % $pas;
                            $jmod = $j % $pas;
                            if($imod < $pas / 2) {
                                $risquecolor = $r1;
                            }
                            else {
                                $risquecolor = $r2;
                            }
                        }

                        switch ($risquecolor) {
                            case 0:
                                imagesetpixel($image, $i, $j, $trans);
                                break;

                            case 1:
                                imagesetpixel($image, $i, $j, $green);
                                break;

                            case 2:
                                imagesetpixel($image, $i, $j, $yellow);
                                break;

                            case 3:
                                imagesetpixel($image, $i, $j, $orange);
                                break;

                            case 4:
                                imagesetpixel($image, $i, $j, $red);
                                break;

                            case 5:
                                imagesetpixel($image, $i, $j, $redhigh);
                                break;
                            default:   
                                imagesetpixel($image, $i, $j, $redhigh);
                                break;
                        }
                    }
                }
                imagepng($image, "./images/" . $filenumber . ".png");
                imagedestroy($image);
            } else {
                die("Erreur : Il y a une erreur lors du chargement des données de météofrance");
            }
        }
    }
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