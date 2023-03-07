<?php
include_once("../global.php");

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: X-Requested-With');

create_folder("../" . $path_altitude);

//Tableaux pour la génération des fichiers json contenant les informations des encadrements des massifs
$cadrealpes = array();
$cadrecorse = array();
$cadrepyrenees = array();
$cadremassif = array();

//Récupération du fichier geojson avec toutes les délimitations des massifs
if (!$jsontext = file_get_contents("../" . $path_geojson . "massifs.geojson"))
    die("Erreur : N'a pas pu ouvrir le fichier geojson des massifs");
else {
    $json = json_decode($jsontext);
    $features = $json->{"features"};

    //Parcours de tous les massifs
    for ($i = 0; $i < count($features); $i++) {
        //id du massif (ex: 07 pour la chartreuse)
        $id = $features[$i]->{"properties"}->{"id"};

        //Génération d'un fichier hgt par massif
        generatehgt($features[$i], $id);
    }

    //Sauvegarde des fichiers
    $content = json_encode($cadrealpes);
    file_put_contents("../" . $path_geojson . "cadrealpes.json", $content);
    $content = json_encode($cadrecorse);
    file_put_contents("../" . $path_geojson . "cadrecorse.json", $content);
    $content = json_encode($cadrepyrenees);
    file_put_contents("../" . $path_geojson . "cadrepyrenees.json", $content);
    $content = json_encode($cadremassif);
    file_put_contents("../" . $path_geojson . "cadremassif.json", $content);
}

//Fonction permettant de définir un cadre autour des massifs (coordonnées des 4 points)
function getcadremassif($massif)
{
    global $hgt_value_size, $hgt_line_records;
    $coord = $massif->{"geometry"}->{"coordinates"}[0];
    $minlat = $coord[0][1];
    $minlon = $coord[0][0];
    $maxlat = $coord[0][1];
    $maxlon = $coord[0][0];
    for ($i = 1; $i < count($coord); $i++) {
        $lat = $coord[$i][1];
        $lon = $coord[$i][0];
        if ($lat < $minlat) {
            $minlat = $lat;
        } else if ($lat > $maxlat) {
            $maxlat = $lat;
        }
        if ($lon < $minlon) {
            $minlon = $lon;
        } else if ($lon > $maxlon) {
            $maxlon = $lon;
        }
    }
    return ["bg" => [$minlat, $minlon], "hg" => [$maxlat, $minlon], "hd" => [$maxlat, $maxlon], "bd" => [$minlat, $maxlon]];
}
function generatehgt($massif, $idfile)
{
    global $hgt_value_size, $hgt_line_size, $hgt_line_records, $hgt_step, $cadremassif, $cadrealpes, $cadrecorse, $cadrepyrenees, $path_maillage, $path_altitude, $fileext, $id_alpes, $id_corse, $id_pyrenees;
    
    //Récupération du cadre du massif
    $cadre = getcadremassif($massif);
    $coord = $massif->{"geometry"}->{"coordinates"}[0];

    //Hauteur et largeur en nombre de points
    $height = ceil(($cadre["hd"][0] - $cadre["bg"][0]) * $hgt_line_records);
    $width = ceil(($cadre["hd"][1] - $cadre["bg"][1]) * $hgt_line_records);

    $coordxy = [];
    $arr = array("id" => $idfile, "cadre" => $cadre);
    array_push($cadremassif, $arr);
    if(in_array($idfile, $id_alpes)) {
        array_push($cadrealpes, $arr);
    }
    else if(in_array($idfile, $id_corse)) {
        array_push($cadrecorse, $arr);
    }
    else if(in_array($idfile, $id_pyrenees)) {
        array_push($cadrepyrenees, $arr);
    }
    else {
        die("Le massif ".$idfile." n'est pas dans les listes de massifs (alpes, corse, ou pyrénées)");
    }

    //Traduction des coordonnées du polygone d'un massif en lat lon vers indices x y.
    for ($i = 0; $i < count($coord); $i++) {
        $x = floor((($coord[$i][0] - $cadre["bg"][1]) / ($cadre["hd"][1] - $cadre["bg"][1]) * $width));
        $y = floor((1 - (($coord[$i][1] - $cadre["bg"][0]) / ($cadre["hd"][0] - $cadre["bg"][0]))) * $height);
        array_push($coordxy, [$x, $y]);
    }

    //Numéro des fichiers en bas à gauche et en haut à droite sur lesquels se situe le massif
    $bgfile = [floor($cadre["bg"][0]), floor($cadre["bg"][1])];
    $hdfile = [floor($cadre["hd"][0]), floor($cadre["hd"][1])];

    $files = array();
    //Parcours et ouverture de tous les fichiers sur lesquels se situe le massif 
    for ($n = $bgfile[0]; $n <= $hdfile[0]; $n++) {
        $filesline = array();
        for ($e = $bgfile[1]; $e <= $hdfile[1]; $e++) {
            $filenumber = getfilenumber($n, $e);

            if (!$fp = fopen("../" . $path_maillage . $filenumber . $fileext, "rb"))
                die("Erreur : N'a pas pu ouvrir le fichier d'altitude");
            else {
                array_push($filesline, $fp);
            }
        }
        array_push($files, $filesline);
    }
    $files = array_reverse($files);
    if (!$fp = fopen("../" . $path_altitude . $idfile . $fileext, "w")) {
        die("Erreur : N'a pas pu ouvrir le fichier");
    } else {
        $indexlathg = $hgt_line_records - floor(($cadre["hg"][0] - floor($cadre["hg"][0])) / $hgt_step);
        $indexlonhg = floor(($cadre["hg"][1] - floor($cadre["hg"][1])) / $hgt_step);
        $indexhg = $hgt_line_size * $indexlathg + $hgt_value_size * $indexlonhg;

        //On écrit dans le fichier binaire les variables globales du massif
        fseek($fp, 0);
        $valw = fwrite($fp, pack("n", $width), 2);
        fseek($fp, 2);
        $valw = fwrite($fp, pack("n", $height), 2);
        fseek($fp, 4);
        $valw = fwrite($fp, pack("f", $cadre["bg"][0]), 4);
        fseek($fp, 8);
        $valw = fwrite($fp, pack("f", $cadre["bg"][1]), 4);
        fseek($fp, 12);
        $valw = fwrite($fp, pack("f", $cadre["hd"][0]), 4);
        fseek($fp, 16);
        $valw = fwrite($fp, pack("f", $cadre["hd"][1]), 4);

        //Parcours des fichiers pour isoler le massif
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {

                //Identifiant du fichier
                $filey = intdiv(intdiv($indexhg, $hgt_line_size) + $y, $hgt_line_size / 2);
                $filex = intdiv(($indexhg % $hgt_line_size) + ($x * 2), $hgt_line_size);
                $file = $files[$filey][$filex];
                //index dans le fichier en question
                fseek($file, (($indexhg + ($x * 2)) - $filex * $hgt_line_size) + ($y * $hgt_line_size) - $filey * ($hgt_line_size) * ($hgt_line_records + 1));
                $val = fread($file, 2);
                $alt = @unpack('n', $val)[1];

                fseek($fp, 20 + $x * $hgt_value_size + $y * $width * $hgt_value_size);
                //si le point est dans le massif on l'ajoute sinon on ajoute 0
                if(est_sur_contour_polygone($coordxy, [$x, $y], 3)) {
                    $valw = fwrite($fp, pack("s", -$alt), 2);
                }
                else if (is_point_in_polygon([$x, $y], $coordxy))
                    $valw = fwrite($fp, pack("s", $alt), 2);
                else
                    $valw = fwrite($fp, pack("s", 0), 2);
            }
        }
    }
}
function is_point_in_polygon($point, $polygon_coordinates)
{
    $n = count($polygon_coordinates);
    $inside = false;
    $p1x = $polygon_coordinates[0][0];
    $p1y = $polygon_coordinates[0][1];
    for ($i = 0; $i <= $n; $i++) {
        $p2x = $polygon_coordinates[$i % $n][0];
        $p2y = $polygon_coordinates[$i % $n][1];
        if ($point[1] > min($p1y, $p2y)) {
            if ($point[1] <= max($p1y, $p2y)) {
                if ($point[0] <= max($p1x, $p2x)) {
                    if ($p1y != $p2y) {
                        $x_inters = ($point[1] - $p1y) * ($p2x - $p1x) / ($p2y - $p1y) + $p1x;
                    }
                    if ($p1x == $p2x || $point[0] <= $x_inters) {
                        $inside = !$inside;
                    }
                }
            }
        }
        $p1x = $p2x;
        $p1y = $p2y;
    }
    return $inside;
}
function est_sur_contour_polygone($polygone, $point, $epaisseur_contour) {
    $x = $point[0];
    $y = $point[1];
    $n = count($polygone);
    $est_sur_contour = false;
    $i = 0;
    $j = $n - 1;
    while ($i < $n) {
        $xi = $polygone[$i][0];
        $yi = $polygone[$i][1];
        $xj = $polygone[$j][0];
        $yj = $polygone[$j][1];
        // Détermine l'équation de la droite passant par les points i et j
        if ($xj - $xi == 0) {
            $a = INF;
            $b = $xi;
        } else {
            $a = ($yj - $yi) / ($xj - $xi);
            $b = $yi - $a * $xi;
        }
        // Détermine la distance du point à la droite
        $dist = abs($a * $x - $y + $b) / (sqrt($a ** 2 + 1));
        if ($dist <= $epaisseur_contour / 2 &&
            min($xi, $xj) - $epaisseur_contour / 2 <= $x && $x <= max($xi, $xj) + $epaisseur_contour / 2 &&
            min($yi, $yj) - $epaisseur_contour / 2 <= $y && $y <= max($yi, $yj) + $epaisseur_contour / 2) {
            $est_sur_contour = true;
            break;
        }
        $j = $i;
        $i += 1;
    }
    return $est_sur_contour;
}
?>