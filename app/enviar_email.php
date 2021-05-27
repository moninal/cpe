<?php
 header('Access-Control-Allow-Origin: *');
 header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
 header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
 
require_once("funciones.php");
require_once "../PHPMailer/PHPMailerAutoload.php";
$email = $_REQUEST["email"];
$idmovimiento = $_REQUEST["idmovimiento"];
$codemp = $_REQUEST["codemp"];
$codsuc = $_REQUEST["codsuc"];
$nroinscripcion = $_REQUEST["nroinscripcion"];
$codciclo = $_REQUEST["codciclo"];
$tabla = $_REQUEST["tabla"];
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

    $mail = new PHPMailer;
    $mail->isSMTP();
    $mail->Mailer = 'smtp';
    $mail->SMTPDebug  = 0;
    $mail->SMTPAutoTLS = false;
    $mail->SMTPSecure = 'ssl'; //tls, ssl
    $mail->Host       = $host_email;
    $mail->Port       = $port_emisor; // si no quiere con el puerto 25 poner el puerto 587, al parecer en produccion va el puerto 587 y en desarollo el puerto 25,
    //o sino la mejor opcion es con SMTPSecure='ssl' y el puerto 665
    $mail->SMTPAuth = true;
    $mail->Username = $email_emisor;
    $mail->Password = $pass_emisor;

    $mail->setFrom(utf8_decode($email_emisor), utf8_decode($empresa->razonsocial));
    $mail->addAddress($email, $comprobante->razonsocial);
    $mail->Subject = utf8_decode($comprobante->tipodoc_descripcion." Electrónica, ".$comprobante->serie."-".$comprobante->nrodocumentotri);
    $mail->isHTML(true);
    //$mail->CharSet = "UTF-8";

    $Contenido = "Estimado Cliente : " . $comprobante->razonsocial . " adjunto se remite los archivos correspondientes a su comprobante de pago de electrónico.";
    $Contenido .= "<br> <br> Atentamente: " . $empresa->razonsocial;

    $mail->Body = $Contenido;

    if($pdf == "S") {
        $_REQUEST["id"] = $idmovimiento;
        $pdf = crear_pdf();
        $pdf = $pdf->output();
        file_put_contents("../PDF/".nombre_documento() . ".pdf", $pdf);
        //$pdf->stream(nombre_documento() . ".pdf", array("Attachment" => false));

        $mail->addAttachment("../PDF/".nombre_documento() . ".pdf");
    }

    if($xml == "S") {
        if(empty($comprobante->documento_nombre_xml) || $comprobante->documento_nombre_xml == NULL ) {
            $nombre_xml = nombre_documento().".xml";
        } else {
            $nombre_xml = $comprobante->documento_nombre_xml;
        }

        //echo "../XML/".$nombre_xml; exit;
        if(!file_exists("../XML/".$nombre_xml)) {
            $row = (object) $_REQUEST;
            crear_xml($row);
          
        }
        
        $mail->addAttachment("../XML/".$nombre_xml);
    }

    if($cdr == "S") {
        if(empty($comprobante->documento_nombre_cdr) || $comprobante->documento_nombre_cdr == NULL ) {
            $nombre_cdr = "R-".nombre_documento().".zip";
        } else {
            $nombre_cdr = $comprobante->documento_nombre_cdr;
        }

        if(!file_exists("../CDR/".$nombre_cdr)) {
           
            $cpe->consulta_cdr($empresa->ruc, $comprobante->codsunat, $comprobante->serie, $comprobante->correlativo, $nombre_cdr);
        }

        $mail->addAttachment("../CDR/".$nombre_cdr);
    }
    

    if (!$mail->send()) {
        $response["res"] = 2;
        $response["mensaje"] = "Message could not be sent.\nMailer Error: ". $mail->ErrorInfo;
        echo json_encode($response); exit;
    }

    $response["res"] = 1;
    $response["mensaje"] = "El Correo se Envió Correctamente";
    echo json_encode($response);

}



