<?php 
    //ini_set("display_errors", 1);
    //PARA FACTURAS Y BOLETAS


    require_once("NumerosEnLetras.php");
    
    if($_REQUEST["tipodoc_id"] == "01" || $_REQUEST["tipodoc_id"] == "03" ) {
        $TipoDocumento =  $_REQUEST["Venta"]["tipodoc_descripcion"];
        $Documento =  $_REQUEST["Venta"]["venta_documento"];
        $Cliente =  utf8_decode($_REQUEST["Venta"]["cliente_nombres"]);
        $NroDocumentoI =  $_REQUEST["Venta"]["cliente_numero_documento"];
        $Fecha =  $_REQUEST["Venta"]["venta_fecha"];
        //$Hora =  $_REQUEST["Venta"]["venta_hora"];

        $Direccion =  (isset($_REQUEST["Venta"]["cliente_direccion"])) ? $_REQUEST["Venta"]["cliente_direccion"] : "";

        $Total =  number_format($_REQUEST["Venta"]["venta_total"],2);
        $valor_venta =  number_format($_REQUEST["Venta"]["valor_venta"],2);
        $FormaPago =  $_REQUEST["Venta"]["fp_descripcion"];
        $Moneda =  $_REQUEST["Venta"]["moneda_descripcion"];
        $igv_status =  $_REQUEST["Venta"]["igv_status"];
        $subtotal =  number_format($_REQUEST["Venta"]["subtotal"],2);
        $total_igv =  number_format($_REQUEST["Venta"]["igv"],2);
        $redondeo =  number_format($_REQUEST["Venta"]["redondeo"],2);
        $porcentaje_igv =  number_format($_REQUEST["Venta"]["porcentaje_igv"],2);

       
        $AltoCliente = "95px";
       
		
		
    }

    //PARA NOTAS CREDITO Y DEBITO
    // if($_REQUEST["tipodoc_id"] == "07" || $_REQUEST["tipodoc_id"] == "08") {
    //     $TipoDocumento = $_REQUEST["Nota"]["tipodoc_descripcion"];
    //     $NroDocumentoI =  $_REQUEST["Nota"]["cliente_numero_documento"];
    //     $Documento =  $_REQUEST["Nota"]["nota_documento"];
    //     $Cliente =  $_REQUEST["Nota"]["cliente_nombres"];
    //     $Fecha =  $_REQUEST["Nota"]["nota_fecha"];
    //     $Hora =  $_REQUEST["Nota"]["nota_hora"];
    //     $Direccion =  $_REQUEST["Nota"]["cliente_direccion"];
    //     $Total =  $_REQUEST["Nota"]["nota_total"];
    //     $Moneda =  $_REQUEST["Nota"]["moneda_descripcion"];
    //     $FormaPago =  $_REQUEST["Nota"]["fp_descripcion"];
    //     $DocumentoReferencia =  $_REQUEST["Nota"]["nota_documento_referencia"];
    //     $Motivo =  $_REQUEST["Nota"]["motivo_descripcion"];
    //     $Observacion =  $_REQUEST["Nota"]["nota_observacion"];
    //     $AltoCliente = "115px";
    //     $ICBPER = 0;
		
		
    // }


?> 
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo $TipoDocumento; ?> ELECTR&Oacute;NICA</title>

    <!-- <link href="https://fonts.googleapis.com/css?family=Fjalla+One&display=swap" rel="stylesheet"> -->
    <link href="https://fonts.googleapis.com/css?family=Doppio+One&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Roboto&display=swap" rel="stylesheet">

    <style>

        * {
            font-family: 'Roboto', sans-serif;
            font-size: 12px;
			
            /* line-height: 15px; */
        }
		

        #contenido {
            width: 704px;
            /* border: 1px solid #494949 */
					
        }

        #logo img {
		
            width: 100%;
        }

        #Empresa, #documento {
            padding: 1%;
        }

        #cliente {
            border-radius: 5px; 
            border: 1px solid #494949; 
            padding: 0.5% 1%;
            /* height: 115px; */
            /* line-height: 15px; */

        }

        #detalle {
            border-radius: 5px; 
            border: 1px solid #494949; 
            padding: 0.5% 1%;
            /* height: 460px; */
		
        }

        #total_letras {
            border-radius: 5px; 
            border: 1px solid #494949; 
            padding: 1%;
        }

        #pie {
            border-radius: 5px; 
            border: 1px solid #494949; 
            padding: 1%;
            text-align: center;
        }

        #totales {
            border-radius: 5px; 
            border: 1px solid #494949; 
            padding: 1%;
            height: 180px;
        }
	
        .enfasis {
            /* font-family: 'Patua One', cursive; */
            /* font-family: 'Cuprum', sans-serif */
            /* font-family: 'Oswald', sans-serif; */
            font-size: 13px;
            /* font-family: 'Fjalla One', sans-serif; */
            /* letter-spacing: 1px; */
            /* margin-top: -1px; */
            font-family: 'Doppio One', sans-serif;
			
        }

        .row {
            width: 100%;
            /* margin-top: 15px; */
            /* clear: both; */
        }
        .clear {
            clear: both; 
        }
        .col {
            float: left;
        }

        h2, h3, h4, h5 {
            /* text-align: center !important; */
            margin: 2px 0;
            /* padding-botton: 2px; */
			
        }
		
        /* strong {
            font-weight: 600;
        } */
    </style>
</head>
<body>
    <div id="contenido">
        <div id="cabecera">
            <div class="row">
                <div id="logo" class="col" style="width: 25%; padding-right: 5px; border-right: 1px solid black;">
                    <img height="90"  src="http://localhost:9000/cpe/logos/<?php echo utf8_decode($_REQUEST["Empresa"]["empresa_logo"]); ?>" alt="">
                </div>
                <div id="Empresa" class="col" style="width: 42%;">
                    <div class="enfasis"><?php echo utf8_decode($_REQUEST["Empresa"]["empresa_razonsocial"]); ?></div>
                    <?php echo utf8_decode($_REQUEST["Empresa"]["empresa_direccion"]); ?><br>
                    <?php echo "EMAIL: ".utf8_decode($_REQUEST["Empresa"]["empresa_email"]); ?><br>
                    <?php echo "TELF: ".$_REQUEST["Empresa"]["empresa_telefonos"]; ?>
                </div>
                <div id="documento" class="col" style="width:28%; border-radius: 5px; border: 1px solid #494949">
                    <div class="enfasis" style="text-align: center !important;">R.U.C.: <?php echo $_REQUEST["Empresa"]["empresa_ruc"]; ?></div>
                    <div class="enfasis" style="text-align: center !important;"><?php echo $TipoDocumento; ?> ELECTR&Oacute;NICA</div>
                    <div class="enfasis" style="text-align: center !important;">N&deg; <?php echo $Documento; ?></div>
                </div>
            </div>
        </div>
        <div class="clear"></div>
        <div style="height: 10px;"></div>
        <div id="cliente" style="height: <?php echo $AltoCliente; ?>">
            <div class="row" >
				
                <div class="col enfasis" style="width:15%;">
                   
                    N&deg; DNI/RUC
                </div>
                <div class="col enfasis" style="width:2%;">
                    :
                </div>
                <div class="col"  style="width:33%;">
                    <?php echo $NroDocumentoI; ?>
					
                </div>
                <div class="col"  style="width:25%; text-align: right;">
                    <span class="enfasis">Fecha Emisi&oacute;n : </span> <?php echo $Fecha; ?>
                </div>
                <!-- <div class="col"  style="width:25%; text-align: right;">
                    <span class="enfasis">Hora Emisi&oacute;n : </span> <?php echo $Hora; ?>
                </div> -->
            </div>
            <div class="clear"></div>
            <div class="row" >
            <div class="col enfasis" style="width:15%;">
                    Cliente
                </div>
                <div class="col enfasis" style="width:2%;">
                    :
                </div>
                <div class="col"  style="width:83%;">
                    <?php echo $Cliente; ?>
                </div>
				
            </div>
            <div class="clear"></div>
            <div class="row" >
                <div class="col enfasis" style="width:15%;">
                    Direcci&oacute;n
                </div>
                <div class="col enfasis" style="width:2%;">
                    :
                </div>
                <div class="col"  style="width:83%;">
                    <?php echo $Direccion; ?>
                </div>
				
            </div>
            <div class="clear"></div>
            <div class="row" >
                <div class="col enfasis" style="width:15%;">
                    Forma de Pago
                </div>
                <div class="col enfasis" style="width:2%;">
                    :
                </div>
                <div class="col"  style="width:14%;">
                    <?php echo $FormaPago; ?>
                </div>
              
                <div class="col enfasis" style="width:8%;">
                    Moneda
                </div>
                <div class="col enfasis" style="width:2%;">
                    :
                </div>
                <div class="col"  style="width:10%; text-align: right;">
                    <?php echo $Moneda; ?>
                </div>
            </div>
            <div class="clear"></div>
            <?php if($_REQUEST["tipodoc_id"] == "07" || $_REQUEST["tipodoc_id"] == "08") { ?>
                <div class="row">
                    <div class="col enfasis" style="width:15%;">
                        Doc. Referencia
                    </div>
                    <div class="col enfasis" style="width:2%;">
                        :
                    </div>
                    <div class="col"  style="width:14%;">
                        <?php echo $DocumentoReferencia; ?>
                    </div>
			
                    <div class="col enfasis" style="width:8%;">
                        Motivo
                    </div>
                    <div class="col enfasis" style="width:2%;">
                        :
                    </div>
                    <div class="col"  style="width:22%;">
                        <?php echo $Motivo; ?>
                    </div>
				
                    <div class="col enfasis" style="width:12%;">
                        Descripci&oacute;n
                    </div>
                    <div class="col enfasis" style="width:2%;">
                        :
                    </div>
                    <div class="col"  style="width:23%;">
                        <?php echo $Observacion; ?>
                    </div>
                </div>
                <div class="clear"></div>
            <?php } ?>
        </div>
		
        <div style="height: 5px;"></div>
		
        <?php if($_REQUEST["tipodoc_id"] == "01" || $_REQUEST["tipodoc_id"] == "03") { ?>
            <div id="detalle" style="height: 470px;">
                <div class="row">
                    <div class="col enfasis" style="width: 5%; border-bottom: 1px solid #494949;">Item</div>
                    <div class="col enfasis" style="width: 10%; border-bottom: 1px solid #494949;">C&oacute;digo</div>
                    <div class="col enfasis" style="width: 35%; border-bottom: 1px solid #494949;">Descripci&oacute;n</div>
                    <div class="col enfasis" style="width: 5%; border-bottom: 1px solid #494949;text-align: right;">U. M.</div>
                    <div class="col enfasis" style="width: 10%; border-bottom: 1px solid #494949;text-align: right;">Cantidad</div>
                    <div class="col enfasis" style="width: 10%; border-bottom: 1px solid #494949;text-align: right;">Precio U.</div>
                    <div class="col enfasis" style="width: 8%; border-bottom: 1px solid #494949;text-align: right;">IGV</div>
                    <div class="col enfasis" style="width: 8%; border-bottom: 1px solid #494949;text-align: right;">Precio</div>
                    <div class="col enfasis" style="width: 9%; border-bottom: 1px solid #494949;text-align: right;">Importe</div>
                </div>
                <div class="clear"></div>
                <?php 
                    $item = 1;
                    foreach ($_REQUEST["DetalleVenta"] as $key => $value) {
                        // print_r($value); exit;
                        $importe = $value["cantidad"]*$value["precio_unitario"];
                        $importe = number_format(round($importe, 2), 2);
                        $cantidad = number_format(round($value["cantidad"], 2), 2);
                        $igv = number_format($value["igv"], 2);
                        $precio_unitario = number_format($value["precio_unitario"], 2);
                        $valor_unitario = number_format($value["valor_unitario"], 2);
                        echo '<div class="row">
                                <div class="col" style="width: 5%;">'.$item.'</div>
                                <div class="col" style="width: 10%;">'.$value["codproducto"].'</div>
                                <div class="col" style="width: 35%;">'.utf8_decode($value["producto"]).'</div>
                                <div class="col" style="width: 5%; text-align: right;">'.$value["codunidad"].'</div>
                                <div class="col" style="width: 10%; text-align: right;">'.$cantidad.'</div>
                                <div class="col" style="width: 10%; text-align: right;">'.$valor_unitario.'</div>
                                <div class="col" style="width: 8%; text-align: right;">'.$igv.'</div>
                                <div class="col" style="width: 8%; text-align: right;">'.$precio_unitario.'</div>
                                <div class="col" style="width: 9%; text-align: right;">'.$importe.'</div>
                            </div>
                            <div class="clear"></div>';
                        $item++;
                    }

                ?>
            </div>
		
			
			
        <?php } ?>
        <?php if($_REQUEST["tipodoc_id"] == "07" || $_REQUEST["tipodoc_id"] == "08") { ?>
            <div id="detalle" style="height: 460px;">
                <div class="row">
                    <div class="col enfasis" style="width: 50%; border-bottom: 1px solid #494949;">Producto</div>
                    <div class="col enfasis" style="width: 20%; border-bottom: 1px solid #494949;">Unidad Medida</div>
                    <div class="col enfasis" style="width: 10%; border-bottom: 1px solid #494949;">Cantidad</div>
                    <div class="col enfasis" style="width: 10%; border-bottom: 1px solid #494949;">Precio</div>
                    <div class="col enfasis" style="width: 10%; border-bottom: 1px solid #494949;">Importe</div>
                </div>
                <div class="clear"></div>
                <?php 
                    foreach ($_REQUEST["DetalleNota"] as $key => $value) {
                        $importe = $value["dn_cantidad"]*$value["dn_precio"];
                        $importe = number_format(round($importe, 2), 2);
                        $cantidad = number_format(round($value["dn_cantidad"], 2), 2);
                        echo '<div class="row">
                                <div class="col" style="width: 50%;">'.$value["producto_descripcion"].'</div>
                                <div class="col" style="width: 20%;">'.$value["um_id"].'</div>
                                <div class="col" style="width: 10%; text-align: right;">'.$cantidad.'</div>
                                <div class="col" style="width: 10%; text-align: right;">'.$value["dn_precio"].'</div>
                                <div class="col" style="width: 10%; text-align: right;">'.$importe.'</div>
                            </div>
                            <div class="clear"></div>';
                    }

                ?>
            </div>
		
			
			
        <?php } ?>
        <div style="height: 5px;"></div>
        <div id="total_letras">
            <div class="row enfasis">
                <?php echo "SON: ".NumerosEnLetras::convertir(str_replace(",","",$Total)); ?>
            </div>
        </div>
        <div style="height: 5px;"></div>
        <div id="totales">
            <div class="row">
                <div class="col" style="width: 50%; height: 180px; border-right: 1px solid #494949;">
				
                    <img style="width: 53%; margin: 0 auto;" src="http://localhost:9000/cpe//QR/<?php echo $_REQUEST["nombre_documento"].".png"; ?>" alt="">
					
                </div>
                <div class="col" style="width: 47%; height: 180px; padding: 1%, 2%; line-height: 13px;" >
					
                    <table style="width: 100%;">
                        <tr >
                            <td class="enfasis" style="width: 68%;">Total Op. Gravadas</td>
                            <td class="enfasis" style="width: 2%;">S/.</td>
                            <td class="" style="width: 30%; text-align: right;"><?php echo ($igv_status == "S") ? $valor_venta : "0.00"; ?></td>
                        </tr>
                        <tr >
                            <td class="enfasis" style="width: 68%;">Total Op. Exoneradas</td>
                            <td class="enfasis" style="width: 2%;">S/.</td>
                            <td class="" style="width: 30%; text-align: right;"><?php echo ($igv_status == "N") ? $valor_venta : "0.00"; ?></td>
                        </tr>
                        <tr >
                            <td class="enfasis" style="width: 68%;">Total Op. Inafectas</td>
                            <td class="enfasis" style="width: 2%;">S/.</td>
                            <td class="" style="width: 30%; text-align: right;">0.00</td>
                        </tr>
                        
                        <tr >
                            <td class="enfasis" style="width: 68%;">Total Op. Gratuitas</td>
                            <td class="enfasis" style="width: 2%;">S/.</td>
                            <td class="" style="width: 30%; text-align: right;">0.00</td>
                        </tr>
                        <tr >
                            <td class="enfasis" style="width: 68%;">Total IGV ( <?php echo $porcentaje_igv."%"; ?> )</td>
                            <td class="enfasis" style="width: 2%;">S/.</td>
                            <td class="" style="width: 30%; text-align: right;"><?php echo $total_igv; ?></td>
                        </tr>
                        <!-- <tr >
                            <td class="enfasis" style="width: 68%;">ICBPER</td>
                            <td class="enfasis" style="width: 2%;">S/.</td>
                            <td class="" style="width: 30%; text-align: right;"><?php echo $ICBPER; ?></td>
                        </tr> -->
                        <tr >
                            <td class="enfasis" style="width: 68%;">Subtotal</td>
                            <td class="enfasis" style="width: 2%;">S/.</td>
                            <td class="" style="width: 30%; text-align: right;"><?php echo $subtotal; ?></td>
                        </tr>
                        <tr >
                            <td class="enfasis" style="width: 68%;">Redondeo</td>
                            <td class="enfasis" style="width: 2%;">S/.</td>
                            <td class="" style="width: 30%; text-align: right;"><?php echo $redondeo; ?></td>
                        </tr>
                        <tr >
                            <td class="enfasis" style="width: 68%;">Total</td>
                            <td class="enfasis" style="width: 2%;">S/.</td>
                            <td class="" style="width: 30%; text-align: right;"><?php echo $Total; ?></td>
                        </tr>
                    </table>
		
                </div>
            </div>
			
        </div>
        <!-- <div class="clear"></div> -->
        <div style="height: 5px;"></div>
        <div id="pie">
            <div class="row enfasis">
                REPRESENTACION IMPRESA DE LA <?php echo $TipoDocumento; ?> ELECTR&Oacute;NICA, ESTA PUEDE SER CONSULTADA EN: <?php echo $_REQUEST["Empresa"]["link_consulta"]; ?>
            </div>
        </div>
    </div>
	
   
</body>
</html>
