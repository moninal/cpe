<?php 

// header('Access-Control-Allow-Origin: *');
// header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
// header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

// require_once("CPE.php");
require_once("funciones.php");

function generar_resumen_diario($fecha, $codemp) {
    global $model, $cpe; 
    
    $response = array();
    try {
        $model->getPDO()->beginTransaction();
        $sql_correlativo = "SELECT COALESCE((MAX(rd_correlativo) + 1), 1) AS correlativo FROM cpe.resumenes_diarios WHERE rd_fecha='" . date("Y-m-d"). "' AND codemp={$codemp}";
        $result_correlativo = $model->query($sql_correlativo)->fetch();
        $correlativo = $result_correlativo->correlativo;

        $resumen = array();
        $resumen["fecha_resumen"] = date("Y-m-d");
        $resumen["fecha_generacion"] = $fecha;
        $resumen["correlativo"] = (string)$correlativo;
        $resumen = (object)$resumen;
            
        $sql_detalle_resumen = "SELECT 
        vde.codsunat AS codtipodocumento,
        vde.serie,
        vde.nrodocumentotri AS correlativo,
        CASE WHEN vde.estado = 1 THEN 'I' ELSE 'A' END estado,
        CASE WHEN vde.estado = 1 THEN '3' ELSE '1' END dr_estado,
        vde.tdi_id AS codtipodocumentoidentidad,
        vde.cliente_numero_documento AS nrodocumentoidentidad,
        vde.imptotal AS total,
        vde.subtotal AS valor_venta,
        vde.igv,
        vde.codemp,
        vde.codsuc,
        vde.nroinscripcion,
        vde.codciclo,
        vde.idmovimiento,
        vde.tabla,
        vde.nrodocumento,
        vde.direccion,
        CASE WHEN p.valor::FLOAT > 0 THEN 'S' ELSE 'N' END AS  igv_status
        FROM cpe.vista_documentos_electronicos AS vde
        LEFT JOIN reglasnegocio.parame AS p ON(p.codsuc=vde.codsuc AND p.tippar = 'IMPIGV')
        LEFT JOIN cpe.detalle_resumen AS dr ON(dr.documento_id=vde.documento_id)
        LEFT JOIN cpe.resumenes_diarios AS rd ON(rd.rd_id=dr.rd_id)
        WHERE vde.codsunat='03' AND vde.documentofecha='" . $fecha . "' AND vde.codemp={$codemp} AND vde.estado=1 /*SOLO ANULADOS*/ 
        AND CASE WHEN dr.documento_id IS NOT NULL THEN vde.estado_documento = 'A' AND vde.documento_estado<>'I' AND dr.dr_estado<>'3' ELSE dr.documento_id IS NULL END
        ORDER BY vde.documentofecha ASC";
        
        $detalle_resumen = $model->query($sql_detalle_resumen)->fetchAll();
        //   echo "<pre>";
        
        if(count($detalle_resumen) <= 0) {
            $mensaje = "YA SE EMITIERON LAS BAJAS DE LAS BOLETAS ANULADAS DE LA FECHA: ".$fecha;
            throw new Exception($mensaje);
        }

        foreach ($detalle_resumen as $key => $value) {

            if($value->nroinscripcion == "0") {
                $nrodocumento = substr($value->nrodocumento, -8, 8);
                if(is_numeric($nrodocumento)) {
                    $detalle_resumen[$key]->nrodocumentoidentidad = $nrodocumento;
                    $detalle_resumen[$key]->codtipodocumentoidentidad = "1";
                } else {
                    $detalle_resumen[$key]->nrodocumentoidentidad = "00000000";
                    $detalle_resumen[$key]->codtipodocumentoidentidad = "0";
                }
            } 
        }


       


        $cpe->resumen_diario($resumen, $detalle_resumen);
        $cpe->enviar_sunat();
        if($cpe->getCode() !== 0) {

            $code = intval($cpe->getCodigoError());

            if($code != 98 && $code != 99) {
               

                $mensaje = "Error en resumen de baja de la fecha: ".$fecha." error: ".$cpe->getCodigoError().", ".$cpe->getErrorDescripcion();
                throw new Exception($mensaje);
            } else {
                $cpe->setCode($code);
                $cpe->setObservaciones($cpe->getErrorDescripcion());
            }

        }
        
        $datos_resumen_diario = array();
        $datos_resumen_diario[":codemp"] = $codemp;
        $datos_resumen_diario[":rd_correlativo"] = $correlativo;
        $datos_resumen_diario[":rd_tipo"] = "RB";
        $datos_resumen_diario[":rd_fecha"] = date("Y-m-d");
        $datos_resumen_diario[":rd_fecha_generacion"] = $fecha;

        $res = guardar_resumen($datos_resumen_diario, $cpe);

        $rd_id = $res->lastInsertId();
        if($res->errorCode() != '00000') {
            $error = $res->errorInfo();
            $mensaje = "Error al insertar Resumen Diario: ".$error[2];
            throw new Exception($mensaje);
        }


        $documentos = $model->query($sql_detalle_resumen);
        // echo $model->NumRows();
        while ($row = $documentos->fetch()) {
            if($cpe->getCode() === 0 ) {
                $res = guardar_documento($row, $cpe, "La Boleta número " . $row->serie."-".$row->correlativo . ", ha sido dado de baja");
            } else {
                $res = guardar_documento($row, $cpe, "");
            }

          
            
            $documento_id = $res->lastInsertId();

            if($res->errorCode() != '00000') {
                $error = $res->errorInfo();
                $mensaje = "Error al insertar Documento: ".$error[2];
                throw new Exception($mensaje);
            }


            $res = guardar_detalle_resumen($rd_id, $documento_id, $row->dr_estado);
            //echo $model->Sql()."\n";
            if($res->errorCode() != '00000') {
                $error = $res->errorInfo();
                $mensaje = "Error al insertar Detalle Resumen: ".$error[2];
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


// $fdesde = CodFecha($_REQUEST["fdesde"]);
// $fhasta = CodFecha($_REQUEST["fhasta"]);

$fdesde = CodFecha(isset($argv[1]) ? $argv[1] : "");
$fhasta = CodFecha(isset($argv[2]) ? $argv[2] : "");
$fechaCursor = $fdesde ;

$response = array();

$mensajes = "";
while($fechaCursor <= $fhasta) {
    
    $sql_boletas = "SELECT codemp FROM cpe.vista_documentos_electronicos WHERE documentofecha='".$fechaCursor."' AND codsunat='03' AND estado=1
    GROUP BY codemp
    ORDER BY codemp ASC";
    $model->query($sql_boletas);

    if($model->NumRows() > 0) {
        $boletas = $model->query($sql_boletas);
        while($row = $boletas->fetch()) {

            $model->query("SELECT * FROM cpe.resumenes_diarios WHERE rd_fecha_generacion='".$fechaCursor."' AND codemp={$row->codemp} AND rd_tipo='RN' AND rd_code = 0"); // tiene que existir primero un resumen normal de ese dia ya aceptado
            if($model->NumRows() > 0) {
            //if(true) {
             

                $response = generar_resumen_diario($fechaCursor, $row->codemp);

                if($response["res"] == 2) {
                    // throw new Exception($response["mensaje"]);
                    $mensajes .= $response["mensaje"]."\n";
                
                }
               
            } else {
                $mensajes .= "AUN PUEDE GENERAR UN RESUMEN DIARIO NORMAL O AUN NO HA SIDO ACEPTADO EL RESUMEN DIARIO DE LA FECHA: ".$fechaCursor."\n";
            }
        }
    } else {
       // $mensajes .= "NO EXISTE BOLETAS ANULADAS EN LA FECHA: ".$fechaCursor."\n";
    }
    
    $fechaTime = strtotime($fechaCursor."+ 1 days");
    $fechaCursor = date("Y-m-d", $fechaTime);
}

$response["mensaje"] = $mensajes;

$model->liberar();
echo json_encode($response);


    

?>