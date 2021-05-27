<?php 
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

require_once "PHPMailerAutoload.php";

$codemp = 1;
$codsuc = $_POST["codsuc"];

$nroreclamoweb     = $_POST["nroreclamoweb"];
$nroinscripcion    = $_POST["nroinscripcion"];
$reclamante        = $_POST["reclamante"];
$telefono          = $_POST["telefono"];
$tiporeclamo       = $_POST["tiporeclamo"];
// $codconcepto       = $_POST["codconcepto"];
$glosa             = $_POST["glosa"];
$email             = $_POST['correo'];
$dni               = $_POST['dni'];
$codtipoparentesco = $_POST['codtipoparentesco'];
$anio              = date("Y");
$mes               = date("m");
$check_mes         = $_REQUEST["check_mes"];
$estado            = 1;

$Servidor = "localhost";////"localhost";//"192.168.1.39";
$Puerto   = "5432"; //"5432"; //"5434";
$Usuario  = "postgres"; //"corp"; //"postgres";
$Password = "@dmin$7391&"; //"@dmin$7391&"; //$CriptF;
$Base     = "ica"; //"corp_sicuani"; //"e-siincoweb_empssapal";
$gbd='Postgresql';

try {
    $conexion = & new PDO("pgsql:dbname=$Base;port=$Puerto;host=$Servidor", $Usuario, $Password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));
}
catch (PDOException $e) {
    die(utf8_decode('Fallo la conexion Postgres ').$e->getMessage());
}

$consulta = $conexion->prepare("SELECT e.razonsocial,s.direccion,s.descripcion,e.ruc,s.facturaalcantarillado , e.telefono , e.web_oficial, s.codubigeo
FROM admin.empresas as e
inner join admin.sucursales as s on(e.codemp=s.codemp)
WHERE s.codemp=1 and s.codsuc=?");
$consulta->execute(array($codsuc));
$empresa = $consulta->fetch();

$consulta2 = $conexion->prepare("SELECT * FROM reclamosweb.configuracion");
$consulta2->execute(array());
$configuracion = $consulta2->fetch();


// print_r($configuracion);


if (isset($_REQUEST["check_pdf"]) && $_REQUEST["check_pdf"] == "S") {
    if (isset($email)) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            
            $mail = new PHPMailer;
            $mail->isSMTP();
            //$mail->Mailer = 'smtp';
            $mail->SMTPDebug  = 0;
            $mail->SMTPSecure = 'ssl'; //tls, ssl
            $mail->Host       = $configuracion["correo_host"];
            $mail->Port       = $configuracion["correo_port"]; // si no quiere con el puerto 25 poner el puerto 587, al parecer en produccion va el puerto 587 y en desarollo el puerto 25,
            //o sino la mejor opcion es con SMTPSecure='ssl' y el puerto 665
            $mail->SMTPAuth = true;
            $mail->Username = $configuracion["correo_emisor"];
            $mail->Password = $configuracion["pass_emisor"];
            
            $mail->setFrom($configuracion["correo_emisor"], $empresa["razonsocial"]);
            $mail->addAddress($email, $reclamante);
            $mail->Subject = "Constancia Reclamo Online";
            $mail->isHTML(true);
            //$mail->CharSet = "UTF-8";
            
            $Contenido = "Estimado Cliente : " . $reclamante . " adjunto se remite copia de la solicitud de reclamo";
            $Contenido .= "<br> <br> Atentamente: " . $empresa["razonsocial"];
            
            $mail->Body = $Contenido;
            
            $archivo = "C:/Program Files (x86)/Apache Software Foundation/Apache2.2/htdocs/e-siincoweb/aguaica/reclamosweb/archivos/1-" . $codsuc . "-" . $nroreclamoweb . "-" . $nroinscripcion . ".pdf";
            
            if (file_exists($archivo)) {
                try {
                    $mail->addAttachment($archivo);
                } catch (Exception $e) {
                    echo "ERROR AL ADJUNTAR ARCHIVO AL MENSAJE DEL CORREO";
                }
            } else {
                echo "EL ARCHIVO NO EXISTE " . $archivo;
                // die();
            }
            
            if (!$mail->send()) {
                echo 'Message could not be sent.';
                echo 'Mailer Error: ' . $mail->ErrorInfo;exit;
            }
            
            // echo "ola";
            $mail = new PHPMailer;
            $mail->isSMTP();
            //$mail->Mailer = 'smtp';
            $mail->SMTPDebug  = 0;
            $mail->SMTPSecure = 'ssl'; //tls, ssl
            $mail->Host       = $configuracion["correo_host"];
            $mail->Port       = $configuracion["correo_port"]; // si no quiere con el puerto 25 poner el puerto 587, al parecer en produccion va el puerto 587 y en desarollo el puerto 25,
            //o sino la mejor opcion es con SMTPSecure='ssl' y el puerto 665
            $mail->SMTPAuth = true;
            $mail->Username = $configuracion["correo_emisor"];
            $mail->Password = $configuracion["pass_emisor"];
            
            $mail->setFrom($configuracion["correo_emisor"], $empresa["razonsocial"]);
            $mail->addAddress($configuracion["correo"], "Trabajador");
            $mail->Subject = "Constancia Reclamo Online";
            $mail->isHTML(true);
            //$mail->CharSet = "UTF-8";
            
            $Contenido = "Estimado Trabajador : adjunto se remite copia de una nueva solicitud de reclamo, del Reclamante: " . $reclamante;
            $Contenido .= "<br> <br> Atentamente: " . $empresa["razonsocial"];
            
            $mail->Body = $Contenido;
            
            $archivo = "C:/Program Files (x86)/Apache Software Foundation/Apache2.2/htdocs/e-siincoweb/aguaica/reclamosweb/archivos/1-" . $codsuc . "-" . $nroreclamoweb . "-" . $nroinscripcion . ".pdf";
            
            if (file_exists($archivo)) {
                try {
                    $mail->addAttachment($archivo);
                } catch (Exception $e) {
                    echo "ERROR AL ADJUNTAR ARCHIVO AL MENSAJE DEL CORREO";
                }
            } else {
                echo "EL ARCHIVO NO EXISTE " . $archivo;
                // die();
            }
            
            if (!$mail->send()) {
                echo 'Message could not be sent.';
                echo 'Mailer Error: ' . $mail->ErrorInfo;exit;
            }
            
        }
    }
}
?>