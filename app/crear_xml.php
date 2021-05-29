<?php 

    require_once("funciones.php");
    $row = array();

    $row["idmovimiento"] = isset($argv[1]) ? $argv[1] : "";
    $row["codemp"] = isset($argv[2]) ? $argv[2] : "";
    $row["codsuc"] = isset($argv[3]) ? $argv[3] : "";
    $row["nroinscripcion"] = isset($argv[4]) ? $argv[4] : "";
    $row["codciclo"] = isset($argv[5]) ? $argv[5] : "";
    $row["tabla"] = isset($argv[6]) ? $argv[6] : "";
    $row = (object) $row;
    $response = crear_xml($row);
    echo json_encode($response);

?>