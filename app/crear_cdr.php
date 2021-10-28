<?php 

    require_once("funciones.php");
    $row = array();

    $ruc = isset($argv[1]) ? $argv[1] : "";
    $codsunat = isset($argv[2]) ? $argv[2] : "";
    $serie = isset($argv[3]) ? $argv[3] : "";
    $correlativo = isset($argv[4]) ? $argv[4] : "";
    $nombre_cdr = isset($argv[5]) ? $argv[5] : "";
 
    echo "olaaa";
    $cpe->consulta_cdr($ruc, $codsunat, $serie, $correlativo, $nombre_cdr);

?>