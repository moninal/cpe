<?php 

    require_once("funciones.php");
    
    $_REQUEST["id"] = isset($argv[1]) ? $argv[1] : "";
    $_REQUEST["codemp"] = isset($argv[2]) ? $argv[2] : "";
    $_REQUEST["codsuc"] = isset($argv[3]) ? $argv[3] : "";
    $_REQUEST["nroinscripcion"] = isset($argv[4]) ? $argv[4] : "";
    $_REQUEST["codciclo"] = isset($argv[5]) ? $argv[5] : "";
    $_REQUEST["tabla"] = isset($argv[6]) ? $argv[6] : "";
    $pdf = crear_pdf();
    // print_r($pdf); exit;
    $pdf = $pdf->output();
    file_put_contents(dirname(__DIR__)."/PDF/".nombre_documento() . ".pdf", $pdf);
   

?>