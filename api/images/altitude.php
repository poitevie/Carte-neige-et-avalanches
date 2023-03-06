<?php
include_once("../global.php");
include_once("../couleur.php");

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: X-Requested-With');

create_folder("../" . $path_altitude_img);
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
    global $path_altitude, $fileext, $hgt_value_size, $path_altitude_img, $imageext;
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

                    imagesetpixel($image, $i, $j, colorScale($alt, $image));
                }
            }
            imagepng($image, "../" . $path_altitude_img . $filenumber . $imageext);
            imagedestroy($image);
        }
    }
}

function colorScale($alt, $image) {
    if($alt == 0) {
        return imagecolorallocatealpha($image, 0, 0, 0, 127);
    }
    $newAlt = $alt > 4809 ? 4809 : $alt;
    if($newAlt <= 801) {    // Bleu -> Cyan
        return imagecolorallocatealpha($image, 0, 0, 255, 127 - $newAlt*127/801);
    }
    else if ($newAlt <= 1603) {  // Cyan -> Vert
        return imagecolorallocatealpha($image, 0, ($newAlt-801)*255/(1603-801), 255, 0);
    }
    else if ($newAlt <= 2404) {  // Vert -> Jaune
        return imagecolorallocatealpha($image, 0, 255, 255-($newAlt-1603)*255/(2404-1603), 0);
    }
    else if ($newAlt <= 3206) {  // Jaune -> Rouge
        return imagecolorallocatealpha($image, ($newAlt-2404)*255/(3206-2404), 255, 0, 0);
    }
    else if ($newAlt <= 4007) {  // Jaune -> Rouge
        return imagecolorallocatealpha($image, 255, 255-($newAlt-3206)*255/(4007-3206), 0, 0);
    }
    else {  // Rouge -> Noir
        return imagecolorallocatealpha($image, 255-($newAlt-4007)*255/(4809-4007), 0, 0, 0);
    }
}
