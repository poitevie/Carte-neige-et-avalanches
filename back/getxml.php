<?php
$xml = (array)simplexml_load_string(file_get_contents("http://api.meteofrance.com/files/mountain/bulletins/BRA40.xml"));

if(isset($xml["CARTOUCHERISQUE"])) {
    if(isset($xml["CARTOUCHERISQUE"]->{"RISQUE"})) {
        $risque = $xml["CARTOUCHERISQUE"]->{"RISQUE"};
        var_dump($risque);
    }
}
?>