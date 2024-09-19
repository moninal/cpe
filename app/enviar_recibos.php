<?php
//  header('Access-Control-Allow-Origin: *');
//  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
//  header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// require_once("funciones.php");
require dirname(__DIR__).'/vendor/autoload.php';
$email = isset($argv[1]) ? $argv[1] : "";
$nroinscripcion = isset($argv[2]) ? $argv[2] : "";
$rutaarchivo = isset($argv[3]) ? strtr($argv[3],"__"," ") : "";
$rutaarchivo = strtr($rutaarchivo, "_"," ");
$nombrecliente = isset($argv[4]) ? strtr($argv[4], "__", " ") : "";
$nombrecliente = strtr($nombrecliente, "_"," "); 

$response = array();

// $sql_empresa = "SELECT * FROM admin.empresas";
// $empresa = $model->query($sql_empresa)->fetch();
// $host_email = "fdfdlfjdlfj";
// $email_emisor = "fdfdlfjdlfj";
// $pass_emisor = "fdfdlfjdlfj";
// $port_emisor = "fdfdlfjdlfj";
$host_email = "mail.jjingenieros.pe";
$email_emisor = "jcarbajal@jjingenieros.pe";
$pass_emisor = "Sanchez75270586";
$port_emisor = "587";

 

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
        $mail->SMTPSecure = "tls";  // TLS: ENCRYPTION_STARTTLS, SSL: ENCRYPTION_SMTPS
        $mail->Port       = $port_emisor; // si no quiere con el puerto 25 poner el puerto 587, al parecer en produccion va el puerto 587 y en desarollo el puerto 25,
        //o sino la mejor opcion es con SMTPSecure='ssl' y el puerto 665

    
        $mail->setFrom(utf8_decode($email_emisor), utf8_decode("Recibo de Facturación"));
        $mail->addAddress($email, $nombrecliente);
        $mail->Subject = utf8_decode("RECIBO DE CONSUMO DE SERVICIO - EMAPICA");
        $mail->isHTML(true);


        $Contenido = "Estimado Cliente : " . $nombrecliente . ", en el adjunto se remite el recibo de su facturación del mes.";
        $Contenido .= "<br> <br> Atentamente: " . "EMAPICA S.A.";

        $mail->Body = $Contenido;   
            //$pdf->stream(nombre_documento() . ".pdf", array("Attachment" => false));

        $mail->addAttachment($rutaarchivo);

        
        

        // if (!$mail->send()) {
        //     $response["res"] = 2;
        //     $response["mensaje"] = "Message could not be sent.\nMailer Error: ". $mail->ErrorInfo;
        //     echo json_encode($response); exit;
        // }
        $mail->send();
        $response["res"] = 1;
        $response["mensaje"] = "El Correo se Envió Correctamente";
        return json_encode($response);
    } catch (Exception $e) {
        $response["res"] = 2;
        $response["mensaje"] = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        echo json_encode($response);
    }
    

}



