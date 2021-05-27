<?php


header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

// require_once "clsModel.php";
require_once "CPE.php";

$response = array();

try {
    $model->getPDO()->beginTransaction();
   

    $tabla          = $_REQUEST["tabla"];
    $codemp         = $_REQUEST["codemp"];
    $codsuc         = $_REQUEST["codsuc"];
    $nroinscripcion = $_REQUEST["nroinscripcion"];
    $codciclo       = $_REQUEST["codciclo"];
    $id             = $_REQUEST["id"];

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
    if($igv->valor > 0) {
        $igv_status = "S";
        $codtipoigv = "10";
    } else {
        $igv_status = "N";
        $codtipoigv = "20";
    }

    $sql_cliente = "SELECT 
    vde.nroinscripcion,
    '6' AS codtipodocumentoidentidad,
    CASE WHEN vde.nroinscripcion=0 THEN vde.nrodocumento ELSE vde.cliente_numero_documento END AS nrodocumentoidentidad,
    CASE WHEN vde.nroinscripcion=0 THEN  vde.propietario ELSE vde.razonsocial END AS razon_social,
    CASE WHEN vde.nroinscripcion=0 THEN  vde.direccion ELSE vde.direcciondistribucion END AS direccion

    FROM cpe.vista_documentos_electronicos AS vde
    WHERE vde.tabla='{$tabla}' AND vde.codemp={$codemp} AND vde.codsuc={$codsuc} AND vde.nroinscripcion={$nroinscripcion} AND vde.codciclo={$codciclo} AND vde.idmovimiento={$id}";
   

    $cliente = $model->query($sql_cliente)->fetch();

    if($cliente->nroinscripcion == "0") {
        $cliente->nrodocumentoidentidad = substr($cliente->nrodocumentoidentidad, -11, 11);
    }
    
    if(!is_numeric($cliente->nrodocumentoidentidad) || strlen($cliente->nrodocumentoidentidad) != 11) {
        $mensaje = "RUC INVÁLIDO";
        throw new Exception($mensaje);
    }


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
    vde.redondeo,
    (vde.subtotal + vde.igv) AS subtotal,
    
    vde.igv AS total_impuestos,
    '".$igv_status."' AS igv_status,
    ".$igv->valor." AS porcentaje_igv,
    'N' AS icbper_status,
    CASE WHEN vde.estado = 1 THEN '3' ELSE '1' END estado
    FROM cpe.vista_documentos_electronicos AS vde
    WHERE vde.tabla='{$tabla}' AND vde.codemp={$codemp} AND vde.codsuc={$codsuc} AND vde.nroinscripcion={$nroinscripcion} AND vde.codciclo={$codciclo} AND vde.idmovimiento={$id}";
    //die($sql_comprobante);

    $comprobante = $model->query($sql_comprobante)->fetch();


    $sql_detalle_comprobante = "SELECT 
    d.codconcepto AS codproducto,
    'ZZ' AS codunidad, /* codunidad para servicios*/
    c.descripcion AS producto,
    1 AS cantidad,
    d.importe AS valor_unitario,
    d.importe AS valor_venta,
    d.importe * $igv->valor / 100 AS igv,
    ".$codtipoigv." AS codtipoigv,
    d.importe * $igv->valor / 100 AS total_impuestos,
    d.importe + (d.importe * $igv->valor / 100) AS precio_unitario
    FROM cpe.vista_documentos_electronicos AS vde
    ".$join."
    WHERE vde.tabla='{$tabla}' AND vde.codemp={$codemp} AND vde.codsuc={$codsuc} AND vde.nroinscripcion={$nroinscripcion} AND vde.codciclo={$codciclo} AND vde.idmovimiento={$id} AND d.codconcepto NOT IN(5,7,8)";
    // die($sql_detalle_comprobante);
    $detalle_comprobante = $model->query($sql_detalle_comprobante)->fetchAll();


    $cpe->comprobante($comprobante, $detalle_comprobante);
    $cpe->enviar_sunat();

    if($cpe->getSuccess() == "0") {
        $mensaje = $cpe->getCodigoError().": ".$cpe->getErrorDescripcion();
        throw new Exception($mensaje);
    }

    $where_eliminar_documento = array();
    $where_eliminar_documento[":codemp"] = $codemp;
    $where_eliminar_documento[":codsuc"] = $codsuc;
    $where_eliminar_documento[":nrooperacion"] = $id;
    $where_eliminar_documento[":nroinscripcion"] = $nroinscripcion;
    $where_eliminar_documento[":codciclo"] = $codciclo;
                
    $model->eliminar("cpe.documentos", $where_eliminar_documento);
    if($model->errorCode() != '00000') {
        $error = $model->errorInfo();
        $mensaje = "Error al eliminar Documento: ".$error[2];
        throw new Exception($mensaje);
    }

    $datos_documento = array();
    $datos_documento[":codemp"] = $codemp;
    $datos_documento[":codsuc"] = $codsuc;
    $datos_documento[":codciclo"] = $codciclo;
    $datos_documento[":nrooperacion"] = $id;
    $datos_documento[":nroinscripcion"] = $nroinscripcion;
    $datos_documento[":documento_success"] = $cpe->getSuccess();
    $datos_documento[":documento_cdr_response"] = $cpe->getCdrResponse();
    $datos_documento[":tabla"] = $tabla;
    $datos_documento[":documento_codigo_error"] = $cpe->getCodigoError();
    $datos_documento[":documento_error_descripcion"] = $cpe->getErrorDescripcion();
    $datos_documento[":documento_code"] = $cpe->getCode();
    $datos_documento[":documento_forma_comprobacion"] = $cpe->getFormaComprobacion();
    $datos_documento[":documento_observaciones"] = $cpe->getObservaciones();
    $datos_documento[":documento_nombre_xml"] = $cpe->getNombreXml();
    $datos_documento[":documento_nombre_cdr"] = $cpe->getNombreCdr();
    $datos_documento[":documento_fecha"] = date("Y-m-d");
    $datos_documento[":documento_estado"] = '1'; // se pone como activo porque primero debe emitirse en sunat, para luego darle la comunicacion de baja

    $model->insertar("cpe.documentos", $datos_documento);
    
    if($model->errorCode() != '00000') {
        $error = $model->errorInfo();
        $mensaje = "Error al insertar documento: ".$error[2];
        throw new Exception($mensaje);
    }

    //ACTUALIZAMOS LA RESPECTIVA TABLA DE PAGO, SI EL COMPORBANTE FUE ACEPTADO
    if($cpe->getCode() === 0) {
        $datos_pago = array();
        $datos_pago[":fe_facturado"] = 1;
        $WherePagos = array();

        if($tabla == "cobranza.cabpagos") {
           
            $WherePagos[":nroprepago"] = $id;
        } else {
           
            $WherePagos[":nropago"] = $id;
        }

        $where_pago[":codemp"] = $codemp;
        $where_pago[":codsuc"] = $codsuc;
        $where_pago[":nroinscripcion"] = $nroinscripcion;
        $where_pago[":codciclo"] = $codciclo;

        $model->modificar($tabla, $datos_pago, $where_pago);

        if($model->errorCode() != '00000') {
            $error = $model->errorInfo();
            $mensaje = "Error al modificar: ".$tabla." ".$error[2];
            throw new Exception($mensaje);
        }
    }

    $response["res"] = 1;
    $model->getPDO()->commit();
    $model->liberar();  
    echo json_encode($response);
} catch (Exception $e) {
    $model->getPDO()->rollBack();
    $response["res"]     = 2;
    $response["mensaje"] = $e->getMessage();
    echo json_encode($response);

}
