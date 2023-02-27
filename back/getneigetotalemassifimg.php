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
            if (!$fp2 = fopen($filespath . "orientation/" . $filenumber . $fileext, "rb"))
                die("Erreur : N'a pas pu ouvrir le fichier d'orientation " . $filenumber . $fileext);
            else {
            if (!file_exists('images/neigetotale')) {
                mkdir('images/neigetotale', 0777, true);
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

            if (isset($xml["ENNEIGEMENT"])) {


                $neige = $xml["ENNEIGEMENT"];
                $niveaux = array();
                foreach ($neige->NIVEAU as $niveau) {
                    $niveaux[] = array(
                        'alti' => (string)$niveau['ALTI'],
                        'n' => (string)$niveau['N'],
                        's' => (string)$niveau['S']
                    );
                }

                $limiteSud = $neige["LimiteSud"];
                $limiteNord = $neige["LimiteNord"];


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

                        fseek($fp2, 20 + ($i) * $hgt_value_size + ($j) * $width * $hgt_value_size);
                        $val = fread($fp2, 2);
                        $ori = @unpack('n', $val)[1];
                        //génération du code risque en fonction de l'altitude et des données météofrance

                        $neigecolor = 0;
                        if ($alt > 0) {
                            if ($ori == 6 || $ori == 4 || (($alt < $limiteNord) && ($alt < $limiteSud))) {
                                $neigecolor = 0;
                            } else if (($ori == 1 || $ori == 2 || $ori == 3 || $ori == 5) && ($alt >= $limiteNord) && ($limiteNord != -1)) { //comprend NO, NE, N, sommet
                                if ($alt >= $niveaux[2]["alti"]) {
                                    $neigecolor = $niveaux[2]["n"];
                                } else if ($alt >= $niveaux[1]["alti"]) {
                                    $neigecolor = $niveaux[1]["n"];
                                } else if ($alt >= $niveaux[0]["alti"]) {
                                    $neigecolor = $niveaux[0]["n"];
                                }
                            } else if (($ori == 7 || $ori == 8 || $ori == 9) && ($alt >= $limiteSud) && ($limiteSud != -1)) { //comprend SO, SE, S
                                if ($alt >= $niveaux[2]["alti"]) {
                                    $neigecolor = $niveaux[2]["s"];
                                } else if ($alt >= $niveaux[1]["alti"]) {
                                    $neigecolor = $niveaux[1]["s"];
                                } else if ($alt >= $niveaux[0]["alti"]) {
                                    $neigecolor = $niveaux[0]["s"];
                                }
                            } else {
                                $neigecolor = 0;
                            }
                        } else {
                            $neigecolor = 0;
                        }

                        if ($neigecolor == 0) {
                            imagesetpixel($image, $i, $j, $trans);
                        }
                        else if($neigecolor >=200) {
                            imagesetpixel($image, $i, $j,imagecolorallocatealpha($image, 0, 48, 67, 0));
                        } else {

                            // Couleurs de départ et d'arrivée
                            $couleurDebut = [132, 214, 249]; // Bleu clair
                            $couleurFin = [0, 48, 67]; // Bleu foncé

                            // Nombre de couleurs dans le dégradé
                            $nbCouleurs = 200;

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
                imagepng($image, "./images/neigetotale/" . $filenumber . ".png");
                imagedestroy($image);
            } else {
                die("Erreur : Il y a une erreur lors du chargement des données de météofrance");
            }
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
