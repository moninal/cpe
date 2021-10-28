<?php 

/**
 * CPE 1.0 fase Beta
 * Author: Joan Manuel
 */

// declare(strict_types=1);

// para el certificado 
use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;

// para datos de la empresa y cliente
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;

// para datos de la empresa
use Greenter\Model\Company\Company;

// para el comprobante 
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Sale\Invoice;

// para los comprobantes (boletas, facturas, notas)
use Greenter\Model\Sale\SaleDetail;
use Greenter\Model\Sale\Legend;

// para el resumen diario
use Greenter\Model\Summary\Summary;
use Greenter\Model\Summary\SummaryDetail;

// para el cdr
use Greenter\Model\DocumentInterface;

// para la comunicacion de baja
use Greenter\Model\Voided\Voided;
use Greenter\Model\Voided\VoidedDetail;

// para la consulta
use Greenter\Ws\Services\SoapClient;
// para el envio del xml de comprobantes (boletas, facturas, notas)
use Greenter\Ws\Services\BillSender;
// para consulta de resumenes y comunicacion de baja
use Greenter\Ws\Services\ExtService;

// para consulta cdr
use Greenter\Ws\Services\ConsultCdrService;


// require '../vendor/autoload.php';
require_once(dirname(__DIR__)."/vendor/autoload.php");
date_default_timezone_set("America/Lima");
require_once("clsModel.php");
require_once("NumerosEnLetras.php");

class CPE {

   

    private $empresa;
    private $see;
    private $cliente;
    private $codtipodocumento; // RD: resumen diario, CB: comunicacion baja
    private $cpe;

    private $nombre_xml;
    private $nombre_cdr;
    private $ticket;
    //private $success;
    //private $forma_comprobacion;
    private $code;
    private $cdr_response;
    private $observaciones;
    private $codigo_error;
    private $error_descripcion;
    private $endpoint;
    private $soap;

    public function __construct($endpoint, $empresa, $ws) {

        global $usuario_sol, $clave_sol;

        $address = new Address();
        $address->setUbigueo($empresa->codubigeo)
            ->setDistrito($empresa->distrito)
            ->setProvincia($empresa->provincia)
            ->setDepartamento($empresa->departamento)
            ->setUrbanizacion('NONE')
            ->setCodLocal('0000')
            ->setDireccion($empresa->direccion);

        $company = new Company();
        $company->setRuc($empresa->ruc)
            ->setNombreComercial($empresa->nombre_comercial)
            ->setRazonSocial($empresa->razon_social)
            ->setAddress($address)
            ->setEmail($empresa->email)
            ->setTelephone($empresa->telefono);

        $this->empresa = $company;

        $see = new See();
        $see->setCertificate(file_get_contents(dirname(__DIR__).'/certificados/'.$empresa->certificado_digital));
        $see->setService($endpoint);

        if($ws == "OSE") {
            // die($empresa->clave_sol);
            $see->setClaveSOL("", $empresa->usuario_sol, $empresa->clave_sol);
        } else {

            $see->setClaveSOL($empresa->ruc, $empresa->usuario_sol, $empresa->clave_sol);
        }


       

        $soap = new SoapClient("https://e-factura.sunat.gob.pe/ol-it-wsconscpegem/billConsultService?wsdl");
        // $soap->setService("https://e-factura.sunat.gob.pe/ol-it-wsconscpegem/billConsultService?wsdl");
        if($ws == "OSE") {

            $soap->setCredentials($empresa->ruc.$usuario_sol, $clave_sol);
        } else {


            $soap->setCredentials($empresa->ruc.$empresa->usuario_sol, $empresa->clave_sol);
        }

        // echo "<pre>";
        // print_r($soap);

        $this->soap = $soap;
        $this->endpoint = $endpoint;
        $this->see = $see;
        $this->codtipodocumento = "";

        $this->nombre_xml = "";
        $this->nombre_cdr = "";
        $this->ticket = "";
        //$this->success = "";
        //$this->forma_comprobacion = "";
        $this->code = -1;
        $this->cdr_response = "";
        $this->observaciones = "";
        $this->codigo_error = "";
        $this->error_descripcion = "";
        $this->nombre_documento = "";

        // echo "<pre>";
        // print_r($company); exit;
    }


    public function limpiar_generar() {
        $this->codtipodocumento = "";
        $this->nombre_xml = "";
        $this->nombre_cdr = "";
        $this->nombre_documento = "";
       
       
       
    }

    public function limpiar_enviar() {
         //$this->success = "99";
        //$this->forma_comprobacion = "";
        $this->ticket = "";
        $this->code = -1;
        $this->cdr_response = "";
        $this->observaciones = "";
        $this->codigo_error = "";
        $this->error_descripcion = "";
    }

    public function setCodTipoDocumento($codtipodocumento) {
        $this->codtipodocumento = $codtipodocumento;
    }
   

    public function getSee() {
        return $this->see;
    }

    public function getEmpresa() {
        return $this->empresa;
    }

    public function getCodTipoDocumento() {
        return $this->codtipodocumento;
    }

    public function getNombreXml() {
        return $this->nombre_xml;
    }

    public function getNombreCdr() {
        return $this->nombre_cdr;
    }

    public function getTicket() {
        return $this->ticket;
    }

    // public function getSuccess() {
    //     return $this->success;
    // }

    // public function getFormaComprobacion() {
    //     return $this->forma_comprobacion;
    // }

    public function getCode() {
        return $this->code;
    }

    public function getCdrResponse() {
        return $this->cdr_response;
    }

    public function getObservaciones() {
        return $this->observaciones;
    }

    public function getCodigoError() {
        return $this->codigo_error;
    }

    public function getErrorDescripcion() {
        return $this->error_descripcion;
    }

    public function getNombreDocumento() {
        return $this->nombre_documento;
    }

    public function completar_vacios($cadena, $caracter, $tamanio) {

        return substr(str_repeat($caracter, $tamanio).$cadena, - $tamanio);
    }

    public function setCliente($cliente) {
        $client = new Client();
        $client->setTipoDoc((string)$cliente->codtipodocumentoidentidad)
            ->setNumDoc((string)$cliente->nrodocumentoidentidad)
            ->setRznSocial($cliente->razon_social)
            ->setAddress((new Address())
                    ->setDireccion($cliente->direccion));
        /*->setEmail('client@corp.com')
        ->setTelephone('01-445566');*/
        $this->cliente = $client;
    }


    public function comprobante($comprobante, $detalle_comprobante) {
        $this->limpiar_generar();
        $array_items = array();

        $this->codtipodocumento = $comprobante->codtipodocumento;

        $invoice = new Invoice();
        $invoice
            ->setUblVersion('2.1')
            ->setFecVencimiento(new DateTime($comprobante->fecha))
            ->setTipoOperacion('0101') // Catalog. 51
            ->setTipoDoc($comprobante->codtipodocumento)
            ->setSerie($comprobante->serie) // 4 caracteres 
            ->setCorrelativo($this->completar_vacios($comprobante->correlativo, "0", 8))// minimo 1, maximo 8 digitos
            ->setFechaEmision(new DateTime($comprobante->fecha))
            ->setFormaPago(new FormaPagoContado())
            ->setTipoMoneda($comprobante->codmoneda)
            ->setCompany($this->empresa)
            ->setClient($this->cliente);
        
        if($comprobante->igv_status == "S") { // S: SI, N: NO
        
            $invoice->setMtoOperGravadas($comprobante->valor_venta); // con igv, es el monto sumando montos de impuestos, es la suma de todos los montos de valor venta del detalle en donde tipo de igv = '10'
                
        } else {
            $invoice->setMtoOperExoneradas($comprobante->valor_venta); // sin igv, es el monto sin sumar montos de impuestos(por logica como no toma en cuenta igv, no habra tampoco montos de impuestos), es la suma de todos los montos de valor venta del detalle en donde tipo de igv = '20'
        }
        $invoice
            ->setMtoIGV($comprobante->igv) // solo el monto de igv, si no hay poner cero
            ->setTotalImpuestos($comprobante->total_impuestos) // se suman todos los montos de impuestos como: igv, impuesto de bolsa, etc, si no hay poner cero
            ->setValorVenta($comprobante->valor_venta) // es la suma de todos los montos de valor venta del detalle, sin sumar montos de impuestos
            ->setSubTotal($comprobante->subtotal) // es el total general (incluido montos impuestos), en el caso de existir redondeo, seria el total sin redondear
            ->setRedondeo($comprobante->redondeo) // si no hay poner cero
            ->setMtoImpVenta($comprobante->total); // es el total ya redondeado, lo que realmente se le va cobrar al cliente


        foreach ($detalle_comprobante as $key => $value) {
            $item = new SaleDetail();
            $item->setCodProducto($value->codproducto)
                ->setUnidad($value->codunidad)
                ->setDescripcion($value->producto)
                ->setCantidad($value->cantidad)
                ->setMtoValorUnitario($value->valor_unitario) // es el precio del producto, sin incluir monto igv 
                ->setMtoValorVenta($value->valor_venta) // es el resultado de la cantidad x el precio(valor_unitario) sin tomar en cuenta monto del igv
                ->setMtoBaseIgv($value->valor_venta) // es el resultado de la cantidad x el precio sin tomar en cuenta monto del igv
                ->setPorcentajeIgv($comprobante->porcentaje_igv) // es el % igv por lo general es el 18%
                ->setIgv($value->igv) // es el monto del igv del producto de la cantidad x el precio unitario
                ->setTipAfeIgv($value->codtipoigv) // Catalog: 07, 10 con igv, 20 sin igv
                ->setTotalImpuestos($value->total_impuestos) // es la suma de todos los montos de impuestos: igv, icbper, etc
                ->setMtoPrecioUnitario($value->precio_unitario); // es el precio del producto, incluyendo monto igv si es que tuviera(sumado solo monto igv si es que tuviera, ningun otro monto de impuestos)

            if($comprobante->icbper_status == "S") {
                
            }

            

        }

        array_push($array_items, $item);
        // print_r($invoice); exit;
        $invoice->setDetails($array_items)
        ->setLegends([
            (new Legend())
                ->setCode('1000') // Catálogo No. 15 del Anexo N° 8 – Catálogo de códigos
                ->setValue(NumerosEnLetras::convertir($comprobante->total))
        ]);

        //$this->success = "0";
        $this->cpe = $invoice;
        // print_r($this->cpe->getDetails()[0]->getUnidad()); exit;
        // file_put_contents('../XML/' . $sum->getName() . '.xml', $xml);
        $this->nombre_xml = $invoice->getName() . '.xml';
        $this->nombre_documento = $invoice->getName();
        $xml = $this->see->getXmlSigned($invoice);
        //$this->see->getFactory()->getLastXml(); // eso solo funciona cuando primero envias el send como en los ejemplos de greenter, pero como yo primero genero el xml utilizo la otra funcion para obtener el xml $this->see->getXmlSigned($invoice)
        $this->writeXml($invoice->getName(), $xml, dirname(__DIR__)."/XML");
    }

   

    public function resumen_diario($resumen, $detalle_resumen) {
        $this->limpiar_generar();
        $array_detail = array();
        $this->codtipodocumento = "RD"; 
        foreach ($detalle_resumen as $key => $value) {

            // $estado = '1'; 
            // if ($value->estado == "0") { // 0: ANULADO. 1: ACTIVO
            //     $estado = '3';
            // }
            $detail = new SummaryDetail();
            $detail->setTipoDoc($value->codtipodocumento)
                ->setSerieNro($value->serie."-".$value->correlativo)
                ->setEstado($value->dr_estado) // cat 19 1 -> adicionar, 2-> modificar, 3 -> anulado,
            //4 anulado en el dia antes de informar comprobante, transporte publico,
                // 08/05/2021:
                // 1: Se esta informando por primera vez.
                // 2: Se informó previamente y se quiere editar sus valores.
                // 3: Se quiere anular el comprobante
                ->setClienteTipo($value->codtipodocumentoidentidad) // suponiendo que es el tipo de documento de identidad
                ->setClienteNro($value->nrodocumentoidentidad) // el numero de documento de identidad
                ->setTotal($value->total);
            if($value->igv_status == "S") {
                $detail->setMtoOperGravadas($value->valor_venta); // 
            } else {
                $detail->setMtoOperExoneradas($value->valor_venta); // 
            }
                
                //->setMtoOperInafectas(24.4)  // no se usa
                //->setMtoOperExoneradas($value->imptotal)  // 
                //->setMtoOtrosCargos(21) // no se usa
                $detail->setMtoIGV($value->igv); // si no hay o no toma en cuenta igv, se pone cero nomas
            //     echo "<pre>";
            // print_r($detail);
            array_push($array_detail, $detail);
            
        }
     
        $sum = new Summary();
        $sum->setFecGeneracion(new \DateTime($resumen->fecha_generacion))
            ->setFecResumen(new \DateTime($resumen->fecha_resumen))
            ->setCorrelativo($resumen->correlativo)
            ->setCompany($this->empresa)
            ->setDetails($array_detail);
            // print_r($array_detail); exit;
        $this->cpe = $sum;

       
        // $xml = $this->see->getXmlSigned($sum);
        // file_put_contents('../XML/' . $sum->getName() . '.xml', $xml);
        $this->nombre_xml = $sum->getName() . '.xml';
        $this->nombre_documento = $sum->getName();
        $xml = $this->see->getXmlSigned($sum);
        $this->writeXml($sum->getName(), $xml, dirname(__DIR__)."/XML");
    }

    public function comunicacion_baja($comunicacion, $detalle_comunicacion) {
        $this->limpiar_generar();

        $array_detail = array();
        $this->codtipodocumento = "CB"; 
      
        foreach ($detalle_comunicacion as $key => $value) {
            $detail = new VoidedDetail();
            $detail->setTipoDoc($value->codtipodocumento)
                ->setSerie($value->serie)
                ->setCorrelativo($value->correlativo)
                ->setDesMotivoBaja($value->motivo_baja);
            array_push($array_detail, $detail);
        }


        $voided = new Voided();
        $voided->setCorrelativo($comunicacion->correlativo)
            ->setFecComunicacion(new \DateTime($comunicacion->fecha_comunicacion))
            ->setFecGeneracion(new \DateTime($comunicacion->fecha_generacion))
            ->setCompany($this->empresa)
            ->setDetails($array_detail);
       
        $this->cpe = $voided;
        // $xml = $this->see->getXmlSigned($sum);
        // file_put_contents('../XML/' . $sum->getName() . '.xml', $xml);
        $this->nombre_xml = $voided->getName() . '.xml';
        $this->nombre_documento = $voided->getName();
        $xml = $this->see->getXmlSigned($voided);
        $this->writeXml($voided->getName(), $xml, dirname(__DIR__)."/XML");
    }

    public function enviar_sunat() {
        $this->limpiar_enviar();
        $res = $this->see->send($this->cpe);
        // $contador = 1;
        // do {
            // echo $contador."<br>";
           
            // $res = $this->see->send($this->cpe);
        //     $contador ++;
        //     if($contador == 20) {
        //         break;
        //     }
        // } while (!$res->isSuccess());
        // print_r($res); exit;
        if($this->codtipodocumento != "RD" && $this->codtipodocumento != "CB") {
            //$this->success = "0";
        }
        
        if ($res->isSuccess()) {
            if($this->codtipodocumento == "RD" || $this->codtipodocumento == "CB") {
                //$this->success = "0";
                $this->ticket = $res->getTicket();
                // $contador = 1;
                // do {
                    
                    $res = $this->see->getStatus($this->ticket); 
                //     $contador ++;
                //     if($contador == 20) {
                //         break;
                //     }
                // } while (!$res->isSuccess());
                

                // $statusService = new ExtService();
                // $statusService->setClient($this->soap);
                // $res = $statusService->getStatus($this->ticket);
            }

            if ($res->isSuccess()) {
                        
                //$this->success = "1";
                //$this->forma_comprobacion = "S";

                $cdr = $res->getCdrResponse();
                $this->writeCdr($this->cpe->getName(), $res->getCdrZip(), dirname(__DIR__)."/CDR");

                $this->cdr_response = $cdr->getDescription();
                $this->nombre_cdr = 'R-' . $this->cpe->getName() . '.zip';

                $code = (int)$cdr->getCode();
                $this->code = $code;
                if (count($cdr->getNotes()) > 0) {
                    foreach ($cdr->getNotes() as $obs) {
                        $this->observaciones .= $obs.PHP_EOL;
                    }
                }
            } 

            // else {
            //     $this->codigo_error =  $res->getError()->getCode();
            //     $this->error_descripcion = $res->getError()->getMessage();
            // }
        }
        // else {
        //     $this->codigo_error  =  $res->getError()->getCode();
        //     $this->error_descripcion = $res->getError()->getMessage();
        // }

        // print_r($this->code); exit;
        if (!$res->isSuccess()) {
            $this->codigo_error =  $res->getError()->getCode();
            $this->error_descripcion = $res->getError()->getMessage();
        }
        
    }


    function consultar_documento_no_usar($nombre_documento, $ticket = "") {
        //$this->success = "0";
        if($this->codtipodocumento == "RD" || $this->codtipodocumento == "CB") {

            $statusService = new ExtService();
            $statusService->setClient($this->soap);
            $res = $statusService->getStatus($ticket);
            
            
        } else {
            $sender = new BillSender();
            $sender->setClient($this->soap);
          
            $xml = file_get_contents(dirname(__DIR__)."/XML/".$nombre_documento.'.xml');
            $res = $sender->send($nombre_documento, $xml);
        }
      
        if ($res->isSuccess()) {
                        
            //$this->success = "1";
            //$this->forma_comprobacion = "SA";

            $cdr = $res->getCdrResponse();
            $this->writeCdr($nombre_documento, $res->getCdrZip(), dirname(__DIR__)."/CDR");

            $this->cdr_response = $cdr->getDescription();
            $this->nombre_cdr = $nombre_documento . '.zip';

            $code = (int)$cdr->getCode();
            $this->code = $code;
            if (count($cdr->getNotes()) > 0) {
                foreach ($cdr->getNotes() as $obs) {
                    $this->observaciones .= $obs.PHP_EOL;
                }
            }
        } 

        if (!$res->isSuccess()) {
            $this->codigo_error =  $res->getError()->getCode();
            $this->error_descripcion = $res->getError()->getMessage();
        }
    
    }


    
    public function writeXml(?string $nombre_document, ?string $xml, ?string $dir): void {
        $this->writeFile($nombre_document.'.xml', $xml, $dir);
    }

    public function writeCdr(?string $nombre_document, ?string $zip, ?string $dir): void {
        $this->writeFile('R-'.$nombre_document.'.zip', $zip, $dir);
    }

    public function writeFile(?string $filename, ?string $content, ?string $dir): void {
        if (getenv('GREENTER_NO_FILES')) {
            return;
        }

        $fileDir = $dir;

        if (!file_exists($fileDir)) {
            mkdir($fileDir, 0777, true);
        }

        if(file_exists($fileDir.DIRECTORY_SEPARATOR.$filename)) {
            $file = explode(".", $filename);
            $nombre = $file[0];
            $ext = $file[1];
            rename($fileDir.DIRECTORY_SEPARATOR.$filename, $fileDir.DIRECTORY_SEPARATOR.$nombre."_".date("dmY")."_".date("His").".old");
        }   
        //echo $fileDir.DIRECTORY_SEPARATOR.$filename;
        file_put_contents($fileDir.DIRECTORY_SEPARATOR.$filename, $content);
    }

    public function consulta_cdr($ruc, $codtipodocumento, $serie, $correlativo, $nombre_cdr) {
        $this->limpiar_enviar();
        $service = new ConsultCdrService();
        $service->setClient($this->soap);

        $rucEmisor = $ruc;
        $tipoDocumento = $codtipodocumento; // 01: Factura, 07: Nota de Crédito, 08: Nota de Débito
        $serie =  $serie;
        $correlativo = (int)$correlativo;
        // echo $rucEmisor." ".$tipoDocumento." ".$serie." ".$correlativo;
        $result = $service->getStatusCdr($rucEmisor, $tipoDocumento, $serie, $correlativo);
       

        if (!$result->isSuccess()) {
            var_dump($result->getError());
            return;
        }


        $cdr = $result->getCdrResponse();
        // echo "<pre>";
        // print_r($cdr->getDescription());
        // print_r($cdr->getNotes());
        if ($cdr === null) {
            echo 'CDR no encontrado, el comprobante no ha sido comunicado a SUNAT.';
            return;
        }
       
       // file_put_contents('R-20000000001-01-F001-1.zip', $result->getCdrZip());

        $this->writeCdr($nombre_cdr, $result->getCdrZip(), dirname(__DIR__)."/CDR");

        $this->cdr_response = $cdr->getDescription();
        $this->nombre_cdr = $nombre_cdr . '.zip';

        $code = (int)$cdr->getCode();
        $this->code = $code;
        if (count($cdr->getNotes()) > 0) {
            foreach ($cdr->getNotes() as $obs) {
                $this->observaciones .= $obs.PHP_EOL;
            }
        }
        // return $cdr;
        //var_dump($cdr);
    }



}

$sql_empresa = "SELECT 
codubigeo,
direccion,
ruc,
nombre_comercial,
razonsocial AS razon_social,
email,
telefono,
certificado_digital,
usuario_sol,
clave_sol
FROM admin.empresas WHERE codemp={$codemp}";

$empresa = $model->query($sql_empresa)->fetch();

$coddepartamento = substr($empresa->codubigeo, 0, -4);
$codprovincia = substr($empresa->codubigeo, 0, -2);
$coddistrito = $empresa->codubigeo;


$departamento_sql =  "SELECT * FROM public.ubigeo WHERE codubigeo='".$coddepartamento."0000"."'";
// die($departamento_sql); exit;
$departamento = $model->query($departamento_sql)->fetch();

$provincia_sql =  "SELECT * FROM public.ubigeo WHERE codubigeo='".$codprovincia."00"."'";
$provincia = $model->query($provincia_sql)->fetch();

$distrito_sql =  "SELECT * FROM public.ubigeo WHERE codubigeo='".$coddistrito."'";
$distrito = $model->query($distrito_sql)->fetch();
// print_r($distrito_sql); exit;
$empresa->distrito = $distrito->descripcion;
$empresa->provincia = $provincia->descripcion;
$empresa->departamento = $departamento->descripcion;

// echo "<pre>";
// print_r($empresa);
// exit;
// echo SunatEndpoints::FE_PRODUCCION; exit;
//var_dump(SunatEndpoints::FE_PRODUCCION);
// $cpe = new CPE(SunatEndpoints::FE_BETA, (object)$empresa);
// global $endpoint;
if(empty($endpoint)) {
    $endpoint = SunatEndpoints::FE_PRODUCCION;
}

// die($ws);
$cpe = new CPE($endpoint, (object)$empresa, $ws);
// $hash = hash('sha256', "Otic$2021");
// $hash = hash('sha256', "emapicaOtic$2021");
// echo $hash;
// print_r($cpe);        
// $array["key"] = "value";


// $object = json_encode($array);
// $object1 = json_decode($object);

// print_r($object);
// echo $object->key;

    


?>