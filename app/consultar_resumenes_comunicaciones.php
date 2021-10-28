<?php 
    
require_once("funciones.php");

$sql = "SELECT * FROM cpe.resumenes_diarios WHERE rd_code IN(98, 99)";
$resumenes = $model->query($sql);

while($value = $resumenes->fetch()) {
    $cpe->consultar_resumen($value->rd_ticket, $value->rd_nombre_documento);
    if($cpe->getCode() === 0) {

        $where = array();
        $where[":rd_id"] = $value->rd_id;

        
        $datos_update = array();
        $datos_update[":rd_code"] = $cpe->getCode();
        $datos_update[":rd_cdr_response"] = $cpe->getCdrResponse();
        $datos_update[":rd_observaciones"] = $cpe->getObservaciones();
        $datos_update[":rd_nombre_cdr"] = $cpe->getNombreCdr();

        $model->modificar("cpe.resumenes_diarios", $datos_update, $where);


        $sql_detalle = "SELECT * FROM cpe.detalle_resumen AS dr 
        INNER JOIN cpe.vista_documentos_electronicos AS v ON(dr.documento_id=v.documento_id)
        WHERE dr.rd_id={$value->rd_id}";
        $detalle = $model->query($sql);

        while($row = $detalle->fetch()) {
            
          
            $where = array();
            $where[":documento_id"] = $row->documento_id;

            
            $datos_update = array();
            $datos_update[":documento_code"] = $cpe->getCode();
            if($row->dr_estado == "1") {

                $datos_update[":documento_cdr_response"] = "La Boleta número " . $row->serie."-".$row->nrodocumentotri . ", ha sido aceptada";
            } else {
                $datos_update[":documento_cdr_response"] = "La Boleta número " . $row->serie."-".$row->nrodocumentotri . ", ha sido dado de baja";
            }
            $datos_update[":documento_observaciones"] = $cpe->getObservaciones();
 
            $model->modificar("cpe.documentos", $datos_update, $where);

           

        }

    }
}



$sql = "SELECT * FROM cpe.comunicacion_baja WHERE cb_code IN(98, 99)";
$comunicaciones = $model->query($sql);

while($value = $comunicaciones->fetch()) {
    $cpe->consultar_resumen($value->cb_ticket, $value->cb_nombre_documento);
    if($cpe->getCode() === 0) {

        $where = array();
        $where[":cb_id"] = $value->cb_id;

        
        $datos_update = array();
        $datos_update[":cb_code"] = $cpe->getCode();
        $datos_update[":cb_cdr_response"] = $cpe->getCdrResponse();
        $datos_update[":cb_observaciones"] = $cpe->getObservaciones();
        $datos_update[":cb_nombre_cdr"] = $cpe->getNombreCdr();

        $model->modificar("cpe.resumenes_diarios", $datos_update, $where);


        $sql_detalle = "SELECT * FROM cpe.detalle_baja AS db 
        INNER JOIN cpe.vista_documentos_electronicos AS v ON(db.documento_id=v.documento_id)
        WHERE db.cb_id={$value->cb_id}";
        $detalle = $model->query($sql);

        while($row = $detalle->fetch()) {
            
          
            $where = array();
            $where[":documento_id"] = $row->documento_id;

            
            $datos_update = array();
            $datos_update[":documento_code"] = $cpe->getCode();
            $datos_update[":documento_cdr_response"] = "La Factura número " . $row->serie."-".$row->nrodocumentotri . ", ha sido dado de baja";
            $datos_update[":documento_observaciones"] = $cpe->getObservaciones();
 
            $model->modificar("cpe.documentos", $datos_update, $where);

           

        }

    }
}


?>