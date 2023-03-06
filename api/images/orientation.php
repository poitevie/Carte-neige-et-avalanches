<?php
include_once("../global.php");
include_once("../couleur.php");

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: X-Requested-With');

create_folder("../" . $path_orientation_img);
if(isset($_GET["massif"])) {
    generateImage($_GET["massif"]);
}
else {
    $files = scandir("../" . $path_orientation);
    foreach ($files as $file) {
        $filenumber = explode(".", $file)[0];
        generateImage($filenumber);
    }
}
function generateImage($filenumber) {
    global $path_orientation, $fileext, $no_couleur, $n_couleur, $ne_couleur, $o_couleur, $c_couleur, $e_couleur, $so_couleur, $s_couleur, $se_couleur, $hgt_value_size, $path_orientation_img, $imageext;
    if ($filenumber != "") {

        // Si le fichier binaire du massif existe
        if (!file_exists("../" . $path_orientation . $filenumber . $fileext))
            die("Erreur : " . $filenumber . $fileext . " n'existe pas");

        if (!$fp = fopen("../" . $path_orientation . $filenumber . $fileext, "rb"))
            die("Erreur : N'a pas pu ouvrir le fichier d'oritude " . $filenumber . $fileext);
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
            $no = imagecolorallocatealpha($image, $no_couleur[0], $no_couleur[1], $no_couleur[2], 0);
            $n = imagecolorallocatealpha($image, $n_couleur[0], $n_couleur[1], $n_couleur[2], 0);
            $ne = imagecolorallocatealpha($image, $ne_couleur[0], $ne_couleur[1], $ne_couleur[2], 0);
            $o = imagecolorallocatealpha($image, $o_couleur[0], $o_couleur[1], $o_couleur[2], 0);
            $c = imagecolorallocatealpha($image, $c_couleur[0], $c_couleur[1], $c_couleur[2], 0);
            $e = imagecolorallocatealpha($image, $e_couleur[0], $e_couleur[1], $e_couleur[2], 0);
            $so = imagecolorallocatealpha($image, $so_couleur[0], $so_couleur[1], $so_couleur[2], 0);
            $s = imagecolorallocatealpha($image, $s_couleur[0], $s_couleur[1], $s_couleur[2], 0);
            $se = imagecolorallocatealpha($image, $se_couleur[0], $se_couleur[1], $se_couleur[2], 0);
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
                            imagesetpixel($image, $i, $j, $trans);
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
            imagepng($image, "../" . $path_orientation_img . $filenumber . $imageext);
            imagedestroy($image);
        }
    }
}
?>