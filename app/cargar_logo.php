<?php
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');



    if(!file_exists("../logos/")) {
        mkdir("../logos/", 0777);
    }
    
    $nombre_logo = "";
    
    
    if (isset($_FILES["logo"]) && !empty($_FILES["logo"]["name"])) {
        $array_logo  = explode(".", $_FILES["logo"]["name"]);
        $ext_logo    = $array_logo[count($array_logo) - 1];
        $_REQUEST["razon_social"] = str_replace(".", "_", $_REQUEST["razon_social"]).date("dmYHis");
        $nombre_logo = strtolower(str_replace(" ", "_", $_REQUEST["razon_social"])).".".$ext_logo;
       
            
        if (!move_uploaded_file($_FILES["logo"]["tmp_name"], '../logos/' . $nombre_logo)) {
            $conexion->rollBack();
            // print_r($_FILES["logo"]);
            $mensaje = "Error al cargar logo ";
            $res     = 2;
            echo $res . "|" . $mensaje;
            exit;
        }
    
    }

    echo "1|".$nombre_logo;
?>