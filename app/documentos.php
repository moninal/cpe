<?php 

// header('Access-Control-Allow-Origin: *');
// header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
// header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

// require_once("CPE.php");

require_once("funciones.php");

function generar_comprobante($row) {
    global $model, $cpe; 
    $response = array();

    try {
        //$model->getPDO()->beginTransaction();
   
        
        $tabla          = $row->tabla;
        $codemp         = $row->codemp;
        $codsuc         = $row->codsuc;
        $nroinscripcion = $row->nroinscripcion;
        $codciclo       = $row->codciclo;
        $id             = $row->idmovimiento;

        $join = "";
        if ($tabla == "cobranza.cabpagos") {
            $join = " INNER JOIN cobranza.detpagos AS d ON(d.codemp=vde.codemp AND d.codsuc=vde.codsuc AND d.nroinscripcion=vde.nroinscripcion AND d.nropago=vde.idmovimiento) 
            INNER JOIN facturacion.conceptos AS c ON(c.codemp=d.codemp AND c.codsuc=d.codsuc AND c.codconcepto=d.codconcepto)";
        }

        if ($tabla == "cobranza.cabprepagos") {
            $join = " INNER JOIN cobranza.detprepagos AS d ON(d.codemp=vde.codemp AND d.codsuc=vde.codsuc AND d.nroinscripcion=vde.nroinscripcion AND d.nroprepago=vde.idmovimiento)
            INNER JOIN facturacion.conceptos AS c ON(c.codemp=d.codemp AND c.codsuc=d.codsuc AND c.codconcepto=d.codconcepto) ";
        }

        $sql_igv = "SELECT * FROM reglasnegocio.parame WHERE tippar = 'IMPIGV' AND codsuc = {$codsuc}";
       
        $igv = $model->query($sql_igv)->fetch();
        // print_r($igv);
        if($igv->valor > 0) {
            $igv_status = "S";
            $codtipoigv = "10";
        } else {
            $igv_status = "N";
            $codtipoigv = "20";
        }

        $sql_cliente = "SELECT 
        vde.nroinscripcion,
        vde.tdi_id AS codtipodocumentoidentidad,
        CASE WHEN vde.nroinscripcion=0 THEN vde.nrodocumento ELSE vde.cliente_numero_documento END AS nrodocumentoidentidad,
        vde.razonsocial AS razon_social,
        CASE WHEN vde.nroinscripcion=0 THEN  vde.direccion ELSE vde.direcciondistribucion END AS direccion,
        vde.documentofecha,
        vde.serie || '-' || vde.nrodocumentotri AS comprobante,
        vde.codsunat

        FROM cpe.vista_documentos_electronicos AS vde
        WHERE vde.tabla='{$tabla}' AND vde.codemp={$codemp} AND vde.codsuc={$codsuc} AND vde.nroinscripcion={$nroinscripcion} AND vde.codciclo={$codciclo} AND vde.idmovimiento={$id}";
   
        // die($sql_cliente);
        $cliente = $model->query($sql_cliente)->fetch();

        if($cliente->nroinscripcion == "0") {
            if($cliente->codsunat == "01") {
                $cliente->nrodocumentoidentidad = substr($cliente->nrodocumentoidentidad, -11, 11);
                $cliente->codtipodocumentoidentidad = '6';
                // if(!is_numeric($cliente->nrodocumentoidentidad) || strlen($cliente->nrodocumentoidentidad) != 11) {
                //     $mensaje = "RUC INVÁLIDO de la fecha: ".$cliente->documentofecha." del comprobante: ".$cliente->comprobante;
                //     throw new Exception($mensaje);
                // }
            } else {
                $cliente->nrodocumentoidentidad  = substr($cliente->nrodocumentoidentidad, -8, 8);
                $cliente->codtipodocumentoidentidad = '1';

                if(!is_numeric($cliente->nrodocumentoidentidad) || strlen($cliente->nrodocumentoidentidad) != 8) {
                    $cliente->nrodocumentoidentidad = '00000000';
                    $cliente->codtipodocumentoidentidad = '0';
                }
            }
            
        }

        if($cliente->codtipodocumentoidentidad == "6" && (!is_numeric($cliente->nrodocumentoidentidad) || strlen($cliente->nrodocumentoidentidad) != 11)) {
            $mensaje = "RUC INVÁLIDO de la fecha: ".$cliente->documentofecha." del comprobante: ".$cliente->comprobante;
            throw new Exception($mensaje);
        }

        if($cliente->codtipodocumentoidentidad == "1" && (!is_numeric($cliente->nrodocumentoidentidad) || strlen($cliente->nrodocumentoidentidad) != 8)) {
            $mensaje = "DNI INVÁLIDO de la fecha: ".$cliente->documentofecha." del comprobante: ".$cliente->comprobante;
            throw new Exception($mensaje);
        }
    
        // print_r($cliente); exit;
        $cpe->setCliente($cliente);
        

        $sql_comprobante = "SELECT 
        vde.codsunat AS codtipodocumento,
        vde.serie,
        vde.nrodocumentotri AS correlativo,
        vde.imptotal AS total,
        vde.subtotal AS valor_venta,
        vde.igv,
        vde.documentofecha AS fecha,
        'PEN' AS codmoneda,
        vde.redondeo * -1 AS redondeo,
        (vde.subtotal + vde.igv) AS subtotal,
    
        vde.igv AS total_impuestos,
        /*'".$igv_status."' AS igv_status,*/
        CASE WHEN vde.igv > 0 THEN 'S' ELSE 'N' END AS igv_status,
        ".$igv->valor." AS porcentaje_igv,
        'N' AS icbper_status,
        CASE WHEN vde.estado = 1 AND vde.codsunat='03' THEN 'I' ELSE 'A' END estado
        FROM cpe.vista_documentos_electronicos AS vde
        WHERE vde.tabla='{$tabla}' AND vde.codemp={$codemp} AND vde.codsuc={$codsuc} AND vde.nroinscripcion={$nroinscripcion} AND vde.codciclo={$codciclo} AND vde.idmovimiento={$id}";
        // die($sql_comprobante);

        $comprobante = $model->query($sql_comprobante)->fetch();
        // echo $comprobante->serie;
        // var_dump(strpos($comprobante->serie, "F"));
        // exit;
        if($comprobante->codtipodocumento == '01') {
            // $comprobante->serie[0] = "F";
            // var_dump(strpos($comprobante->serie, "F"));
            // exit;
            if(strpos($comprobante->serie, "F") === false) {
                $mensaje = "Error en la fecha: ".$cliente->documentofecha." del comprobante: ".$cliente->comprobante." error: La serie debe ser del siguiente formato FXXX";
                throw new Exception($mensaje);
            }

            if($cliente->codtipodocumentoidentidad != 6) {
                $mensaje = "Error en la fecha: ".$cliente->documentofecha." del comprobante: ".$cliente->comprobante." error: el tipo de documento de identidad del cliente debe ser RUC porque el comprobante es una factura";
                throw new Exception($mensaje);
            }
            
            

        }

        if($comprobante->codtipodocumento == '03') {
            // $comprobante->serie[0] = "B";
            if(strpos($comprobante->serie, "B") === false) {
                $mensaje = "Error en la fecha: ".$cliente->documentofecha." del comprobante: ".$cliente->comprobante." error: La serie debe ser del siguiente formato BXXX";
                throw new Exception($mensaje);
            }
          
            

        }

        // print_r($comprobante); exit;

        $sql_detalle_comprobante = "SELECT 
        d.codconcepto AS codproducto,
        'ZZ' AS codunidad, /* codunidad para servicios*/
        c.descripcion AS producto,
        1 AS cantidad,
        d.importe AS valor_unitario,
        d.importe AS valor_venta,
        d.importe * $igv->valor / 100 AS igv,
        /*".$codtipoigv." AS codtipoigv,*/
        CASE WHEN c.afecto_igv = 1 THEN '10' ELSE '20' END AS codtipoigv,
        d.importe * $igv->valor / 100 AS total_impuestos,
        d.importe + (d.importe * $igv->valor / 100) AS precio_unitario
        FROM cpe.vista_documentos_electronicos AS vde
        ".$join."
        WHERE vde.tabla='{$tabla}' AND vde.codemp={$codemp} AND vde.codsuc={$codsuc} AND vde.nroinscripcion={$nroinscripcion} AND vde.codciclo={$codciclo} AND vde.idmovimiento={$id} AND d.codconcepto NOT IN(5,7,8)";
        // die($sql_detalle_comprobante);
        $detalle_comprobante = $model->query($sql_detalle_comprobante)->fetchAll();


        $cpe->comprobante($comprobante, $detalle_comprobante);
        // print_R($cpe); exit;
        if($comprobante->codtipodocumento == "01") {
            $cpe->enviar_sunat();
            // exit;
            // print_r($cpe->getCode()); exit;
            $cdr_response = $cpe->getCdrResponse();
            if($cpe->getCode() !== 0) {
                $mensaje = "Error en la fecha: ".$cliente->documentofecha." del comprobante: ".$cliente->comprobante." error: ".$cpe->getCodigoError().": ".$cpe->getErrorDescripcion();
                throw new Exception($mensaje);
            }

       
            $row->estado = $comprobante->estado;
            $res = guardar_documento($row, $cpe, $cdr_response);
    
            if($res->errorCode() != '00000') {
                $error = $res->errorInfo();
                $mensaje = "Error al insertar documento: ".$error[2];
                throw new Exception($mensaje);
            }

            //ACTUALIZAMOS LA RESPECTIVA TABLA DE PAGO, SI EL COMPORBANTE FUE ACEPTADO
            // if($cpe->getCode() === 0) {
            
                $res = actualizar_pago($row);
                // print_r($res->errorCode()); exit;
                if($res->errorCode() != '00000') {
                    $error = $res->errorInfo();
                 
                    $mensaje = "Error al modificar: ".$tabla." error: ".$error[2];
                    throw new Exception($mensaje);
                }
            // }
        } else {
            $update_documento = array();
            $update_documento[":documento_nombre"] = $cpe->getNombreDocumento();
            $update_documento[":documento_nombre_xml"] = $cpe->getNombreXml();
            $update_documento[":documento_nombre_cdr"] = $cpe->getNombreCdr();
            
            $where_documento = array();
            $where_documento[":codemp"] = $row->codemp;
            $where_documento[":codsuc"] = $row->codsuc;
            $where_documento[":nrooperacion"] = $row->idmovimiento;
            $where_documento[":nroinscripcion"] = $row->nroinscripcion;
            $where_documento[":codciclo"] = $row->codciclo;
            $model->modificar("cpe.documentos", $update_documento, $where_documento);

            if($model->errorCode() != '00000') {
                $error = $model->errorInfo();
                $mensaje = "Error al modificar documento: ".$error[2];
                throw new Exception($mensaje);
            }

        }
       


        $response["res"] = 1;
        // $response["cpe"] = $cpe;
        //$model->getPDO()->commit();
        // $model->liberar();  
        return $response;
        //echo json_encode($response);
    } catch (Exception $e) {
        //$model->getPDO()->rollBack();
        $response["res"]     = 2;
        $response["mensaje"] = $e->getMessage();
        // echo json_encode($response);

        return $response;

    }

}


function generar_resumen_diario($fecha, $codemp) {
    global $model, $cpe; 
    
    $response = array();
    try {
        //$model->getPDO()->beginTransaction();

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
       /*CASE WHEN p.valor::FLOAT > 0 THEN 'S' ELSE 'N' END AS  igv_status*/
        CASE WHEN vde.igv > 0 THEN 'S' ELSE 'N' END AS  igv_status
        FROM cpe.vista_documentos_electronicos AS vde
        LEFT JOIN reglasnegocio.parame AS p ON(p.codsuc=vde.codsuc AND p.tippar = 'IMPIGV')
        WHERE vde.codsunat='03'  AND vde.documentofecha='" . $fecha . "' AND vde.codemp={$codemp}
        ORDER BY vde.documentofecha ASC";
        // die($sql_detalle_resumen);
        $detalle_resumen = $model->query($sql_detalle_resumen)->fetchAll();
        //   echo "<pre>";

        // print_r($resumen); exit;
        
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
        // print_r($cpe); exit;
        $cpe->enviar_sunat();
        // var_dump($cpe->getCode()); exit;
        if($cpe->getCode() !== 0) {
            $mensaje = "Error en resumen de la fecha: ".$fecha." error: ".$cpe->getCodigoError().", ".$cpe->getErrorDescripcion();
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
            $res = guardar_documento($row, $cpe, "La Boleta número " . $row->serie."-".$row->correlativo . ", ha sido aceptada");

            $documento_id = $res->lastInsertId();
    
            if($res->errorCode() != '00000') {
                $error = $res->errorInfo();
                $mensaje = "Error al insertar documento: ".$error[2];
                throw new Exception($mensaje);
            }

            $res = generar_comprobante($row);
            // $res = guardar_documento($row, $cpe);
            if($res["res"] == 2) {
                throw new Exception($res["mensaje"]);
            }

           

            $res = guardar_detalle_resumen($rd_id,  $documento_id, $row->dr_estado);
            //echo $model->Sql()."\n";
            if($res->errorCode() != '00000') {
                $error = $res->errorInfo();
                $mensaje = "Error al insertar Detalle Resumen: ".$error[2];
                throw new Exception($mensaje);
            }

            //ACTUALIZAMOS LA RESPECTIVA TABLA DE PAGO, SI EL COMPORBANTE FUE ACEPTADO
            // if($cpe->getCode() === 0) {
                
               
            // }

            $res = actualizar_pago($row);

            if($res->errorCode() != '00000') {
                $error = $res->errorInfo();
                $mensaje = "Error al modificar: ".$row->tabla." ".$error[2];
                throw new Exception($mensaje);
            }

        }

        $response["res"] = 1;
        //$model->getPDO()->commit();
        return $response;
    } catch(Exception $e) {
        //$model->getPDO()->rollBack();
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
                            throw new Exception("Resumen, Fecha Generacion :".$fecha.", SE REPITE EL CORRELATIVO: ".$vb->serie."-".Formato8Caracteres($correlativo_anterior));

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


// $fdesde = CodFecha($_REQUEST["fdesde"]);
// $fhasta = CodFecha($_REQUEST["fhasta"]);

$fdesde = CodFecha(isset($argv[1]) ? $argv[1] : "");
$fhasta = CodFecha(isset($argv[2]) ? $argv[2] : "");
$fechaCursor = $fdesde ;


$response = array();

$mensajes = "";
$contador = 1;
while($fechaCursor <= $fhasta) {
    // echo $fechaCursor."<br>";
    // RESUMEN DIARIO
    $sql_boletas = "SELECT codemp FROM cpe.vista_documentos_electronicos WHERE documentofecha='".$fechaCursor."' AND codsunat='03'
    GROUP BY codemp
    ORDER BY codemp ASC";
    // die($sql_boletas);
    $model->query($sql_boletas);
   
    if($model->NumRows() > 0) {
        $boletas = $model->query($sql_boletas);
        
        while($row = $boletas->fetch()) {
            // print_r($row);
          
            // NO DEBERIA EXISTIR RESUMENES ACEPTADOS EN LA FECHA CORRESPONDIENTE
            $model->query("SELECT * FROM cpe.resumenes_diarios WHERE rd_fecha_generacion='".$fechaCursor."' AND codemp={$row->codemp} AND rd_tipo='RN' AND rd_code = 0");
            // var_dump($model->NumRows()); exit;
            if($model->NumRows() <= 0) {
                $response = validar_resumen_diario($fechaCursor, $row->codemp);
               
                if($response["res"] == 2) {
                    // throw new Exception($response["mensaje"]);
                    $mensajes .= $response["mensaje"]."\n";
                    continue;
                }

                try {
                    $model->getPDO()->beginTransaction();
                    $response = generar_resumen_diario($fechaCursor, $row->codemp);
                    // print_R($reponse);
                    // exit;
                    if($response["res"] == 2) {
                        throw new Exception($response["mensaje"]);
                        
                    }

                    $model->getPDO()->commit();
                } catch(Exception $e) {
                    $model->getPDO()->rollBack();
                    $mensajes .= $contador.") ".$e->getMessage()."\n";
                    $contador = $contador + 1;
                }
            } else {
                //$mensajes .= "YA EXISTE UN RESUMEN DIARO DE FECHA: ".$fechaCursor."\n";
            }

        }
    } else {
        //$mensajes .= "NO EXISTEN BOLETAS EN LA FECHA: ".$fechaCursor."\n";
    }
    
    // FACTURAS
    $sql_facturas = "SELECT codemp FROM cpe.vista_documentos_electronicos WHERE documentofecha='".$fechaCursor."' AND codsunat='01'
    GROUP BY codemp
    ORDER BY codemp ASC";
    $model->query($sql_facturas);

    if($model->NumRows() > 0) {
        $facturas = $model->query($sql_facturas);
        while($row = $facturas->fetch()) {
            
            $sql_facturas = "SELECT * FROM cpe.vista_documentos_electronicos WHERE documentofecha='".$fechaCursor."' AND codsunat='01' AND codemp={$row->codemp} AND estado_cpe='PENDIENTE'";
            // die($sql_facturas);
            $model->query($sql_facturas);

            if($model->NumRows() > 0) {
                $facturas = $model->query($sql_facturas);

                while($factura = $facturas->fetch()) {
                    try {
                        $model->getPDO()->beginTransaction();
                       

                        $response = generar_comprobante($factura);
                        // print_r($response); exit;
                        if($response["res"] == 2) {
                            throw new Exception($response["mensaje"]);
                        
                        }
                        $model->getPDO()->commit();
                    } catch(Exception $e) {
                        $model->getPDO()->rollBack();
                        $mensajes .= $contador.") ".$e->getMessage()."\n";
                        $contador = $contador + 1;
                    }

                   

                 
                }
                
            }
            
            


        }

    }
    $fechaTime = strtotime($fechaCursor."+ 1 days");
    $fechaCursor = date("Y-m-d", $fechaTime);
}

$response["mensaje"] = $mensajes;
// $response["res"] = 1;
// print_r($response);

$model->liberar();
echo json_encode($response);

    

    

?>