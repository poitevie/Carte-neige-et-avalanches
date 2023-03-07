<?php
include_once("../global.php");

create_folder("../" . $path_orientation);

$files = scandir("../" . $path_altitude);
foreach ($files as $file) {
    $filenumber = explode(".", $file)[0];

    if ($filenumber != "") {
        if (!$fp = fopen("../" . $path_altitude . $filenumber . ".hgt", "rb"))
            die("Erreur : N'a pas pu ouvrir le fichier d'altitude " . $filenumber . $fileext);
        else {
            if (!$fp2 = fopen("../" . $path_orientation . $filenumber . ".hgt", "w")) {
                die("Erreur : N'a pas pu ouvrir le fichier");
            } else {
                //Variables globales stockées dans le fichier
                fseek($fp, 0);
                $val = fread($fp, 2);
                $width = @unpack('n', $val)[1];
                fseek($fp2, 0);
                $valw = fwrite($fp2, pack("n", $width), 2);

                fseek($fp, 2);
                $val = fread($fp, 2);
                $height = @unpack('n', $val)[1];
                fseek($fp2, 2);
                $valw = fwrite($fp2, pack("n", $height), 2);

                fseek($fp, 4);
                $val = fread($fp, 4);
                $bglat = @unpack('f', $val)[1];
                fseek($fp2, 4);
                $valw = fwrite($fp2, pack("f", $bglat), 4);

                fseek($fp, 8);
                $val = fread($fp, 4);
                $bglon = @unpack('f', $val)[1];
                fseek($fp2, 8);
                $valw = fwrite($fp2, pack("f", $bglon), 4);

                fseek($fp, 12);
                $val = fread($fp, 4);
                $hdlat = @unpack('f', $val)[1];
                fseek($fp2, 12);
                $valw = fwrite($fp2, pack("f", $hdlat), 4);

                fseek($fp, 16);
                $val = fread($fp, 4);
                $hdlon = @unpack('f', $val)[1];
                fseek($fp2, 16);
                $valw = fwrite($fp2, pack("f", $hdlon), 4);

                for ($j = 0; $j < $height; $j += 1) {
                    for ($i = 0; $i < $width; $i += 1) {
                        fseek($fp, 20 + ($i) * $hgt_value_size + ($j) * $width * $hgt_value_size);
                        $val = fread($fp, 2);
                        $altC = abs(@unpack('s', $val)[1]);

                        if ($altC != 0 && $j != 0 && $j != $height && $i != 0 && $i != $width) {

                            $max = 0;
                            $orientation = 5;

                            for ($yo = -1; $yo <= 1; $yo++) {
                                for ($xo = -1; $xo <= 1; $xo++) {
                                    if ($xo != 0 && $yo != 0) {
                                        fseek($fp, 20 + ($i + $xo) * $hgt_value_size + ($j + $yo) * $width * $hgt_value_size);
                                        $val = fread($fp, 2);
                                        $alt = abs(@unpack('s', $val)[1]);

                                        $cal = $alt - $altC;
                                        if ($cal > $max) {
                                            $max = $cal;
                                            $orientation = 10 - (($xo + 2) + 3 * ($yo + 1));
                                        }
                                    }
                                }
                            }
                            fseek($fp2, 20 + ($i) * $hgt_value_size + ($j) * $width * $hgt_value_size);
                            $valw = fwrite($fp2, pack("n", $orientation), 2);
                        } else {
                            fseek($fp2, 20 + ($i) * $hgt_value_size + ($j) * $width * $hgt_value_size);
                            $valw = fwrite($fp2, pack("n", 0), 2);
                        }
                    }
                }
            }
        }
    }
}
?>