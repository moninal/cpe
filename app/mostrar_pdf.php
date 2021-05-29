<?php 
    require_once("funciones.php");
    $pdf = crear_pdf();
    $pdf->stream(nombre_documento() . ".pdf", array("Attachment" => false));
?>