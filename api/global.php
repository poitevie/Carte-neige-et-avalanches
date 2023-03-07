<?php
$id_alpes = ["07","08","04","12","14","15","18","19","16","20","21","22","23","17","13","09","11","10","06","02","05","03","01"];
$id_corse = ["40", "41"];
$id_pyrenees = ["74","73","71","72","70","69","68","67","65","66","64"];

$pas_rayure = 20;
$hgt_value_size = 2;
$hgt_line_records = 3600;
$limiteneigefraiche = 100;
$limiteneigefraicheprevision =100;
$limiteneigetotale = 200;
$fileext = '.hgt';
$imageext = '.png';
$hgt_step = 1 / $hgt_line_records;
$hgt_line_size = $hgt_value_size * ($hgt_line_records + 1);
$path_geojson = "geojson/";
$path_hgt = "hgt/";
$path_images = "images/";
$path_maillage = $path_hgt . "maillage/";
$path_massifs = $path_hgt . "massifs/";
$path_altitude = $path_massifs . "altitude/";
$path_orientation = $path_massifs . "orientation/";
$path_pente = $path_massifs . "pente/";
$path_neigefraiche = $path_images . "neigefraiche/";
$path_neigefraicheprevision = $path_images . "neigefraicheprevision/";
$path_neigetotale = $path_images . "neigetotale/";
$path_risque = $path_images . "risque/";
$path_altitude_img = $path_images . "altitude/";
$path_pente_img = $path_images . "pente/";
$path_orientation_img = $path_images . "orientation/";

// Contours massifs
$offset_couleur_contour = 63;
$epaisseur_contour = 30;

function create_folder($path)
{
    if (substr($path, -1) == "/") {
        $path = substr($path, 0, -1);
    }
    if (!file_exists($path)) {
        mkdir($path, 0777, true);
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

// Renvoie la valeur bornée dans l'intervalle 0 255
function getColorInterval($value) {
    return $value < 0 ? 0 : ($value > 255 ? 255 : $value);
}
?>