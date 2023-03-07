<?php
include_once("../global.php");
include_once("../couleur.php");

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: X-Requested-With');

create_folder("../" . $path_neigetotale);
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
    global $path_altitude, $fileext, $path_orientation, $hgt_value_size, $limiteneigetotale, $pluie_couleur2, $couleurFin, $couleurDebut, $path_neigetotale, $imageext;
    if ($filenumber != "") {
        // Si le fichier binaire du massif existe
        if (!file_exists("../" . $path_altitude . $filenumber . $fileext))
            die("Erreur : " . $filenumber . $fileext . " n'existe pas");

        if (!$fp = fopen("../" . $path_altitude . $filenumber . $fileext, "rb"))
            die("Erreur : N'a pas pu ouvrir le fichier d'altitude " . $filenumber . $fileext);
        else {
            if (!$fp2 = fopen("../" . $path_orientation . $filenumber . $fileext, "rb"))
                die("Erreur : N'a pas pu ouvrir le fichier d'orientation " . $filenumber . $fileext);
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

                if (isset($xml["ENNEIGEMENT"])) {
                    $neige = $xml["ENNEIGEMENT"];
                    $niveaux = array();
                    foreach ($neige->NIVEAU as $niveau) {
                        $niveaux[] = array(
                            'alti' => (string) $niveau['ALTI'],
                            'n' => (string) $niveau['N'],
                            's' => (string) $niveau['S']
                        );
                    }
                    $limiteSud = $neige["LimiteSud"];
                    $limiteNord = $neige["LimiteNord"];

                    $image = imagecreatetruecolor($width, $height);
                    $trans = imagecolorallocatealpha($image, 0, 0, 0, 127);

                    imagesavealpha($image, true);
                    imagefill($image, 0, 0, $trans);
                    //génération de la tuile du massif
                    for ($j = 0; $j < $height; $j += 1) {
                        for ($i = 0; $i < $width; $i += 1) {
                            fseek($fp, 20 + ($i) * $hgt_value_size + ($j) * $width * $hgt_value_size);
                            $val = fread($fp, 2);
                            $alt = @unpack('s', $val)[1];

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
                            }
                            
                            else if ($alt < 0) {
                                $neigecolor = 5;
                            } else {
                                $neigecolor = 0;
                            }

                            if ($neigecolor == 0) {
                                imagesetpixel($image, $i, $j, $trans);
                            }
                            else if($neigecolor >=$limiteneigetotale) {
                                imagesetpixel($image, $i, $j,imagecolorallocatealpha($image, $pluie_couleur2[0], $pluie_couleur2[1], $pluie_couleur2[2], 0)); 
                            } else {
                                // Nombre de couleurs dans le dégradé
                                $nbCouleurs = $limiteneigetotale;

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
                    imagepng($image, "../" . $path_neigetotale . $filenumber . $imageext);
                    imagedestroy($image);
                } else {
                    die("Erreur : Il y a une erreur lors du chargement des données de météofrance");
                }
            }
        }
    }
}
?>