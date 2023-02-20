<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: X-Requested-With');


//Le paramètre massif correspond à l'id du massif ex: 07 pour la Chartreuse
if (isset($_GET["massif"])) {
    // VARIABLES GLOBALES
    $hgt_value_size = 2;
    $hgt_line_records = 3600;
    $hgt_step = 1 / $hgt_line_records;
    $hgt_line_size = $hgt_value_size * ($hgt_line_records + 1);
    $filespath = "hgt/massifs/";
    $filenumber = htmlspecialchars($_GET["massif"]);

    //Cache
    if (file_exists("cache/getaltmassif/" . $filenumber . ".json")) {
        readfile("cache/getaltmassif/" . $filenumber . ".json");
    } else {
        // Si le fichier binaire du massif existe
        if (file_exists($filespath . $filenumber . '.hgt')) {
            $fileext = '.hgt';
            $hgt_line_records = 3600;
        } else
            die("Erreur : " . $filenumber . $fileext . " n'existe pas");

        if (!$fp = fopen($filespath . $filenumber . $fileext, "rb"))
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

            $array = array("width" => $width, "height" => $height, "bg" => [$bglat, $bglon], "hd" => [$hdlat, $hdlon], "tile" => null);
            $tile = array();
            //génération de la tuile du massif
            for ($j = 0; $j < $height; $j += 1) {
                $line = array();
                for ($i = 0; $i < $width; $i += 1) {
                    fseek($fp, 20 + ($i) * $hgt_value_size + ($j) * $width * $hgt_value_size);
                    $val = fread($fp, 2);
                    $alt = @unpack('n', $val)[1];
                    array_push($line, $alt);
                }
                array_push($tile, $line);
            }
            $array["tile"] = $tile;

            //Affichage de la réponse + stockage dans le cache

            ob_start();
            echo json_encode($array);
            $page = ob_get_contents();
            ob_end_clean();
            file_put_contents("cache/getaltmassif/" . $filenumber . ".json", $page);
            echo $page;
        }
    }
} else {
    die("Merci d'indiquer le paramètre 'massif'");
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