<?php
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

    use Greenter\XMLSecLibs\Certificate\X509Certificate;
    use Greenter\XMLSecLibs\Certificate\X509ContentType;
    
    require '../vendor/autoload.php';

    if(!file_exists("../certificados/")) {
        mkdir("../certificados/", 0777);
    }
    
    $nombre_certificado = "";
    
    
    if (isset($_FILES["certificado"]) && !empty($_FILES["certificado"]["name"])) {
        $array_certificado  = explode(".", $_FILES["certificado"]["name"]);
        $ext_certificado    = $array_certificado[count($array_certificado) - 1];
        $_REQUEST["razon_social"] = str_replace(".", "_", $_REQUEST["razon_social"]).date("dmYHis");
        $nombre_certificado = strtolower(str_replace(" ", "_", $_REQUEST["razon_social"])).".".$ext_certificado;
        if($ext_certificado != "pfx" && $ext_certificado != "pem") {
            $mensaje = "La extensión del certificado debe ser .pfx o .pem";
            $res     = 2;
            echo $res . "|" . $mensaje;
            exit;
        }
        

        if (!move_uploaded_file($_FILES["certificado"]["tmp_name"], '../certificados/' . $nombre_certificado)) {
    
            $mensaje = "Error al cargar certificado ";
            $res     = 2;
            echo $res . "|" . $mensaje;
            exit;
        }
    
        if($ext_certificado == "pfx") {

            if(empty($_REQUEST["pass_certificado"])) {
                $mensaje = "Debe ingresar la contraseña del certificado para cargarlo. ";
                $res     = 2;
                echo $res . "|" . $mensaje;
                exit;
            }

            $pfx = file_get_contents('../certificados/'.$nombre_certificado);
            $password = $_REQUEST["pass_certificado"];
    
            $certificate = new X509Certificate($pfx, $password);
            $pem = $certificate->export(X509ContentType::PEM);
        
            file_put_contents('../certificados/'.strtolower(str_replace(" ", "_", $_REQUEST["razon_social"])).".pem", $pem);
        }

    }

    echo "1|".$nombre_certificado;
?>