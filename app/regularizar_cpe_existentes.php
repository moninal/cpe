<?php 
// para regularizar comprobantes que ya existen en sunat y obtener sus cdr y actualizar en la bd del e5w

require_once("funciones.php");

$empresa = $model->query("SELECT * FROM admin.empresas")->fetch();
$estado_sunat = "";
if($empresa->id_consulta != "" && $empresa->clave_consulta != "" && $empresa->id_consulta != NULL && $empresa->clave_consulta != NULL) {
    $apiInstance = new \Greenter\Sunat\ConsultaCpe\Api\AuthApi(
        new \GuzzleHttp\Client()
    );

    $token = "";
    $grant_type = 'client_credentials'; // Constante
    $scope = 'https://api.sunat.gob.pe/v1/contribuyente/contribuyentes'; // Constante
    $client_id = $empresa->id_consulta; // client_id generado en menú sol
    $client_secret = $empresa->clave_consulta; // client_secret generado en menú sol

    try {
        $result = $apiInstance->getToken($grant_type, $scope, $client_id, $client_secret);
        $token = $result->getAccessToken();
        // echo 'Token: '.$result->getAccessToken().PHP_EOL;
        // echo 'Expira: '.$result->getExpiresIn().' segundos'.PHP_EOL;
    } catch (Exception $e) {
        // echo 'Excepcion cuando invocaba AuthApi->getToken: ', $e->getMessage(), PHP_EOL;
    }

    $config = \Greenter\Sunat\ConsultaCpe\Configuration::getDefaultConfiguration()->setAccessToken($token);

    $apiInstance = new \Greenter\Sunat\ConsultaCpe\Api\ConsultaApi(
        new GuzzleHttp\Client(),
        $config->setHost($config->getHostFromSettings(1))
    );
    $ruc = $empresa->ruc; // RUC de quién realiza la consulta
    

    $sql = "SELECT v.*, CASE WHEN v.estado = 1 AND v.codsunat='03' THEN 'I' ELSE 'A' END estado 
    FROM cpe.vista_documentos_electronicos AS v
     WHERE v.estado_cpe <> 'ACEPTADO' AND (v.idmovimiento::varchar || v.codsuc::varchar) NOT IN(SELECT idmovimiento::varchar FROM cpe.tmp)  ";
    $res = $model->query($sql);
    
    while($value = $res->fetch()) {
        $fecha = explode("-", $value->documentofecha);
        $cpeFilter = (new \Greenter\Sunat\ConsultaCpe\Model\CpeFilter())
                ->setNumRuc($ruc)
                ->setCodComp($value->codsunat) // Tipo de comprobante
                ->setNumeroSerie($value->serie)
                ->setNumero($value->nrodocumentotri)
                ->setFechaEmision($fecha[2]."/".$fecha[1]."/".$fecha[0])
                ->setMonto($value->imptotal);

        try {
            $result = $apiInstance->consultarCpe($ruc, $cpeFilter);
            if (!$result->getSuccess()) {
                echo $result->getMessage();
                return;
            }

            $data = $result->getData();
            switch ($data->getEstadoCp()) {
                case '0': $estado_sunat = 'NO EXISTE'; break;
                case '1': $estado_sunat = 'ACEPTADO'; break;
                case '2': $estado_sunat = 'ANULADO'; break;
                case '3': $estado_sunat = 'AUTORIZADO'; break;
                case '4': $estado_sunat = 'NO AUTORIZADO'; break;
            }

            if($data->getEstadoCp() == '0') {
                $model->insertar("cpe.tmp", array(":idmovimiento" => $value->idmovimiento.$value->codsuc));
            }

            // if($data->getEstadoCp() == '1') {
            //     $_REQUEST["id"] = $value->idmovimiento;
            //     $_REQUEST["codemp"] = $value->codemp;
            //     $_REQUEST["codsuc"] = $value->codsuc;
            //     $_REQUEST["nroinscripcion"] = $value->nroinscripcion;
            //     $_REQUEST["codciclo"] = $value->codciclo;
            //     $_REQUEST["tabla"] = $value->tabla;
            //     $nombre_documento = nombre_documento();
           
            //     if($value->codsunat == '01') {
                   
    
            //         $cdr = $cpe->consulta_cdr($ruc, $value->codsunat, $value->serie, $value->nrodocumentotri, "R-".$nombre_documento);

            //         // $cdr_response = "La Factura número " . $value->serie."-".$value->nrodocumentotri . ", ha sido aceptada";
            //         $cdr_response = $cpe->getCdrResponse();

            //         $documento_code = $cpe->getCode();
            //         $documento_observaciones = $cpe->getObservaciones();
            //         $documento_nombre_cdr = $cpe->getNombreCdr();
                 
            //     } else {
            //         $cdr_response = "La Boleta número " . $value->serie."-".$value->nrodocumentotri . ", ha sido aceptada";

            //         $documento_code = 0;
            //         $documento_observaciones = "";
            //         $documento_nombre_cdr = "";

            //     }


            //     $datos_documentos = array();
            //     $datos_documentos[":codemp"] = $value->codemp;
            //     $datos_documentos[":codsuc"] = $value->codsuc;
            //     $datos_documentos[":codciclo"] = $value->codciclo;
            //     $datos_documentos[":nrooperacion"] = $value->idmovimiento;
            //     $datos_documentos[":nroinscripcion"] = $value->nroinscripcion;
   
            //     $datos_documentos[":documento_cdr_response"] = $cdr_response;
            //     $datos_documentos[":tabla"] = $value->tabla;
              
            //     $datos_documentos[":documento_code"] = $documento_code;
              
            //     $datos_documentos[":documento_observaciones"] = $documento_observaciones;
            //     $datos_documentos[":documento_fecha"] = date("Y-m-d");
            //     $datos_documentos[":documento_estado"] = 'A';
            //     $datos_documentos[":documento_nombre"] = $nombre_documento;
            //     $datos_documentos[":documento_nombre_xml"] = $nombre_documento.".xml";
            //     $datos_documentos[":documento_nombre_cdr"] = $documento_nombre_cdr;
                
            //     $model->insertar("cpe.documentos", $datos_documentos);
               
            // }


            // echo PHP_EOL.'Estado RUC: '.$data->getEstadoRuc();
            // echo PHP_EOL.'Condicion RUC: '.$data->getCondDomiRuc();
            echo $value->idmovimiento." -> ".$fecha[2]."/".$fecha[1]."/".$fecha[0]." -> ".$value->serie."-".$value->nrodocumentotri." -> ".$value->imptotal." -> ".$estado_sunat."<br>";
        } catch (Exception $e) {
            echo 'Excepcion cuando invocaba ConsultaApi->consultarCpe: ', $e->getMessage(), PHP_EOL;
        }
    }
}

?>