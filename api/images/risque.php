<?php
include_once("../global.php");
include_once("../couleur.php");

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: X-Requested-With');

create_folder("../" . $path_risque);
if(isset($argv[1])) {
    generateImage($argv[1]);
}
else {
    $files = scandir("../" . $path_altitude);
    foreach ($files as $file) {
        $filenumber = explode(".", $file)[0];
        generateImage($filenumber);
    }
}
function generateImage($filenumber) {
    global $path_altitude, $fileext, $path_orientation, $path_pente, $hgt_value_size, $risque_1, $risque_2, $risque_3, $risque_4, $risque_5, $path_risque, $imageext;
    if ($filenumber != "") {
        if (!$fp = fopen("../" . $path_altitude . $filenumber . $fileext, "rb"))
            die("Erreur : N'a pas pu ouvrir le fichier d'altitude " . $filenumber . $fileext);
        if (!$fp2 = fopen("../" . $path_orientation . $filenumber . $fileext, "rb"))
            die("Erreur : N'a pas pu ouvrir le fichier d'orientation " . $filenumber . $fileext);
        if (!$fp3 = fopen("../" . $path_pente . $filenumber . $fileext, "rb"))
            die("Erreur : N'a pas pu ouvrir le fichier de pente " . $filenumber . $fileext);
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
                            $risquecolor = $risquecolor % 10;
                        }
                        
                        $alpha = intval(127 / 2);
                        if (
                            ($penteNO && ($orientation == 1 || $orientation == 5)) ||
                            ($penteN && ($orientation == 2 || $orientation == 5)) ||
                            ($penteNE && ($orientation == 3 || $orientation == 5)) ||
                            ($penteO && ($orientation == 4 || $orientation == 5)) ||
                            ($penteE && ($orientation == 6 || $orientation == 5)) ||
                            ($penteSO && ($orientation == 7 || $orientation == 5)) ||
                            ($penteS && ($orientation == 8 || $orientation == 5)) ||
                            ($penteSE && ($orientation == 9 || $orientation == 5))
                        ) {
                            $alpha = 0;
                        }

                        $couleur_1 = imagecolorallocatealpha($image, $risque_1[0], $risque_1[1], $risque_1[2], $alpha);
                        $couleur_2 = imagecolorallocatealpha($image, $risque_2[0], $risque_2[1], $risque_2[2], $alpha);
                        $couleur_3 = imagecolorallocatealpha($image, $risque_3[0], $risque_3[1], $risque_3[2], $alpha);
                        $couleur_4 = imagecolorallocatealpha($image, $risque_4[0], $risque_4[1], $risque_4[2], $alpha);
                        $couleur_5 = imagecolorallocatealpha($image, $risque_5[0], $risque_5[1], $risque_5[2], $alpha);

                        switch ($risquecolor) {
                            case 0:
                                imagesetpixel($image, $i, $j, $trans);
                                break;

                            case 1:
                                imagesetpixel($image, $i, $j, $couleur_1);
                                break;

                            case 2:
                                imagesetpixel($image, $i, $j, $couleur_2);
                                break;

                            case 3:
                                imagesetpixel($image, $i, $j, $couleur_3);
                                break;

                            case 4:
                                imagesetpixel($image, $i, $j, $couleur_4);
                                break;

                            case 5:
                                imagesetpixel($image, $i, $j, $couleur_5);
                                break;
                            default:
                                imagesetpixel($image, $i, $j, $trans);
                                break;
                        }
                    }
                }
                imagepng($image, "../" . $path_risque . $filenumber . $imageext);
                imagedestroy($image);
            } else {
                die("Erreur : Il y a une erreur lors du chargement des données de météofrance");
            }
        }
    }
}
?>