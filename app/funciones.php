<?php

use Dompdf\Dompdf;
require_once("CPE.php");
require_once("Ciqrcode.php");

function CodFecha($Fec) {
    if ($Fec == "") {
        return $Fec;
    }

    $mifecha = preg_split('/[\/.-]/', $Fec);
    $fecha = $mifecha[2]."-".$mifecha[1]."-".$mifecha[0];

    return $fecha;
}


function Formato8Caracteres($correlativo) {
    $stringCeros           = "";
    $numDigitosCorrelativo = mb_strlen($correlativo);
    $ceros                 = 8 - $numDigitosCorrelativo;
    while ($ceros > 0) {
        $stringCeros .= "0";
        $ceros--;
    }
    return $stringCeros . $correlativo;
}

function guardar_documento($row, $cpe, $cdr_response) {
    global $model;

    $datos_update = array();
    $datos_update[":estado"] = "I";

    $where_update_documentos = array();
    $where_update_documentos[":codemp"] = $row->codemp;
    $where_update_documentos[":codsuc"] = $row->codsuc;
    $where_update_documentos[":nrooperacion"] = $row->idmovimiento;
    $where_update_documentos[":nroinscripcion"] = $row->nroinscripcion;
    $where_update_documentos[":codciclo"] = $row->codciclo;
        
    $model->modificar("cpe.documentos", $datos_update, $where_update_documentos);

    $sql_documento = "SELECT * FROM cpe.documentos WHERE codemp={$row->codemp} AND codsuc={$row->codsuc} AND nrooperacion={$row->idmovimiento} AND nroinscripcion={$row->nroinscripcion} AND codciclo={$row->codciclo} ORDER BY documento_id DESC";
    $model->query($sql_documento);

    if($model->NumRows() > 0) {
        $documento = $model->query($sql_documento)->fetch();
        $documento_nombre = $documento->documento_nombre;
        $documento_nombre_xml = $documento->documento_nombre_xml;
        $documento_nombre_cdr = $documento->documento_nombre_cdr;
    } else {
        $documento_nombre =  $cpe->getNombreDocumento();
        $documento_nombre_xml = $cpe->getNombreXml();
        $documento_nombre_cdr = $cpe->getNombreCdr();
    }

    $datos_documentos = array();
    $datos_documentos[":codemp"] = $row->codemp;
    $datos_documentos[":codsuc"] = $row->codsuc;
    $datos_documentos[":codciclo"] = $row->codciclo;
    $datos_documentos[":nrooperacion"] = $row->idmovimiento;
    $datos_documentos[":nroinscripcion"] = $row->nroinscripcion;
    //$datos_documentos[":documento_success"] = $cpe->getSuccess();
    $datos_documentos[":documento_cdr_response"] = $cdr_response;
    $datos_documentos[":tabla"] = $row->tabla;
    // $datos_documentos[":documento_codigo_error"] = $cpe->getCodigoError();
    // $datos_documentos[":documento_error_descripcion"] = $cpe->getErrorDescripcion();
    $datos_documentos[":documento_code"] = $cpe->getCode();
    //$datos_documentos[":documento_forma_comprobacion"] = $cpe->getFormaComprobacion();
    $datos_documentos[":documento_observaciones"] = $cpe->getObservaciones();
    $datos_documentos[":documento_fecha"] = date("Y-m-d");
    $datos_documentos[":documento_estado"] = $row->estado;
    $datos_documentos[":documento_nombre"] = $documento_nombre;
    $datos_documentos[":documento_nombre_xml"] = $documento_nombre_xml;
    $datos_documentos[":documento_nombre_cdr"] = $documento_nombre_cdr;
    
    $model->insertar("cpe.documentos", $datos_documentos);

    return $model;
}

function actualizar_pago($row) {
    global $model;
    $datos_pago = array();
    $datos_pago[":fe_facturado"] = 1;
    $where_pago = array();

    if($row->tabla == "cobranza.cabpagos") {
                    
        $where_pago[":nropago"] = $row->idmovimiento;
    } else {
                
        $where_pago[":nroprepago"] = $row->idmovimiento;
    }

    $where_pago[":codemp"] = $row->codemp;
    $where_pago[":codsuc"] = $row->codsuc;
    $where_pago[":nroinscripcion"] = $row->nroinscripcion;
    $where_pago[":codciclo"] = $row->codciclo;
   
    $model->modificar($row->tabla, $datos_pago, $where_pago);
    // print_r($model->Sql());


    return $model;
}

function guardar_resumen($datos_resumen_diario, $cpe) {
    global $model;

    $datos_update = array();
    $datos_update[":estado"] = "I";

    $where = array();
    $where[":codemp"] = $datos_resumen_diario[":codemp"];
    $where[":rd_tipo"] = $datos_resumen_diario[":rd_tipo"];
    $where[":rd_fecha_generacion"] = $datos_resumen_diario[":rd_fecha_generacion"];

    $model->modificar("cpe.resumenes_diarios", $datos_update, $where);
  

    $datos_resumen_diario[":rd_ticket"] = $cpe->getTicket();
    //$datos_resumen_diario[":rd_success"] = $cpe->getSuccess();
    $datos_resumen_diario[":rd_cdr_response"] = $cpe->getCdrResponse();
    // $datos_resumen_diario[":rd_codigo_error"] = $cpe->getCodigoError();
    // $datos_resumen_diario[":rd_error_descripcion"] = $cpe->getErrorDescripcion();
    $datos_resumen_diario[":rd_code"] = $cpe->getCode();
    $datos_resumen_diario[":rd_observaciones"] = $cpe->getObservaciones();
    //$datos_resumen_diario[":rd_forma_comprobacion"] = $cpe->getFormaComprobacion();
    $datos_resumen_diario[":rd_nombre_xml"] = $cpe->getNombreXml();
    $datos_resumen_diario[":rd_nombre_cdr"] = $cpe->getNombreCdr();
    $datos_resumen_diario[":rd_nombre_documento"] = $cpe->getNombreDocumento();
    

    $model->insertar("cpe.resumenes_diarios", $datos_resumen_diario);

    return $model;
}

function guardar_detalle_resumen($rd_id, $documento_id, $dr_estado) {
    global $model;

    $datos_detalle_resumen = array();
            
    $datos_detalle_resumen[":rd_id"] = $rd_id;
    $datos_detalle_resumen[":documento_id"] = $documento_id;
    $datos_detalle_resumen[":dr_estado"] = $dr_estado;
    // $datos_detalle_resumen[":codemp"] = $row->codemp;
    // $datos_detalle_resumen[":codsuc"] = $row->codsuc;
    // $datos_detalle_resumen[":nrooperacion"] = $row->idmovimiento;
    // $datos_detalle_resumen[":nroinscripcion"] = $row->nroinscripcion;
    // $datos_detalle_resumen[":codciclo"] = $row->codciclo;
    // $datos_detalle_resumen[":tabla"] = $row->tabla;
    // $datos_detalle_resumen[":estado"] = $row->estado;

    $model->insertar("cpe.detalle_resumen", $datos_detalle_resumen);

    return $model;
}


// function desactivar_documento($row) {
//     global $model;
//     $datos_update = array();
//     $datos_update[":estado"] = "I";

//     $where_update_documentos = array();
//     $where_update_documentos[":codemp"] = $row->codemp;
//     $where_update_documentos[":codsuc"] = $row->codsuc;
//     $where_update_documentos[":nrooperacion"] = $row->idmovimiento;
//     $where_update_documentos[":nroinscripcion"] = $row->nroinscripcion;
//     $where_update_documentos[":codciclo"] = $row->codciclo;
        
//     $model->modificar("cpe.documentos", $datos_update, $where_update_documentos);

//     return $model;
// }

function crear_xml($row) {
    global $model, $cpe; 
    $response = array();
    // print_r($row); exit;
    try {        
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
        CASE WHEN vde.estado = 1 AND vde.codsunat='03' THEN 'I' ELSE 'A' END estado
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

        $response["res"] = 1;
       
        return $response;
      
    } catch (Exception $e) {
      
        $response["res"]     = 2;
        $response["mensaje"] = $e->getMessage();
      

        return $response;

    }

 

}

function crear_codigo_qr($datos) {
        
    $ciqrcode = new Ciqrcode();

    $params['data'] = $datos->ruc . '|' . $datos->codtipodocumento . "|" . $datos->serie . "|" . $datos->correlativo. "|" . $datos->igv . "|" . $datos->total . "|" . $datos->fecha . "|" . $datos->codtipodocumentoidentidad. "|" . $datos->nrodocumentoidentidad;

    $params['level']    = 'H';
    $params['size']     = 3;
    $params['savename'] = dirname(__DIR__)."/QR/" . $datos->documento_nombre . '.png';
    //print_r($params); exit;
    return $ciqrcode->generate($params);

}

function nombre_documento() {

    global $model;

    $id = $_REQUEST["id"];
    $codemp = $_REQUEST["codemp"];
    $codsuc = $_REQUEST["codsuc"];
    $nroinscripcion = $_REQUEST["nroinscripcion"];
    $codciclo = $_REQUEST["codciclo"];
    $tabla = $_REQUEST["tabla"];

    $empresa = $model->query("SELECT * FROM admin.empresas")->fetch();

    $datos = $model->query("SELECT 
    CASE WHEN documento_nombre IS NULL OR documento_nombre = '' THEN '".$empresa->ruc."' || '-' || codsunat || '-' || serie || '-' || TRIM(to_char(nrodocumentotri::INT,'00000000')) ELSE documento_nombre END AS documento_nombre
    FROM cpe.vista_documentos_electronicos WHERE idmovimiento={$id} AND codemp={$codemp} AND codsuc={$codsuc} AND nroinscripcion={$nroinscripcion} AND codciclo={$codciclo} AND tabla='{$tabla}'")->fetch();
    

    return $datos->documento_nombre;
}


function crear_pdf() {
    global $model;

    $id = $_REQUEST["id"];
    $codemp = $_REQUEST["codemp"];
    $codsuc = $_REQUEST["codsuc"];
    $nroinscripcion = $_REQUEST["nroinscripcion"];
    $codciclo = $_REQUEST["codciclo"];
    $tabla = $_REQUEST["tabla"];

    $empresa = $model->query("SELECT ruc, ruc AS empresa_ruc, razonsocial AS empresa_razonsocial, direccion AS empresa_direccion, telefono AS empresa_telefonos, logo AS empresa_logo, email AS empresa_email, link_consulta FROM admin.empresas")->fetch();
    $datos = $model->query("SELECT 
    imptotal AS total,
    igv,
    CASE WHEN documento_nombre IS NULL OR documento_nombre = '' THEN '".$empresa->ruc."' || '-' || codsunat || '-' || serie || '-' || nrodocumentotri ELSE documento_nombre END AS documento_nombre,
    nroinscripcion,
    tdi_id AS codtipodocumentoidentidad,
    CASE WHEN nroinscripcion=0 THEN nrodocumento::TEXT ELSE cliente_numero_documento::TEXT END AS nrodocumentoidentidad,
    codsunat AS codtipodocumento,
    serie,
    nrodocumentotri AS correlativo,
    documentofecha AS fecha
    FROM cpe.vista_documentos_electronicos WHERE idmovimiento={$id} AND codemp={$codemp} AND codsuc={$codsuc} AND nroinscripcion={$nroinscripcion} AND codciclo={$codciclo} AND tabla='{$tabla}'")->fetch();
    // echo $model->Sql();
    $datos->ruc = $empresa->ruc;

    if($datos->nroinscripcion == "0") {
        if($datos->codtipodocumento == "01") {
            $datos->nrodocumentoidentidad = substr($datos->nrodocumentoidentidad, -11, 11);
            $datos->codtipodocumentoidentidad = '6';
           
        } else {
            $datos->nrodocumentoidentidad  = substr($datos->nrodocumentoidentidad, -8, 8);
            $datos->codtipodocumentoidentidad = '1';

            if(!is_numeric($datos->nrodocumentoidentidad) || strlen($datos->nrodocumentoidentidad) != 8) {
                $datos->nrodocumentoidentidad = '00000000';
                $datos->codtipodocumentoidentidad = '0';
            }
        }
            
    }


    crear_codigo_qr($datos);

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

    $sql_comprobante = "SELECT 
    CASE WHEN vde.codsunat = '01' THEN 'FACTURA' ELSE 'BOLETA DE VENTA' END AS tipodoc_descripcion,
    vde.serie AS venta_serie,
    (vde.serie || '-' || vde.nrodocumentotri) AS venta_documento,
    vde.razonsocial AS cliente_nombres,
    to_char(vde.documentofecha, 'DD/MM/YYYY') AS venta_fecha,
    vde.imptotal AS venta_total,
    vde.subtotal AS valor_venta,
    vde.redondeo,
    ".$igv->valor." AS porcentaje_igv,
    CASE WHEN vde.nroinscripcion=0 THEN  vde.direccion ELSE vde.direcciondistribucion END AS cliente_direccion,
    'PEN' AS moneda_descripcion,
  
    'CONTADO' AS fp_descripcion,
    '".$igv_status."' AS igv_status,
    (vde.subtotal + vde.igv) AS subtotal,
    vde.igv
    FROM cpe.vista_documentos_electronicos AS vde
    WHERE vde.tabla='{$tabla}' AND vde.codemp={$codemp} AND vde.codsuc={$codsuc} AND vde.nroinscripcion={$nroinscripcion} AND vde.codciclo={$codciclo} AND vde.idmovimiento={$id}";
    //die($sql_comprobante);

    $comprobante = $model->query($sql_comprobante)->fetch();
    $comprobante->cliente_numero_documento =  $datos->nrodocumentoidentidad;
    // echo "<pre>";
    // print_r($comprobante); exit;
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
    //die($sql_detalle_comprobante);
    $detalle_comprobante = $model->query($sql_detalle_comprobante)->fetchAll();

    $data = array();
    // print_r($empresa); exit;
    $data["Empresa"] = $empresa;
    $data["Venta"] = $comprobante;
    $data["tipodoc_id"] = $datos->codtipodocumento;
    $data["DetalleVenta"] = $detalle_comprobante;
    $data["nombre_documento"] = $datos->documento_nombre;
   
    $URL = "http://localhost:8080/cpe/app/pdf.php";
    //Indicamos que utilizamos el protocolo http, método post, cabecera de formulario, y los parámetros de la consulta.
    $opciones = array('http' => array(
        'method'  => 'POST',
        'header'  => 'Content-type: application/x-www-form-urlencoded',
        'content' => http_build_query($data),
    ));

    //flujo de opciones.
    $contexto = stream_context_create($opciones);

    //Solicitar el contenido
    $respuesta = file_get_contents($URL, false, $contexto);
   // print_r($respuesta); exit;
    //Imprimimos la respuesta del servidor

    $pdf = new DOMPDF();
    $pdf->set_option('isHtml5ParserEnabled', true);
    $pdf->set_option('isRemoteEnabled', true);

 
  
    $pdf->set_paper('A4', "portrait");

    $pdf->load_html(utf8_decode($respuesta));

    // Renderizamos el documento PDF.
    $pdf->render();

    // $response = array();
    // $response["pdf"] = $pdf;
    // $response["documento_nombre"] = $datos->documento_nombre;
    return $pdf;
}



?>