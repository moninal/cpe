<?php
//  header('Access-Control-Allow-Origin: *');
//  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
//  header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once("funciones.php");
require dirname(__DIR__).'/vendor/autoload.php';


// require_once dirname(__DIR__)."/PHPMailer/PHPMailerAutoload.php";
// $email = $_REQUEST["email"];
// $idmovimiento = $_REQUEST["idmovimiento"];
// $codemp = $_REQUEST["codemp"];
// $codsuc = $_REQUEST["codsuc"];
// $nroinscripcion = $_REQUEST["nroinscripcion"];
// $codciclo = $_REQUEST["codciclo"];
// $tabla = $_REQUEST["tabla"];

$email = isset($argv[1]) ? $argv[1] : "";
$idmovimiento = isset($argv[2]) ? $argv[2] : "";
$codemp = isset($argv[3]) ? $argv[3] : "";
$codsuc = isset($argv[4]) ? $argv[4] : "";
$nroinscripcion = isset($argv[5]) ? $argv[5] : "";
$codciclo = isset($argv[6]) ? $argv[6] : "";
$tabla = isset($argv[7]) ? $argv[7] : "";
$check_pdf = isset($argv[8]) ? $argv[8] : "";
$check_xml= isset($argv[9]) ? $argv[9] : "";
$check_cdr= isset($argv[10]) ? $argv[10] : "";

$_REQUEST["email"] = $email;
$_REQUEST["idmovimiento"] = $idmovimiento;
$_REQUEST["codemp"] = $codemp;
$_REQUEST["codsuc"] = $codsuc;
$_REQUEST["nroinscripcion"] = $nroinscripcion;
$_REQUEST["codciclo"] = $codciclo;
$_REQUEST["tabla"] = $tabla;
$_REQUEST["check_pdf"] = $check_pdf;
$_REQUEST["check_xml"] = $check_xml;
$_REQUEST["check_cdr"] = $check_cdr;

$response = array();

$sql_empresa = "SELECT * FROM admin.empresas";
$empresa = $model->query($sql_empresa)->fetch();
$host_email = $empresa->host_email;
$email_emisor = $empresa->email;
$pass_emisor = $empresa->pass_email;
$port_emisor = $empresa->port_email;


// var_dump($_REQUEST["check_pdf"]);
// print_r($_REQUEST); exit;

$xml = "N";
$pdf = "N";
$cdr = "N";

if(isset($_REQUEST["check_pdf"]) && $_REQUEST["check_pdf"] == "on") {
    $pdf = "S";
}

if(isset($_REQUEST["check_xml"]) && $_REQUEST["check_xml"] == "on") {
    $xml = "S";
}

if(isset($_REQUEST["check_cdr"]) && $_REQUEST["check_cdr"] == "on") {
    $cdr = "S";
}

if(empty($host_email) || $host_email == NULL) {
    $response["mensaje"] = "No existe el Host del Correo";
    $response["res"]     = 2;
    //echo $res . "|" . $mensaje;
    echo json_encode($response);
    exit;
}   


if(empty($email_emisor) || $email_emisor == NULL) {
    $response["mensaje"] = "No existe un Correo Emisor";
    $response["res"]     = 2;
    //echo $res . "|" . $mensaje;
    echo json_encode($response);
    exit;
}   

$sql_comprobante = "SELECT 
CASE WHEN documento_nombre IS NULL OR documento_nombre = '' THEN '".$empresa->ruc."' || '-' || codsunat || '-' || serie || '-' || nrodocumentotri ELSE documento_nombre END AS documento_nombre,
CASE WHEN codsunat = '01' THEN 'Factura' ELSE 'Boleta de Venta' END AS tipodoc_descripcion,
documento_nombre_xml, documento_nombre_cdr,
razonsocial,
serie,
nrodocumentotri,
codsunat
FROM cpe.vista_documentos_electronicos WHERE idmovimiento={$idmovimiento} AND codemp={$codemp} AND codsuc={$codsuc} AND nroinscripcion={$nroinscripcion} AND codciclo={$codciclo} AND tabla='{$tabla}'";

$comprobante = $model->query($sql_comprobante)->fetch();

if(empty($pass_emisor) || $email_emisor == NULL) {
    $response["mensaje"]  = "No existe Contraseña del Correo Emisor";
    $response["res"]     = 2;
    //echo $res . "|" . $mensaje;
    echo json_encode($response);
    exit;
}

if(empty($port_emisor) || $port_emisor == NULL) {
    $response["mensaje"]  = "No existe el Puerto para el envio del Correo";
    $response["res"]     = 2;
    //echo $res . "|" . $mensaje;
    echo json_encode($response);
    exit;
}

if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

    $mail = new PHPMailer(true);

    try {
        $mail->SMTPDebug  = SMTP::DEBUG_OFF; // SMTP::DEBUG_OFF: No output, SMTP::DEBUG_SERVER: Client and server messages
        $mail->isSMTP();
        $mail->Host       = $host_email;
        $mail->SMTPAuth   = true;   
        $mail->Username = $email_emisor;
        $mail->Password = $pass_emisor;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;  // TLS: ENCRYPTION_STARTTLS, SSL: ENCRYPTION_SMTPS
        $mail->Port       = $port_emisor; // si no quiere con el puerto 25 poner el puerto 587, al parecer en produccion va el puerto 587 y en desarollo el puerto 25,
        //o sino la mejor opcion es con SMTPSecure='ssl' y el puerto 665

    
        $mail->setFrom(utf8_decode($email_emisor), utf8_decode($empresa->razonsocial));
        $mail->addAddress($email, $comprobante->razonsocial);
        $mail->Subject = utf8_decode($comprobante->tipodoc_descripcion." Electrónica, ".$comprobante->serie."-".$comprobante->nrodocumentotri);
        $mail->isHTML(true);
   

        $Contenido = "Estimado Cliente : " . $comprobante->razonsocial . " adjunto se remite los archivos correspondientes a su comprobante de pago de electrónico.";
        $Contenido .= "<br> <br> Atentamente: " . $empresa->razonsocial;

        $mail->Body = $Contenido;

        if($pdf == "S") {
            $_REQUEST["id"] = $idmovimiento;
            $pdf = crear_pdf();
            $pdf = $pdf->output();
            file_put_contents(dirname(__DIR__)."/PDF/".nombre_documento() . ".pdf", $pdf);
            //$pdf->stream(nombre_documento() . ".pdf", array("Attachment" => false));

            $mail->addAttachment(dirname(__DIR__)."/PDF/".nombre_documento() . ".pdf");
        }

        if($xml == "S") {
            if(empty($comprobante->documento_nombre_xml) || $comprobante->documento_nombre_xml == NULL ) {
                $nombre_xml = nombre_documento().".xml";
            } else {
                $nombre_xml = $comprobante->documento_nombre_xml;
            }

            //echo dirname(__DIR__)."/XML/".$nombre_xml; exit;
            if(!file_exists(dirname(__DIR__)."/XML/".$nombre_xml)) {
                $row = (object) $_REQUEST;
                crear_xml($row);
            
            }
            
            $mail->addAttachment(dirname(__DIR__)."/XML/".$nombre_xml);
        }

        if($cdr == "S") {
            if(empty($comprobante->documento_nombre_cdr) || $comprobante->documento_nombre_cdr == NULL ) {
                $nombre_cdr = "R-".nombre_documento().".zip";
            } else {
                $nombre_cdr = $comprobante->documento_nombre_cdr;
            }

            if(!file_exists(dirname(__DIR__)."/CDR/".$nombre_cdr)) {
            
                $cpe->consulta_cdr($empresa->ruc, $comprobante->codsunat, $comprobante->serie, $comprobante->correlativo, $nombre_cdr);
            }

            $mail->addAttachment(dirname(__DIR__)."/CDR/".$nombre_cdr);
        }
        

        // if (!$mail->send()) {
        //     $response["res"] = 2;
        //     $response["mensaje"] = "Message could not be sent.\nMailer Error: ". $mail->ErrorInfo;
        //     echo json_encode($response); exit;
        // }
        $mail->send();
        $response["res"] = 1;
        $response["mensaje"] = "El Correo se Envió Correctamente";
        echo json_encode($response);
    } catch (Exception $e) {
        $response["res"] = 2;
        $response["mensaje"] = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        echo json_encode($response);
    }
    

}



