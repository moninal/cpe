<?php 

require_once("funciones.php");

if(empty($_GET["xml"]) || $_GET["xml"] == NULL ) {
    $nombre_xml = nombre_documento().".xml";
} else {
    $nombre_xml = $_GET["xml"];
}


header("Content-disposition: attachment; filename=".$nombre_xml);
header("Content-type: application/xml");


if(empty($nombre_xml) || !file_exists("../XML/".$nombre_xml)) {
    $_REQUEST["idmovimiento"] = $_REQUEST["id"];
    $row = (object) $_REQUEST;
    crear_xml($row);

}

readfile("../XML/".$nombre_xml);
?>