<?php
include_once("../global.php");
include_once("../couleur.php");

create_folder("../" . $path_pente_img);
if(isset($argv[1])) {
    generateImage($argv[1]);
}
else {
    $files = scandir("../" . $path_pente);
    foreach ($files as $file) {
        $filenumber = explode(".", $file)[0];
        generateImage($filenumber);
    }
}
function generateImage($filenumber) {
    global $path_pente, $fileext, $hgt_value_size, $path_pente_img, $imageext;
    if ($filenumber != "") {

        // Si le fichier binaire du massif existe
        if (!file_exists("../" . $path_pente . $filenumber . $fileext))
            die("Erreur : " . $filenumber . $fileext . " n'existe pas");

        if (!$fp = fopen("../" . $path_pente . $filenumber . $fileext, "rb"))
            die("Erreur : N'a pas pu ouvrir le fichier de pente " . $filenumber . $fileext);
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
                    $pente = @unpack('n', $val)[1];
                    $pente = $pente > 50 ? 50 : $pente;

                    if ($pente >= 45) {
                        imagesetpixel($image, $i, $j, imagecolorallocatealpha($image, 255-($pente-45)*255/5, 0, 255-($pente-45)*255/5, 0));
                    } else if ($pente >= 40) {
                        imagesetpixel($image, $i, $j, imagecolorallocatealpha($image, 255, 0, 255-($pente-40)*255/5, 0));
                    } else if ($pente >= 35) {
                        imagesetpixel($image, $i, $j, imagecolorallocatealpha($image, 255, 128-($pente-35)*128/5, 0, 0));
                    } else if ($pente >= 30) {
                        imagesetpixel($image, $i, $j, imagecolorallocatealpha($image, 255, 255-($pente-30)*128/5, 0, 0));
                    } else {
                        imagesetpixel($image, $i, $j, $trans);
                    }
                }
            }
            imagepng($image, "../" . $path_pente_img . $filenumber . $imageext);
            imagedestroy($image);
        }
    }
}
?>