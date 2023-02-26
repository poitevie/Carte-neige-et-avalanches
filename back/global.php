<?php
$pas_rayure = 20;
$hgt_value_size = 2;
$hgt_line_records = 3600;
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

function create_folder($path)
{
    if (substr($path, -1) == "/") {
        $path = substr($$path, 0, -1);
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
?>