<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: X-Requested-With');

$files = scandir('./hgt/massifs/orientation/');
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
        if (file_exists($filespath . "orientation/" . $filenumber . '.hgt')) {
            $hgt_line_records = 3600;
        } else
            die("Erreur : " . $filenumber . $fileext . " n'existe pas");

        if (!$fp = fopen($filespath . "orientation/" . $filenumber . $fileext, "rb"))
            die("Erreur : N'a pas pu ouvrir le fichier d'oritude " . $filenumber . $fileext);
        else {
            if (!file_exists('images/orientation')) {
                mkdir('images/orientation', 0777, true);
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
            $no = imagecolorallocatealpha($image, 0, 54, 115, 0);
            $n = imagecolorallocatealpha($image, 0, 119, 255, 0);
            $ne = imagecolorallocatealpha($image, 140, 194, 255, 0);
            $o = imagecolorallocatealpha($image, 0, 0, 0, 0);
            $c = imagecolorallocatealpha($image, 0, 255, 0, 0);
            $e = imagecolorallocatealpha($image, 255, 255, 255, 0);
            $so = imagecolorallocatealpha($image, 115, 42, 0, 0);
            $s = imagecolorallocatealpha($image, 255, 94, 0, 0);
            $se = imagecolorallocatealpha($image, 255, 182, 140, 0);
            imagesavealpha($image, true);
            imagefill($image, 0, 0, $trans);
            //génération de la tuile du massif
            for ($j = 0; $j < $height; $j += 1) {
                for ($i = 0; $i < $width; $i += 1) {
                    fseek($fp, 20 + ($i) * $hgt_value_size + ($j) * $width * $hgt_value_size);
                    $val = fread($fp, 2);
                    $or = @unpack('n', $val)[1];
                    //génération du code risque en fonction de l'oritude et des données météofrance

                    switch ($or) {
                        case 1:
                            imagesetpixel($image, $i, $j, $no);
                            break;

                        case 2:
                            imagesetpixel($image, $i, $j, $n);
                            break;

                        case 3:
                            imagesetpixel($image, $i, $j, $ne);
                            break;

                        case 4:
                            imagesetpixel($image, $i, $j, $o);
                            break;

                        case 5:
                            imagesetpixel($image, $i, $j, $c);
                            break;

                        case 6:
                            imagesetpixel($image, $i, $j, $e);
                            break;
                        case 7:
                            imagesetpixel($image, $i, $j, $so);
                            break;
                        case 8:
                            imagesetpixel($image, $i, $j, $s);
                            break;
                        case 9:
                            imagesetpixel($image, $i, $j, $se);
                            break;
                        default:
                            imagesetpixel($image, $i, $j, $trans);
                            break;
                    }
                }
            }
            imagepng($image, "./images/orientation/" . $filenumber . ".png");
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