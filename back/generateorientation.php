<?php
$files = scandir('hgt/massifs/');
foreach ($files as $file) {
    $filespath = "hgt/massifs/";
    $filenumber = explode(".", $file)[0];
    if ($filenumber != "") {
        if (!$fp = fopen($filespath . $filenumber . ".hgt", "rb"))
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

            for ($j = 0; $j < $height; $j += 1) {
                for ($i = 0; $i < $width; $i += 1) {
                    fseek($fp, 20 + ($i) * $hgt_value_size + ($j) * $width * $hgt_value_size);
                    $val = fread($fp, 2);
                    $altC = @unpack('n', $val)[1];
                    fseek($fp, 20 + ($i) * $hgt_value_size + ($j) * $width * $hgt_value_size - 1);
                    $val = fread($fp, 2);
                    $altO = @unpack('n', $val)[1];
                    fseek($fp, 20 + ($i) * $hgt_value_size + ($j) * $width * $hgt_value_size + 1);
                    $val = fread($fp, 2);
                    $altE = @unpack('n', $val)[1];

                    fseek($fp, 20 + ($i) * $hgt_value_size + ($j) * ($width-1) * $hgt_value_size);
                    $val = fread($fp, 2);
                    $altN = @unpack('n', $val)[1];
                    fseek($fp, 20 + ($i) * $hgt_value_size + ($j) * ($width-1) * $hgt_value_size - 1);
                    $val = fread($fp, 2);
                    $altNO = @unpack('n', $val)[1];
                    fseek($fp, 20 + ($i) * $hgt_value_size + ($j) * ($width-1) * $hgt_value_size + 1);
                    $val = fread($fp, 2);
                    $altNE = @unpack('n', $val)[1];

                    fseek($fp, 20 + ($i) * $hgt_value_size + ($j) * ($width+1) * $hgt_value_size);
                    $val = fread($fp, 2);
                    $altS = @unpack('n', $val)[1];
                    fseek($fp, 20 + ($i) * $hgt_value_size + ($j) * ($width+1) * $hgt_value_size - 1);
                    $val = fread($fp, 2);
                    $altSO = @unpack('n', $val)[1];
                    fseek($fp, 20 + ($i) * $hgt_value_size + ($j) * ($width+1) * $hgt_value_size + 1);
                    $val = fread($fp, 2);
                    $altSE = @unpack('n', $val)[1];


                }
            }
        }
    }
}
?>