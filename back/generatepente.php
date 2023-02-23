<?php

function getPente($max, $xo, $yo)
{
    if ($yo == 0 && ($xo == -1 || $xo == 1)) {   // Ouest et Est
        return round(atan($max / 20) * (180 / M_PI));
    } else if ($xo == 0 && ($yo == -1 || $yo == 1)) {     // Nord et Sud
        return round(atan($max / 30) * (180 / M_PI));
    } else {  // Autre
        return round(atan($max / 36.0555127546) * (180 / M_PI));
    }
}

$files = scandir('hgt/massifs/altitude/');
if (!file_exists('hgt/massifs/pente')) {
    mkdir('hgt/massifs/pente', 0777, true);
}
foreach ($files as $file) {
    $hgt_value_size = 2;
    $hgt_line_records = 3600;
    $hgt_step = 1 / $hgt_line_records;
    $hgt_line_size = $hgt_value_size * ($hgt_line_records + 1);
    $filespath = "hgt/massifs/";
    $filenumber = explode(".", $file)[0];

    if ($filenumber != "") {
        if (!$fp = fopen($filespath . "altitude/" . $filenumber . ".hgt", "rb"))
            die("Erreur : N'a pas pu ouvrir le fichier d'altitude " . $filenumber . $fileext);
        else {
            if (!$fp2 = fopen($filespath . "pente/" . $filenumber . ".hgt", "w")) {
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
                        $altC = @unpack('n', $val)[1];

                        if ($altC != 0 && $j != 0 && $j != $height && $i != 0 && $i != $width) {

                            $max = 0;
                            $pente = 0;

                            for ($yo = -1; $yo <= 1; $yo++) {
                                for ($xo = -1; $xo <= 1; $xo++) {
                                    if ($xo != 0 && $yo != 0) {
                                        fseek($fp, 20 + ($i + $xo) * $hgt_value_size + ($j + $yo) * $width * $hgt_value_size);
                                        $val = fread($fp, 2);
                                        $alt = @unpack('n', $val)[1];

                                        $cal = $alt - $altC;
                                        if ($cal > $max && $alt != 0) {
                                            $max = $cal;
                                            $pente = getPente($max, $xo, $yo);
                                        }
                                    }
                                }
                            }
                            fseek($fp2, 20 + ($i) * $hgt_value_size + ($j) * $width * $hgt_value_size);
                            $valw = fwrite($fp2, pack("n", $pente), 2);
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
