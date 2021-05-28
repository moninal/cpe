<?php 

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

require_once("CPE.php");
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
        CASE WHEN vde.estado = 1 THEN '3' ELSE '1' END estado,
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
        WHERE vde.codsunat='03'  AND vde.documentofecha='" . $fecha . "' AND vde.codemp={$codemp}
        ORDER BY vde.documentofecha ASC";
        // die($sql_detalle_resumen);
        $detalle_resumen = $model->query($sql_detalle_resumen)->fetchAll();
        //   echo "<pre>";
        
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
        if($cpe->getSuccess() == "") {
            $mensaje = $cpe->getCodigoError().": ".$cpe->getErrorDescripcion();
            throw new Exception($mensaje);
        }
        
        $datos_resumen_diario = array();
        $datos_resumen_diario[":codemp"] = $codemp;
        $datos_resumen_diario[":rd_correlativo"] = $correlativo;
        $datos_resumen_diario[":rd_tipo"] = "RN";
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

        while ($row = $documentos->fetch()) {
            
            $res = guardar_documento($row, $cpe);
            
            if($res->errorCode() != '00000') {
                $error = $res->errorInfo();
                $mensaje = "Error al insertar Documento: ".$error[2];
                throw new Exception($mensaje);
            }

            $res = guardar_detalle_resumen($rd_id, $row);
            //echo $model->Sql()."\n";
            if($res->errorCode() != '00000') {
                $error = $res->errorInfo();
                $mensaje = "Error al insertar Detalle Resumen: ".$error[2];
                throw new Exception($mensaje);
            }

            //ACTUALIZAMOS LA RESPECTIVA TABLA DE PAGO, SI EL COMPORBANTE FUE ACEPTADO
            if($cpe->getCode() === 0) {
                
                $res = actualizar_pago($row);

                if($res->errorCode() != '00000') {
                    $error = $res->errorInfo();
                    $mensaje = "Error al modificar: ".$row->tabla." ".$error[2];
                    throw new Exception($mensaje);
                }
            }

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

function validar_resumen_diario($fecha, $codemp) {
    global $model; 

    $result = array();
    try {
    //     $model->getPDO()->beginTransaction();
        $model->query("SELECT * FROM cpe.vista_documentos_electronicos WHERE documentofecha='".$fecha."' AND codsunat='03' AND codemp={$codemp}");
        
        if($model->NumRows() > 0) {
            // VALIDAR CORRELATIVOS FALTANTES
            //BOLETAS

            $sql_series = "SELECT serie FROM cpe.vista_documentos_electronicos WHERE documentofecha='".$fecha."' AND codsunat='03' AND codemp={$codemp}
            GROUP BY serie ORDER BY serie ASC";
            $model->query($sql_series);

            //OBTENER EL ULTIMO COMPROBANTE DE UN DIA ANTERIOR A LA FECHA A GENERAR
            if($model->NumRows() > 0) {
                $series = $model->query($sql_series);
                while ($value = $series->fetch()) {
                    $r = $model->query("SELECT MAX(nrodocumentotri) AS nrodocumentotri 
                    FROM cpe.vista_documentos_electronicos 
                    WHERE documentofecha<'".$fecha."' AND codsunat='03' AND serie='".$value->serie."' AND codemp={$codemp}")->fetch();
            

                    if($r->nrodocumentotri != null) {
                        $ultimo_correlativo_boletas = (int)$r->nrodocumentotri;
                        $ultimo_correlativo_boletas++;
                    } else {
                        $r = $model->query("SELECT MIN(nrodocumentotri) AS nrodocumentotri 
                        FROM cpe.vista_documentos_electronicos 
                        WHERE documentofecha='".$fecha."' AND codsunat='03' AND serie='".$value->serie."' AND codemp={$codemp} ")->fetch();
                
                        $ultimo_correlativo_boletas = (int)$r->nrodocumentotri;
                    
                    }

                    $boletas = $model->query("SELECT * FROM cpe.vista_documentos_electronicos 
                    WHERE documentofecha='".$fecha."' AND codsunat='03' AND serie='".$value->serie."' AND codemp={$codemp} 
                    ORDER BY nrodocumentotri ASC");
                    $correlativo_anterior = "";
                    while ($vb = $boletas->fetch()) {
                        $correlativo = (int)$vb->nrodocumentotri;
                        
                        if($correlativo == $correlativo_anterior) {
                            throw new Exception("Fecha Generacion :".$fecha.", SE REPITE EL CORRELATIVO: ".$vb->serie."-".Formato8Caracteres($correlativo_anterior));

                        }

                        if($correlativo != $ultimo_correlativo_boletas) {

                            throw new Exception("Fecha Generacion :".$fecha.", CORRELATIVO FALTANTE: ".$vb->serie."-".Formato8Caracteres($ultimo_correlativo_boletas));

                        }

                        $correlativo_anterior =  $correlativo;
                        $ultimo_correlativo_boletas++;
                        
                    }
                }
            
            }

            $result["res"] = 1;
            return $result;
        }

        
    } catch(Exception $e) {
      
        $result["res"] = 2;
        $result["mensaje"] = $e->getMessage();
        return $result;
    
    }
}


$fdesde = CodFecha($_REQUEST["fdesde"]);
$fhasta = CodFecha($_REQUEST["fhasta"]);
$fechaCursor = $fdesde ;

$response = array();

$mensajes = "";
while($fechaCursor <= $fhasta) {
    
    $sql_boletas = "SELECT codemp FROM cpe.vista_documentos_electronicos WHERE documentofecha='".$fechaCursor."' AND codsunat='03'
    GROUP BY codemp
    ORDER BY codemp ASC";
    $model->query($sql_boletas);

    if($model->NumRows() > 0) {
        $boletas = $model->query($sql_boletas);
        while($row = $boletas->fetch()) {

            $model->query("SELECT * FROM cpe.resumenes_diarios WHERE rd_fecha_generacion='".$fechaCursor."' AND codemp={$row->codemp} AND rd_tipo='RN' AND rd_ticket <> '' AND rd_ticket IS NOT NULL AND rd_code <> 0");

            if($model->NumRows() <= 0) {
                $response = validar_resumen_diario($fechaCursor, $row->codemp);
        
                if($response["res"] == 2) {
                    // throw new Exception($response["mensaje"]);
                    $mensajes .= $response["mensaje"]."\n";
                    continue;
                }

                $response = generar_resumen_diario($fechaCursor, $row->codemp);

                if($response["res"] == 2) {
                    // throw new Exception($response["mensaje"]);
                    $mensajes .= $response["mensaje"]."\n";
                    
                }
            } else {
                //$mensajes .= "YA EXISTE UN RESUMEN DIARO DE FECHA: ".$fechaCursor."\n";
            }

        }
    } else {
        //$mensajes .= "NO EXISTEN BOLETAS EN LA FECHA: ".$fechaCursor."\n";
    }
    
    $fechaTime = strtotime($fechaCursor."+ 1 days");
    $fechaCursor = date("Y-m-d", $fechaTime);
}

$response["mensaje"] = $mensajes;
$model->liberar();
echo json_encode($response);

    

    

?>