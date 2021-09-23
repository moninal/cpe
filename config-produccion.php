<?php
       
    $Servidor = "localhost";
    $Puerto   = "5432";
    $Usuario  = "postgres"; 
    $Password = "1235";
    $Base     = "emapica_20210916_1931";
    // $endpoint = "https://oselab.todasmisfacturas.com.pe/ol-ti-itcpfegem/billservice?wsdl";
    $endpoint = "";
    $codemp = 1;

    // $Servidor = "26.178.139.59";
    // $Puerto   = "5432";
    // $Usuario  = "postgres"; 
    // $Password = "Admin2021@++";
    // $Base     = "e5w_epsilo_facturar";
    try {
        $conexion = new PDO("pgsql:dbname=$Base;port=$Puerto;host=$Servidor", $Usuario, $Password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));
    }
    catch (PDOException $e) {
        die(utf8_decode('Fallo la conexion Postgres ').$e->getMessage());
    }
    
?>