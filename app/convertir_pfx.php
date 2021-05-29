<?php 
use Greenter\XMLSecLibs\Certificate\X509Certificate;
use Greenter\XMLSecLibs\Certificate\X509ContentType;
    
require dirname(__DIR__).'/vendor/autoload.php';

$nombre_certificado = isset($argv[1]) ? $argv[1] : "";
$password = isset($argv[2]) ? $argv[2] : "";

$pfx = file_get_contents(dirname(__DIR__).'/certificados/'.$nombre_certificado."pfx");

$certificate = new X509Certificate($pfx, $password);
$pem = $certificate->export(X509ContentType::PEM);
        
file_put_contents(dirname(__DIR__).'/certificados/'.$nombre_certificado.'pem', $pem);

echo "1";
?>