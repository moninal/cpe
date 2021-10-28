<?php 
// para regularizar comprobantes que ya existen en sunat y obtener sus cdr y actualizar en la bd del e5w

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
    

    $sql = "SELECT * FROM cpe.vista_documentos_electronicos WHERE estado_cpe <> 'ACEPTADO'";
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

            // echo PHP_EOL.'Estado RUC: '.$data->getEstadoRuc();
            // echo PHP_EOL.'Condicion RUC: '.$data->getCondDomiRuc();
            echo $fecha[2]."/".$fecha[1]."/".$fecha[0]." -> ".$value->serie."-".$value->nrodocumentotri." -> ".$value->imptotal." -> ".$estado_sunat."<br>";
        } catch (Exception $e) {
            echo 'Excepcion cuando invocaba ConsultaApi->consultarCpe: ', $e->getMessage(), PHP_EOL;
        }
    }
}

?>