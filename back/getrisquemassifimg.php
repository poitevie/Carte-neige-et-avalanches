<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: X-Requested-With');

$files = scandir('./hgt/massifs/altitude/');
foreach ($files as $file) {
    // VARIABLES GLOBALES

    $pas = 10;
    $hgt_value_size = 2;
    $hgt_line_records = 3600;
    $fileext = '.hgt';
    $hgt_step = 1 / $hgt_line_records;
    $hgt_line_size = $hgt_value_size * ($hgt_line_records + 1);
    $filespath = "hgt/massifs/";
    $filenumber = explode(".", $file)[0];
    if ($filenumber != "") {

        // Si le fichier binaire du massif existe
        if (file_exists($filespath . "altitude/" . $filenumber . '.hgt')) {
            $hgt_line_records = 3600;
        } else
            die("Erreur : " . $filenumber . $fileext . " n'existe pas");

        if (!$fp = fopen($filespath . "altitude/" . $filenumber . $fileext, "rb"))
            die("Erreur : N'a pas pu ouvrir le fichier d'altitude " . $filenumber . $fileext);
        else if (!$fp2 = fopen($filespath . "orientation/" . $filenumber . $fileext, "rb"))
            die("Erreur : N'a pas pu ouvrir le fichier d'orientation " . $filenumber . $fileext);
        else if (!$fp3 = fopen($filespath . "pente/" . $filenumber . $fileext, "rb"))
            die("Erreur : N'a pas pu ouvrir le fichier de pente " . $filenumber . $fileext);
        else {
            if (!file_exists('images/risque')) {
                mkdir('images/risque', 0777, true);
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
                $loc1 = substr($risque["LOC1"], 0, 1);
                $altitude = (int) $risque["ALTITUDE"];
                $risque2 = (int) $risque["RISQUE2"];
                $evolurisque2 = (int) $risque["EVOLURISQUE2"];
                $loc2 = substr($risque["LOC2"], 0, 1);
                $risquemaxi = (int) $risque["RISQUEMAXI"];
                //pente
                $pentexml = $xml["CARTOUCHERISQUE"]->{"PENTE"};
                $penteNO = $pentexml["NW"] == "true";
                $penteN = $pentexml["N"] == "true";
                $penteNE = $pentexml["NE"] == "true";
                $penteE = $pentexml["E"] == "true";
                $penteO = $pentexml["W"] == "true";
                $penteSO = $pentexml["SW"] == "true";
                $penteS = $pentexml["S"] == "true";
                $penteSE = $pentexml["SE"] == "true";

                $image = imagecreatetruecolor($width, $height);
                $trans = imagecolorallocatealpha($image, 0, 0, 0, 127);
                imagesavealpha($image, true);
                imagefill($image, 0, 0, $trans);
                //génération de la tuile du massif
                for ($j = 0; $j < $height; $j += 1) {
                    for ($i = 0; $i < $width; $i += 1) {
                        fseek($fp, 20 + ($i) * $hgt_value_size + ($j) * $width * $hgt_value_size);
                        $val = fread($fp, 2);
                        $alt = @unpack('n', $val)[1];

                        fseek($fp2, 20 + ($i) * $hgt_value_size + ($j) * $width * $hgt_value_size);
                        $val = fread($fp2, 2);
                        $orientation = @unpack('n', $val)[1];

                        fseek($fp3, 20 + ($i) * $hgt_value_size + ($j) * $width * $hgt_value_size);
                        $val = fread($fp3, 2);
                        $pente = @unpack('n', $val)[1];

                        //génération du code risque en fonction de l'altitude et des données météofrance

                        $risquecolor = 0;

                        if ($alt > 0 && $pente >= 30) {
                            if ($risque1 == -1) {
                                $risquecolor = 0;
                            } else if ($loc1 == "<" && $alt < $altitude) {
                                $risquecolor = $evolurisque1 != 0 ? intval($risque1 . $evolurisque1) : $risque1;
                            } else if ($loc1 == ">" && $alt > $altitude) {
                                $risquecolor = $evolurisque1 != 0 ? intval($risque1 . $evolurisque1) : $risque1;
                            } else if ($loc2 == "<" && $alt <= $altitude) {
                                $risquecolor = $evolurisque2 != 0 ? intval($risque2 . $evolurisque2) : $risque2;
                            } else if ($loc2 == ">" && $alt >= $altitude) {
                                $risquecolor = $evolurisque2 != 0 ? intval($risque2 . $evolurisque2) : $risque2;
                            } else if ($loc1 == "W" && ($orientation == 1 || $orientation == 4 || $orientation == 7)) {
                                $risquecolor = $evolurisque1 != 0 ? intval($risque1 . $evolurisque1) : $risque1;
                            } else if ($loc1 == "N" && ($orientation == 1 || $orientation == 2 || $orientation == 3)) {
                                $risquecolor = $evolurisque1 != 0 ? intval($risque1 . $evolurisque1) : $risque1;
                            } else if ($loc1 == "E" && ($orientation == 3 || $orientation == 6 || $orientation == 9)) {
                                $risquecolor = $evolurisque1 != 0 ? intval($risque1 . $evolurisque1) : $risque1;
                            } else if ($loc1 == "S" && ($orientation == 7 || $orientation == 8 || $orientation == 9)) {
                                $risquecolor = $evolurisque1 != 0 ? intval($risque1 . $evolurisque1) : $risque1;
                            } else if ($loc2 == "W" && ($orientation == 1 || $orientation == 4 || $orientation == 7)) {
                                $risquecolor = $evolurisque2 != 0 ? intval($risque2 . $evolurisque2) : $risque2;
                            } else if ($loc2 == "N" && ($orientation == 1 || $orientation == 2 || $orientation == 3)) {
                                $risquecolor = $evolurisque2 != 0 ? intval($risque2 . $evolurisque2) : $risque2;
                            } else if ($loc2 == "E" && ($orientation == 3 || $orientation == 6 || $orientation == 9)) {
                                $risquecolor = $evolurisque2 != 0 ? intval($risque2 . $evolurisque2) : $risque2;
                            } else if ($loc2 == "S" && ($orientation == 7 || $orientation == 8 || $orientation == 9)) {
                                $risquecolor = $evolurisque2 != 0 ? intval($risque2 . $evolurisque2) : $risque2;
                            } else {
                                $risquecolor = $evolurisque1 != 0 ? intval($risque1 . $evolurisque1) : $risque1;
                            }
                        } else {
                            $risquecolor = 0;
                        }

                        if ($risquecolor > 10) {
                            $r1 = floor($risquecolor / 10);
                            $r2 = $risquecolor % 10;
                            $imod = $i % $pas;
                            $jmod = $j % $pas;
                            if ($imod < $pas / 2) {
                                $risquecolor = $r1;
                            } else {
                                $risquecolor = $r2;
                            }
                        }
                        $alpha = intval(127 / 2);
                        if (
                            ($penteNO && ($orientation == 1 || $orientation ==  5)) ||
                            ($penteN && ($orientation == 2 || $orientation ==  5)) ||
                            ($penteNE && ($orientation == 3 || $orientation ==  5)) ||
                            ($penteO && ($orientation == 4 || $orientation ==  5)) ||
                            ($penteE && ($orientation == 6 || $orientation ==  5)) ||
                            ($penteSO && ($orientation == 7 || $orientation ==  5)) ||
                            ($penteS && ($orientation == 8 || $orientation ==  5)) ||
                            ($penteSE && ($orientation == 9 || $orientation ==  5))
                        ) {
                            $alpha = 0;
                        }
                        $green = imagecolorallocatealpha($image, 44, 176, 81, $alpha);
                        $yellow = imagecolorallocatealpha($image, 254, 240, 53, $alpha);
                        $orange = imagecolorallocatealpha($image, 253, 127, 54, $alpha);
                        $red = imagecolorallocatealpha($image, 236, 11, 24, $alpha);
                        $redhigh = imagecolorallocatealpha($image, 131, 7, 12, $alpha);

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
                imagepng($image, "./images/risque/" . $filenumber . ".png");
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
