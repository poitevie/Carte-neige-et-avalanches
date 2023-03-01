<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: X-Requested-With');

$files = scandir('./hgt/massifs/pente/');
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
        if (file_exists($filespath . "pente/" . $filenumber . '.hgt')) {
            $hgt_line_records = 3600;
        } else
            die("Erreur : " . $filenumber . $fileext . " n'existe pas");

        if (!$fp = fopen($filespath . "pente/" . $filenumber . $fileext, "rb"))
            die("Erreur : N'a pas pu ouvrir le fichier de pente " . $filenumber . $fileext);
        else {
            if (!file_exists('images/pente')) {
                mkdir('images/pente', 0777, true);
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

            $image = imagecreatetruecolor($width, $height);
            $trans = imagecolorallocatealpha($image, 0, 0, 0, 127);
            $jaune = imagecolorallocatealpha($image, 241, 231, 11, 0);
            $orange = imagecolorallocatealpha($image, 248, 111, 33, 0);
            $rouge = imagecolorallocatealpha($image, 227, 3, 91, 0);
            $violet = imagecolorallocatealpha($image, 203, 135, 186, 0);
            imagesavealpha($image, true);
            imagefill($image, 0, 0, $trans);
            //génération de la tuile du massif
            for ($j = 0; $j < $height; $j += 1) {
                for ($i = 0; $i < $width; $i += 1) {
                    fseek($fp, 20 + ($i) * $hgt_value_size + ($j) * $width * $hgt_value_size);
                    $val = fread($fp, 2);
                    $pente = @unpack('n', $val)[1];

                    if ($pente >= 45) {
                        imagesetpixel($image, $i, $j, $violet);
                    } else if ($pente >= 40) {
                        imagesetpixel($image, $i, $j, $rouge);
                    } else if ($pente >= 35) {
                        imagesetpixel($image, $i, $j, $orange);
                    } else if ($pente >= 30) {
                        imagesetpixel($image, $i, $j, $jaune);
                    } else {
                        imagesetpixel($image, $i, $j, $trans);
                    }
                }
            }
            imagepng($image, "./images/pente/" . $filenumber . ".png");
            imagedestroy($image);
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
?>