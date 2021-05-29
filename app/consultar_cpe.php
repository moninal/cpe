<?php 


    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');


    require_once(dirname(__DIR__)."/vendor/autoload.php");
    // require '../vendor/autoload.php';
    require_once("clsModel.php");

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
        $cpeFilter = (new \Greenter\Sunat\ConsultaCpe\Model\CpeFilter())
                    ->setNumRuc($ruc)
                    ->setCodComp('01') // Tipo de comprobante
                    ->setNumeroSerie('F001')
                    ->setNumero('8')
                    ->setFechaEmision('19/05/2021')
                    ->setMonto('148.3');

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

            // echo PHP_EOL.'Estado RUC: '.$data->getEstadoRuc();
            // echo PHP_EOL.'Condicion RUC: '.$data->getCondDomiRuc();

        } catch (Exception $e) {
            // echo 'Excepcion cuando invocaba ConsultaApi->consultarCpe: ', $e->getMessage(), PHP_EOL;
        }
    }
    


    // print_r($_REQUEST); exit;

    // $ruc = $_REQUEST["ruc"];
    // $codtipodocumento = $_REQUEST["codtipodocumento"];
    // $serie = trim($_REQUEST["serie"]);
    // $correlativo = trim(substr(str_repeat("0", 8).$_REQUEST["correlativo"], - 8));
    // $fecha_emision = $_REQUEST["fecha_emision"];
    // $importe_total = $_REQUEST["importe_total"];

    $ruc = isset($argv[1]) ? $argv[1] : "";
    $codtipodocumento = isset($argv[2]) ? $argv[2] : "";
    $serie = isset($argv[3]) ? trim($argv[3]) : "";
    $correlativo = isset($argv[4]) ? trim(substr(str_repeat("0", 8).$argv[4], - 8)) : "";

    $fecha_emision = isset($argv[5]) ? trim($argv[5]) : "";
    $importe_total = isset($argv[6]) ? trim($argv[6]) : "";
    // print_r($ruc); exit;

    $sql = "SELECT 
    CASE WHEN nroinscripcion=0 THEN nrodocumento ELSE cliente_numero_documento END AS nrodocumentoidentidad,
    razonsocial,
    (serie || '-' || TRIM(to_char(nrodocumentotri::INT, '00000000'))) AS nrocomprobante,
    CASE WHEN codsunat = '01' THEN 'FACTURA' ELSE 'BOLETA DE VENTA' END AS tipodoc_descripcion,
    imptotal,
    estado_cpe,
    codsunat,
    nroinscripcion,
    codemp,
    codciclo,
    codsuc,
    idmovimiento,
    tabla,
    documento_nombre_xml,
    documento_nombre_cdr
    FROM cpe.vista_documentos_electronicos WHERE codsunat='{$codtipodocumento}' AND serie='{$serie}' AND TRIM(to_char(nrodocumentotri::INT, '00000000'))='{$correlativo}' AND documentofecha='{$fecha_emision}'";   
   
    $comprobante = $model->query($sql)->fetch();


    if($comprobante && $comprobante->nroinscripcion == "0") {
        if($comprobante->codsunat == "01") {
            $comprobante->nrodocumentoidentidad = substr($comprobante->nrodocumentoidentidad, -11, 11);
           
         } else {
            $comprobante->nrodocumentoidentidad  = substr($comprobante->nrodocumentoidentidad, -8, 8);
          
            if(!is_numeric($comprobante->nrodocumentoidentidad) || strlen($comprobante->nrodocumentoidentidad) != 8) {
                $comprobante->nrodocumentoidentidad = '00000000';
  
            }
        }
        
    }

    if($comprobante) {
        $comprobante->estado_sunat = $estado_sunat;
    }
    echo json_encode($comprobante);




?>