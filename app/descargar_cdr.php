<?php 

require_once("funciones.php");

if(empty($_GET["cdr"]) || $_GET["cdr"] == NULL ) {
    $nombre_cdr = "R-".nombre_documento().".zip";
} else {
    $nombre_cdr = $_GET["cdr"];
}


$idmovimiento = isset($_REQUEST["id"]) ? $_REQUEST["id"] : "0";
$codemp = isset($_REQUEST["codemp"]) ? $_REQUEST["codemp"] : "0";
$codsuc = isset($_REQUEST["codsuc"]) ? $_REQUEST["codsuc"] : "0";
$nroinscripcion = isset($_REQUEST["nroinscripcion"]) ? $_REQUEST["nroinscripcion"] : "0";
$codciclo = isset($_REQUEST["codciclo"]) ? $_REQUEST["codciclo"] : "0";
$tabla = isset($_REQUEST["tabla"]) ? $_REQUEST["tabla"] : "0";


$sql_empresa = "SELECT * FROM admin.empresas";
$empresa = $model->query($sql_empresa)->fetch();

$sql_comprobante = "SELECT 
CASE WHEN documento_nombre IS NULL OR documento_nombre = '' THEN '".$empresa->ruc."' || '-' || codsunat || '-' || serie || '-' || nrodocumentotri ELSE documento_nombre END AS documento_nombre,
CASE WHEN codsunat = '01' THEN 'Factura' ELSE 'Boleta de Venta' END AS tipodoc_descripcion,
documento_nombre_xml, documento_nombre_cdr,
razonsocial,
serie,
nrodocumentotri,
codsunat
FROM cpe.vista_documentos_electronicos WHERE idmovimiento={$idmovimiento} AND codemp={$codemp} AND codsuc={$codsuc} AND nroinscripcion={$nroinscripcion} AND codciclo={$codciclo} AND tabla='{$tabla}'";
// die($sql_comprobante);
$comprobante = $model->query($sql_comprobante)->fetch();



header("Content-disposition: attachment; filename=".$nombre_cdr);
header("Content-type: application/cdr");


if(empty($nombre_cdr) || !file_exists("../CDR/".$nombre_cdr)) {
    if($comprobante->codsunat != NULL && $comprobante->codsunat == "01") {
        echo "olaaaa";
        $cpe->consulta_cdr($empresa->ruc, $comprobante->codsunat, $comprobante->serie, $comprobante->nrodocumentotri, $nombre_cdr);
    }
    

}

readfile("../CDR/".$nombre_cdr);


?>