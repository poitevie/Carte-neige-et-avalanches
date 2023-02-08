<?php

// EXTRAIT L'ALTITUDE DEPUIS SRTM3, V2 FILLED
function getalt($latitude, $longitude)
	{
	settype($latitude, "float");
	settype($longitude, "float");
	
	// correction $longitude pas dans l'intervalle [-180, 180[
	$longitude = fmod($longitude,360); if($longitude>=180) $longitude -= 360; if($longitude<-180) $longitude += 360;

	$filespath = "hgt/";
	$filenumber = getfilenumber($latitude, $longitude);
	
	if (file_exists($filespath.$filenumber.'.hgt')) { // SRTM3 V2
		$fileext= '.hgt';
		$hgt_line_records = 3600;
		}
	else return;

	$hgt_value_size = 2; // codage sur 2 octets
	$hgt_step= 1/$hgt_line_records;
	$hgt_line_size = $hgt_value_size*($hgt_line_records+1);

	// bas gauche
	$indexlatbg = $hgt_line_records - floor(($latitude - floor($latitude)) / $hgt_step);
	$indexlonbg = floor(($longitude - floor($longitude)) / $hgt_step);
	$indexbg=$hgt_line_size*$indexlatbg + $hgt_value_size*$indexlonbg;
	
	// haut gauche
	$indexhg = $indexbg - $hgt_line_size;
	
	// bas droite
	$indexbd = $indexbg + $hgt_value_size;
	
	// haut droite
	$indexhd = $indexhg + $hgt_value_size;

	// lecture des altitudes dans le fichier
	if (!$fp = fopen($filespath.$filenumber.$fileext,"rb")) die("Erreur : N'a pas pu ouvrir le fichier d'altitude");
	else
		{
		fseek($fp, $indexbg);
		$val = fread($fp, 2);
		$bg = @unpack('n', $val);
		if ($bg[1]=="" OR $bg[1]>8800) $bg[1]=0;

		fseek($fp, $indexbd);
		$val = fread($fp, 2);
		$bd = @unpack('n', $val);
		if ($bd[1]=="" OR $bd[1]>8800) $bd[1]=0;

		fseek($fp, $indexhd);
		$val = fread($fp, 2);
		$hd = @unpack('n', $val);
		if ($hd[1]=="" OR $hd[1]>8800) $hd[1]=0;

		fseek($fp, $indexhg);
		$val = fread($fp, 2);
		$hg = @unpack('n', $val);
		if ($hg[1]=="" OR $hg[1]>8800) $hg[1]=0;

		$ratio_haut= ($latitude - floor($latitude)) / $hgt_step - floor(($latitude - floor($latitude)) / $hgt_step);
		$ratio_droite = ($longitude - floor($longitude)) / $hgt_step - floor(($longitude - floor($longitude)) / $hgt_step);
		
		if($hg[1] && $bg[1]) $alt_gauche = $hg[1]*$ratio_haut + $bg[1]*(1 - $ratio_haut);
		else if($hg[1]) $alt_gauche = $hg[1];
		else if($bg[1]) $alt_gauche = $bg[1];
		else $alt_gauche = 0;
		
		if($hd[1] && $bd[1]) $alt_droite = $hd[1]*$ratio_haut + $bd[1]*(1 - $ratio_haut);
		else if($hd[1]) $alt_droite = $hd[1];
		else if($bd[1]) $alt_droite = $bd[1];
		else $alt_droite = 0;
		
		if($alt_gauche && $alt_droite) $alt = round($alt_droite*$ratio_droite + $alt_gauche*(1 - $ratio_droite),1);
		else if($alt_gauche) $alt = round($alt_gauche,2);
		else if($alt_droite) $alt = round($alt_droite,2);
		else $alt = 0;
		
		return $alt;
		}
	}

function getfilenumber ($latitude, $longitude)
	{
	$lat = abs(floor($latitude));
	$lon = abs(floor($longitude));
	$filenumber = "";
	if ($latitude>=0) $filenumber .= "N";
		else $filenumber .= "S";
	if (strlen($lat)==1) $filenumber .="0";
	$filenumber .= $lat;

	if ($longitude>=0) $filenumber .= "E";
		else $filenumber .= "W";
	if (strlen($lon)==1) $filenumber .="00";
	else if (strlen($lon)==2) $filenumber .="0";
	$filenumber .= $lon;

	return $filenumber;
	}
?>
