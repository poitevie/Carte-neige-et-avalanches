<?php
include_once("../global.php");
include_once("../couleur.php");

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: X-Requested-With');

create_folder("../" . $path_neigefraiche);
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
    global $path_altitude, $fileext, $hgt_value_size, $pas_rayure, $limiteneigefraiche, $pluie_couleur, $pluie_couleur2, $couleurFin, $couleurDebut, $path_neigefraiche, $imageext;
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
                //Recupération de la neige fraiche et stockage si pluie pour hachage future
                $neige = $xml["NEIGEFRAICHE"];
                $neigefraiche = array();
                $somme = 0;
                $pluie = false;

                for($i = 0;$i<4;$i++){
                    if($neige->NEIGE24H[$i]['SS241']==-2){
                        $neigefraiche[] = 0;
                        $pluie = true;
                    }
                    else{
                        $neigefraiche[] = $neige->NEIGE24H[$i]['SS241'];
                    }
                }
                //Récupération de la neige fraiche tombé les 4 derniers   
                $somme = $neigefraiche[0] + $neigefraiche[1] + $neigefraiche[2] + $neigefraiche[3];
                $altneige = $neige["ALTITUDESS"];

                //Récupération de la valeur max de l'iso 0 
                $meteo = $xml["METEO"];
                $iso = 0;
   
                foreach( $meteo->ECHEANCE as $echeance){

                    if(intval($echeance['ISO0'])>$iso){
                        $iso = intval($echeance['ISO0']);
                    
                    }
                }

                $image = imagecreatetruecolor($width, $height);
                $trans = imagecolorallocatealpha($image, 0, 0, 0, 127);
                $gray = imagecolorallocatealpha($image, 52, 56, 82, 0);
                imagesavealpha($image, true);
                imagefill($image, 0, 0, $trans);
                //génération de la tuile du massif
                for ($j = 0; $j < $height; $j += 1) {
                    for ($i = 0; $i < $width; $i += 1) {
                        fseek($fp, 20 + ($i) * $hgt_value_size + ($j) * $width * $hgt_value_size);
                        $val = fread($fp, 2);
                        $alt = @unpack('s', $val)[1];
                        $neigecolor = 0;

                        if ($alt > $iso) {
                            //Affichage uniquement de point gris si il y a de la pluie 
                            if ($pluie && $somme == 0) {
                                $imod = $i % $pas_rayure;
                                $jmod = $j % $pas_rayure;
                                if (($jmod < $pas_rayure / 4 && $imod < $pas_rayure / 4) || ($jmod >= $pas_rayure / 2 && $imod >= $pas_rayure / 2 && $jmod < 3 * $pas_rayure / 4 && $imod < 3 * $pas_rayure / 4)) {
                                    $neigecolor = -2;
                                }
                                else {
                                    $neigecolor=0;
                                }
                            }
                            //Hachage
                            else if ($pluie && $somme > 0) {

                                $imod = $i % $pas_rayure;
                                $jmod = $j % $pas_rayure;
                                if (($jmod < $pas_rayure / 4 && $imod < $pas_rayure / 4) || ($jmod >= $pas_rayure / 2 && $imod >= $pas_rayure / 2 && $jmod < 3 * $pas_rayure / 4 && $imod < 3 * $pas_rayure / 4)) {
                                    $neigecolor = -2;
                                } else {
                                    $neigecolor = $somme;
                                }
                            } else if ($alt<$altneige){
                                if($somme == 0 ){
                                    $neigecolor=0;
                                }
                                else {
                                    $neigecolor = 1;
                                }
                        
                            }
                            else {
                                $neigecolor = $somme;
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
                        //Couleur si pluie
                        else if ($neigecolor == -2) {
                            imagesetpixel($image, $i, $j, imagecolorallocatealpha($image, $pluie_couleur[0], $pluie_couleur[1], $pluie_couleur[2], 0));
                        }
                        else if($neigecolor >=$limiteneigefraiche) {
                            imagesetpixel($image, $i, $j, imagecolorallocatealpha($image, $pluie_couleur2[0], $pluie_couleur2[1], $pluie_couleur2[2], 0));
                        }
                        else {
                            // Nombre de couleurs dans le dégradé
                            $nbCouleurs = $limiteneigefraiche;

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
                imagepng($image, "../" . $path_neigefraiche . $filenumber . $imageext);
                imagedestroy($image);
            } else {
                die("Erreur : Il y a une erreur lors du chargement des données de météofrance");
            }
        }
    }
}
?>