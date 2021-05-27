<?php 
use Greenter\XMLSecLibs\Certificate\X509Certificate;
use Greenter\XMLSecLibs\Certificate\X509ContentType;
    
require '../vendor/autoload.php';
    
$pfx = file_get_contents('../certificados/DetallesZapateria.pfx');
$password = '99192932';
    
$certificate = new X509Certificate($pfx, $password);
$pem = $certificate->export(X509ContentType::PEM);
        
file_put_contents('../certificados/certificate_emapica.pem', $pem);
?>