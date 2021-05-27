<?php
       
    $Servidor = "localhost";
    $Puerto   = "5432";
    $Usuario  = "postgres"; 
    $Password = "1235";
    $Base     = "emapica_20210331_1945";

    try {
        $conexion = new PDO("pgsql:dbname=$Base;port=$Puerto;host=$Servidor", $Usuario, $Password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));
    }
    catch (PDOException $e) {
        die(utf8_decode('Fallo la conexion Postgres ').$e->getMessage());
    }
    
?>