<?php
include_once("../global.php");
include_once("../couleur.php");

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: X-Requested-With');

create_folder("../" . $path_neigefraicheprevision);

$files = scandir("../" . $path_altitude);
foreach ($files as $file) {
    $filenumber = explode(".", $file)[0];
    if ($filenumber != "") {
        // Si le fichier binaire du massif existe
        if (!file_exists("../" . $path_altitude . $filenumber . $fileext))
            die("Erreur : " . $filenumber . $fileext . " n'existe pas");

        if (!$fp = fopen("../" . $path_altitude . $filenumber . $fileext, "rb"))
            die("Erreur : N'a pas pu ouvrir le fichier d'altitude " . $filenumber . $fileext);
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

            //Fichier neige de météofrance en fonction du massif.
            $xml = (array) simplexml_load_string(file_get_contents("http://api.meteofrance.com/files/mountain/bulletins/BRA" . $filenumber . ".xml"));

            if (isset($xml["NEIGEFRAICHE"])) {
                //Récupération de la neige 
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
                $somme = $neigefraiche[4] + $neigefraiche[5];
                $altneige = $neige["ALTITUDESS"];

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
                        $neigecolor = 0;

                        if ($alt > $altneige) {
                            if ($pluie && $somme == 0) {
                                $neigecolor = -2;
                            }
                            //Hachage 
                            else if ($pluie && $somme>0 ){
                                $imod = $i % $pas_rayure;
                                $jmod = $j % $pas_rayure;
                                if (($jmod < $pas_rayure / 4 && $imod < $pas_rayure / 4) || ($jmod >= $pas_rayure / 2 && $imod >= $pas_rayure / 2 && $jmod < 3 * $pas_rayure / 4 && $imod < 3 * $pas_rayure / 4)) {
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
                        //Couleur si pluie
                        else if ($neigecolor == -2) {
                            imagesetpixel($image, $i, $j, imagecolorallocatealpha($image, $pluie_couleur[0], $pluie_couleur[1], $pluie_couleur[2], 0));
                        } else {
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
                imagepng($image, "../" . $path_neigefraicheprevision . $filenumber . $imageext);
                imagedestroy($image);
            } else {
                die("Erreur : Il y a une erreur lors du chargement des données de météofrance");
            }
        }
    }
}
?>