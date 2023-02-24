<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: X-Requested-With');

$files = scandir('./hgt/massifs/altitude/');
foreach ($files as $file) {
    // VARIABLES GLOBALES

    $pas = 20;
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
        else {
            if (!file_exists('images/neigefraiche')) {
                mkdir('images/neigefraiche', 0777, true);
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

            //Fichier neige de météofrance en fonction du massif.
            $xml = (array) simplexml_load_string(file_get_contents("http://api.meteofrance.com/files/mountain/bulletins/BRA" . $filenumber . ".xml"));

            if (isset($xml["NEIGEFRAICHE"])) {

                //Recupération de la neige fraiche et stockage si pluie pour hachage future
                $neige = $xml["NEIGEFRAICHE"];
                $neigefraiche = array();
                $somme = 0;
                $pluie = false;
                foreach ($neige->NEIGE24H as $neige24h) {
                    if ($neige24h['SS241'] == -2) {
                        $neigefraiche[] = 0;
                        $pluie = true;
                    } else {
                        $neigefraiche[] = $neige24h['SS241'];
                    }
                }

                //Récupération de la neige fraiche tombé les 4 derniers   
                $somme = $neigefraiche[0] + $neigefraiche[1] + $neigefraiche[2] + $neigefraiche[3];
                $altneige = $neige["ALTITUDESS"];


                $image = imagecreatetruecolor($width, $height);
                $trans = imagecolorallocatealpha($image, 0, 0, 0, 127);
                $green = imagecolorallocatealpha($image, 44, 176, 81, 0);
                $yellow = imagecolorallocatealpha($image, 254, 240, 53, 0);
                $orange = imagecolorallocatealpha($image, 253, 127, 54, 0);
                $red = imagecolorallocatealpha($image, 236, 11, 24, 0);
                $redhigh = imagecolorallocatealpha($image, 131, 7, 12, 0);
                $gray = imagecolorallocatealpha($image, 52, 56, 82, 0);
                imagesavealpha($image, true);
                imagefill($image, 0, 0, $trans);
                //génération de la tuile du massif
                for ($j = 0; $j < $height; $j += 1) {
                    for ($i = 0; $i < $width; $i += 1) {
                        fseek($fp, 20 + ($i) * $hgt_value_size + ($j) * $width * $hgt_value_size);
                        $val = fread($fp, 2);
                        $alt = @unpack('n', $val)[1];
                        $neigecolor = 0;


                        if ($alt > $altneige) {
                            if ($pluie && $somme == 0) {
                                $neigecolor = -2;
                            }
                            //Hachage
                            else if ($pluie && $somme > 0) {

                                $imod = $i % $pas;
                                $jmod = $j % $pas;
                                if (($jmod < $pas / 4 && $imod < $pas / 4) || ($jmod >= $pas / 2 && $imod >= $pas / 2 && $jmod < 3 * $pas / 4 && $imod < 3 * $pas / 4)) {
                                    $neigecolor = -2;
                                } else {
                                    $neigecolor = $somme;
                                }
                            } else {
                                $neigecolor = $somme;
                            }
                        } else {
                            $neigecolor = 0;
                        }

                        if ($neigecolor == 0) {
                            imagesetpixel($image, $i, $j, $trans);
                        }
                        //COuleur rouge si pluie
                        else if ($neigecolor == -2) {
                            imagesetpixel($image, $i, $j, $gray);
                        } else {




                            // Couleurs de départ et d'arrivée
                            $couleurDebut = [132, 214, 249]; // Bleu clair
                            $couleurFin = [0, 48, 67]; // Bleu foncé

                            // Nombre de couleurs dans le dégradé
                            $nbCouleurs = 100;

                            // Calcul de la différence entre chaque composante de couleur
                            $diffCouleur = [
                                ($couleurFin[0] - $couleurDebut[0]) / ($nbCouleurs - 1),
                                ($couleurFin[1] - $couleurDebut[1]) / ($nbCouleurs - 1),
                                ($couleurFin[2] - $couleurDebut[2]) / ($nbCouleurs - 1)
                            ];

                            // Création du dégradé
                            $r = round($couleurDebut[0] + $diffCouleur[0] * $neigecolor);
                            $g = round($couleurDebut[1] + $diffCouleur[1] * $neigecolor);
                            $b = round($couleurDebut[2] + $diffCouleur[2] * $neigecolor);
                            imagesetpixel($image, $i, $j, imagecolorallocatealpha($image, $r, $g, $b, 0));
                        }
                    }
                }
                imagepng($image, "./images/neigefraiche/" . $filenumber . ".png");
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
