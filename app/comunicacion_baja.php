<?php 

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

// require_once("CPE.php");
require_once("funciones.php");

function generar_comunicacion_baja($fecha, $codemp) {
    global $model, $cpe; 
    
    $response = array();
    try {
        $model->getPDO()->beginTransaction();

        $sql_correlativo = "SELECT COALESCE((MAX(cb_correlativo) + 1), 1) AS correlativo FROM cpe.comunicacion_baja WHERE cb_fecha='" . date("Y-m-d"). "' AND codemp={$codemp}";
        $result_correlativo = $model->query($sql_correlativo)->fetch();
        $correlativo = $result_correlativo->correlativo;

        $comunicacion = array();
        $comunicacion["fecha_comunicacion"] = date("Y-m-d");
        $comunicacion["fecha_generacion"] = $fecha;
        $comunicacion["correlativo"] = (string)$correlativo;
        $comunicacion = (object)$comunicacion;
            
        
        $sql_detalle_baja = "SELECT 
        vde.codsunat AS codtipodocumento,
        vde.serie,
        vde.nrodocumentotri AS correlativo,
        CASE WHEN vde.estado = 1 THEN 'I' ELSE 'A' END estado,
        vde.estado_cpe,
        'ANULADO' AS motivo_baja,
        vde.codemp,
        vde.codsuc,
        vde.nroinscripcion,
        vde.codciclo,
        vde.idmovimiento,
        vde.tabla,
        vde.documento_id
        FROM cpe.vista_documentos_electronicos AS vde
        LEFT JOIN cpe.detalle_baja AS db ON(db.documento_id=vde.documento_id)
        LEFT JOIN cpe.comunicacion_baja AS cb ON(cb.cb_id=db.cb_id)
        WHERE vde.codsunat='01'  AND vde.documentofecha='" . $fecha . "' AND vde.codemp={$codemp} AND vde.estado=1 /*SOLO ANULADOS*/  
        /*AND CASE WHEN cb.cb_id IS NULL THEN vde.estado_documento='A' ELSE cb.cb_id IS NULL END*/
        AND CASE WHEN db.documento_id IS NOT NULL THEN vde.estado_documento = 'A' AND vde.documento_estado<>'I' ELSE (CASE WHEN vde.documento_id IS NOT NULL THEN vde.estado_documento = 'A' AND vde.documento_estado='A' ELSE vde.documento_id IS NULL END) END
        ORDER BY vde.documentofecha ASC";
        //die($sql_detalle_baja);
        $detalle_comunicacion = $model->query($sql_detalle_baja)->fetchAll();

        

        if(count($detalle_comunicacion) <= 0) {
            $mensaje = "YA SE EMITIERON LAS BAJAS DE LAS FACTURAS ANULADAS DE LA FECHA: ".$fecha;
            //$mensaje = "PRIMERO TIENE QUE : ".$fecha;
            throw new Exception($mensaje);
        }

        foreach ($detalle_comunicacion as $key => $value) {

            if($value->estado_cpe != "ACEPTADO") {
                $mensaje = "PRIMERO DEBE ENVIAR LA FACTURA: ".$value->serie."-".$value->correlativo." A SUNAT Y DEBE SER ACEPTADO PARA LUEGO DARLE DE BAJA";
                throw new Exception($mensaje);
            } 
        }


        $cpe->comunicacion_baja($comunicacion, $detalle_comunicacion);
        $cpe->enviar_sunat();
        if($cpe->getCode() !== 0) {

            $mensaje = "Error en comunicacion de la fecha: ".$fecha." error: ".$cpe->getCodigoError().", ".$cpe->getErrorDescripcion();
            throw new Exception($mensaje);
        }

        $datos_update = array();
        $datos_update[":estado"] = "I";

        $where = array();
        $where[":codemp"] = $codemp;
        $where[":cb_fecha_generacion"] = $fecha;
        $model->modificar("cpe.comunicacion_baja", $datos_update, $where);
        
        if($model->errorCode() != '00000') {
            $error = $model->errorInfo();
            $mensaje = "Error al modificar comunicacion baja: ".$error[2];
            throw new Exception($mensaje);
        }

        $datos_comunicacion_baja = array();
        $datos_comunicacion_baja[":codemp"] = $codemp;
        $datos_comunicacion_baja[":cb_correlativo"] = $correlativo;
        $datos_comunicacion_baja[":cb_fecha"] = date("Y-m-d");
        $datos_comunicacion_baja[":cb_fecha_generacion"] = $fecha;
        $datos_comunicacion_baja[":cb_ticket"] = $cpe->getTicket();
        //$datos_comunicacion_baja[":cb_success"] = $cpe->getSuccess();
        $datos_comunicacion_baja[":cb_cdr_response"] = $cpe->getCdrResponse();
        // $datos_comunicacion_baja[":cb_codigo_error"] = $cpe->getCodigoError();
        // $datos_comunicacion_baja[":cb_error_descripcion"] = $cpe->getErrorDescripcion();
        $datos_comunicacion_baja[":cb_code"] = $cpe->getCode();
        $datos_comunicacion_baja[":cb_observaciones"] = $cpe->getObservaciones();
        //$datos_comunicacion_baja[":cb_forma_comprobacion"] = $cpe->getFormaComprobacion();
        $datos_comunicacion_baja[":cb_nombre_xml"] = $cpe->getNombreXml();
        $datos_comunicacion_baja[":cb_nombre_cdr"] = $cpe->getNombreCdr();
        $datos_comunicacion_baja[":cb_nombre_documento"] = $cpe->getNombreDocumento();

        $model->insertar("cpe.comunicacion_baja", $datos_comunicacion_baja);
        $cb_id = $model->lastInsertId();
        if($model->errorCode() != '00000') {
            $error = $model->errorInfo();
            $mensaje = "Error al insertar comunicacion baja: ".$error[2];
            throw new Exception($mensaje);
        }


        $documentos = $model->query($sql_detalle_baja);
        // echo $model->NumRows();
        while ($row = $documentos->fetch()) {
            

            $res = guardar_documento($row, $cpe, "La Factura número " . $row->serie."-".$row->correlativo . ", ha sido dado de baja");
            $documento_id = $res->lastInsertId();
            if($res->errorCode() != '00000') {
                $error = $res->errorInfo();
                $mensaje = "Error al insertar Documento: ".$error[2];
                throw new Exception($mensaje);
            }

            $datos_detalle_baja = array();
            
            $datos_detalle_baja[":cb_id"] = $cb_id;
            $datos_detalle_baja[":documento_id"] = $documento_id;


            $model->insertar("cpe.detalle_baja", $datos_detalle_baja);
            //echo $model->Sql()."\n";
            if($model->errorCode() != '00000') {
                $error = $model->errorInfo();
                $mensaje = "Error al insertar detalle comunicacion baja: ".$error[2];
                throw new Exception($mensaje);
            }

            //ACTUALIZAMOS LA RESPECTIVA TABLA DE PAGO, SI EL COMPORBANTE FUE ACEPTADO
            // if($cpe->getCode() === 0) {
                $res = actualizar_pago($row);

                if($res->errorCode() != '00000') {
                    $error = $res->errorInfo();
                    $mensaje = "Error al modificar: ".$row->tabla." ".$error[2];
                    throw new Exception($mensaje);
                }
            // }

        }

        $response["res"] = 1;
        $model->getPDO()->commit();
        return $response;
    } catch(Exception $e) {
        $model->getPDO()->rollBack();
        $response["res"] = 2;
        $response["mensaje"] = $e->getMessage();
        return $response;
    
    }
}




$fdesde = CodFecha($_REQUEST["fdesde"]);
$fhasta = CodFecha($_REQUEST["fhasta"]);
$fechaCursor = $fdesde ;

$response = array();

$mensajes = "";
while($fechaCursor <= $fhasta) {
    
    $sql_facturas = "SELECT codemp FROM cpe.vista_documentos_electronicos WHERE documentofecha='".$fechaCursor."' AND codsunat='01'
    GROUP BY codemp
    ORDER BY codemp ASC";
    $model->query($sql_facturas);

    if($model->NumRows() > 0) {
        $facturas = $model->query($sql_facturas);
        while($row = $facturas->fetch()) {

            $response = generar_comunicacion_baja($fechaCursor, $row->codemp);

            if($response["res"] == 2) {
                // throw new Exception($response["mensaje"]);
                $mensajes .= $response["mensaje"]."\n";
            }
            
    
        }
    } else {
        //$mensajes .= "NO EXISTE FACTURAS ANULADAS EN LA FECHA: ".$fechaCursor."\n";
    }
    
    $fechaTime = strtotime($fechaCursor."+ 1 days");
    $fechaCursor = date("Y-m-d", $fechaTime);
}

$response["mensaje"] = $mensajes;
$model->liberar();
echo json_encode($response);

    

    

?>