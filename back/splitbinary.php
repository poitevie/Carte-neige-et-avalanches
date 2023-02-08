<?php
// VARIABLES GLOBALES
$hgt_value_size = 2;
$hgt_line_records = 3600;
$hgt_step = 1 / $hgt_line_records;
$hgt_step = 1 / $hgt_line_records;
$hgt_line_size = $hgt_value_size * ($hgt_line_records + 1);
$filespath = "hgt/";
$cadremassif = array();

if (!$jsontext = file_get_contents("geojson/massifs.geojson"))
    die("Erreur : N'a pas pu ouvrir le fichier geojson des massifs");
else {
    $json = json_decode($jsontext);
    $features = $json->{"features"};

    for ($i = 0; $i < count($features); $i++) {
        $id = $features[$i]->{"properties"}->{"id"};
        if($id == "09") generatehgt($features[$i], $id);
    }
    $content = json_encode($cadremassif);
    file_put_contents("geojson/cadremassif.json", $content);


}

function getcadremassif($massif)
{
    global $hgt_value_size, $hgt_line_records;
    $coord = $massif->{"geometry"}->{"coordinates"}[0];
    $minlat = $coord[0][1];
    $minlon = $coord[0][0];
    $maxlat = $coord[0][1];
    $maxlon = $coord[0][0];
    for ($i = 1; $i < count($coord); $i++) {
        $lat = $coord[$i][1];
        $lon = $coord[$i][0];
        if ($lat < $minlat) {
            $minlat = $lat;
        } else if ($lat > $maxlat) {
            $maxlat = $lat;
        }
        if ($lon < $minlon) {
            $minlon = $lon;
        } else if ($lon > $maxlon) {
            $maxlon = $lon;
        }
    }
    return ["bg" => [$minlat, $minlon], "hg" => [$maxlat, $minlon], "hd" => [$maxlat, $maxlon], "bd" => [$minlat, $maxlon]];
}

function generatehgt($massif, $idfile)
{
    global $hgt_value_size, $hgt_line_size, $hgt_line_records, $filespath, $hgt_step, $cadremassif;
    $cadre = getcadremassif($massif);
    $coord = $massif->{"geometry"}->{"coordinates"}[0];

    $height = ceil(($cadre["hd"][0] - $cadre["bg"][0]) * $hgt_line_records);
    $width = ceil(($cadre["hd"][1] - $cadre["bg"][1]) * $hgt_line_records);

    $coordxy = [];
    $arr = array("id" => $idfile, "cadre" => $cadre);
    array_push($cadremassif, $arr);

    for ($i = 0; $i < count($coord); $i++) {
        $x = floor((($coord[$i][0] - $cadre["bg"][1])/($cadre["hd"][1] - $cadre["bg"][1]) * $width) / 2) * 2;
        $y = floor((1-(($coord[$i][1] - $cadre["bg"][0])/($cadre["hd"][0] - $cadre["bg"][0]))) * $height);
        array_push($coordxy, [$x, $y]);
    }

    $bgfile = [floor($cadre["bg"][0]), floor($cadre["bg"][1])];
    $hdfile = [floor($cadre["hd"][0]), floor($cadre["hd"][1])];

    $files = array();
    for ($n = $bgfile[0]; $n <= $hdfile[0]; $n++) {
        $filesline = array();
        for ($e = $bgfile[1]; $e <= $hdfile[1]; $e++) {
            $filenumber = getfilenumber($n, $e);
            
            if (file_exists($filespath . $filenumber . '.hgt')) {
                $fileext = '.hgt';
                $hgt_line_records = 3600;
            } else
                return;

            if (!$fp = fopen($filespath . $filenumber . $fileext, "rb"))
                die("Erreur : N'a pas pu ouvrir le fichier d'altitude");
            else {
                array_push($filesline, $fp);
            }
        }
        array_push($files, $filesline);
    }
    $files = array_reverse($files);
    if (!$fp = fopen("hgt/massifs/" . $idfile.".hgt", "w")) {
        die("Erreur : N'a pas pu ouvrir le fichier");
    } else {
        $indexlathg = $hgt_line_records - floor(($cadre["hg"][0] - floor($cadre["hg"][0])) / $hgt_step);
        $indexlonhg = floor(($cadre["hg"][1] - floor($cadre["hg"][1])) / $hgt_step);
        $indexhg = $hgt_line_size * $indexlathg + $hgt_value_size * $indexlonhg;

        fseek($fp, 0);
        $valw = fwrite($fp, pack("n", $width), 2);
        fseek($fp, 2);
        $valw = fwrite($fp, pack("n", $height), 2);
        fseek($fp, 4);
        $valw = fwrite($fp, pack("f", $cadre["bg"][0]), 4);
        fseek($fp, 8);
        $valw = fwrite($fp, pack("f", $cadre["bg"][1]), 4);
        fseek($fp, 12);
        $valw = fwrite($fp, pack("f", $cadre["hd"][0]), 4);
        fseek($fp, 16);
        $valw = fwrite($fp, pack("f", $cadre["hd"][1]), 4);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {

                $filey = intdiv(intdiv($indexhg, $hgt_line_size) + $y, $hgt_line_size/2);
                $filex = intdiv(($indexhg % $hgt_line_size) + ($x * 2), $hgt_line_size);
                $file = $files[$filey][$filex];
                fseek($file, (($indexhg + ($x * 2)) - $filex * $hgt_line_size) + ($y * $hgt_line_size) - $filey * ($hgt_line_size) * ($hgt_line_records + 1));
                $val = fread($file, 2);
                $alt = @unpack('n', $val)[1];

                fseek($fp, 20 + $x * $hgt_value_size + $y * $width * $hgt_value_size);
                if(is_point_in_polygon([$x, $y],$coordxy))
                    $valw = fwrite($fp, pack("n", $alt), 2);
                else
                    $valw = fwrite($fp, pack("n", 0), 2);
                // $valw = fwrite($fp, pack("n", $filey), 2);
            }
        }
    }
}
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
function is_point_in_polygon($point, $polygon_coordinates)
{
    $n = count($polygon_coordinates);
    $inside = false;
    $p1x = $polygon_coordinates[0][0];
    $p1y = $polygon_coordinates[0][1];
    for ($i = 0; $i <= $n; $i++) {
        $p2x = $polygon_coordinates[$i % $n][0];
        $p2y = $polygon_coordinates[$i % $n][1];
        if ($point[1] > min($p1y, $p2y)) {
            if ($point[1] <= max($p1y, $p2y)) {
                if ($point[0] <= max($p1x, $p2x)) {
                    if ($p1y != $p2y) {
                        $x_inters = ($point[1] - $p1y) * ($p2x - $p1x) / ($p2y - $p1y) + $p1x;
                    }
                    if ($p1x == $p2x || $point[0] <= $x_inters) {
                        $inside = !$inside;
                    }
                }
            }
        }
        $p1x = $p2x;
        $p1y = $p2y;
    }
    return $inside;
}

?>