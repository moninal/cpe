<?php

use Greenter\Model\Client\Client;
use Greenter\Model\Company\Company;
use Greenter\Model\Company\Address;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\SaleDetail;
use Greenter\Model\Sale\Legend;

require __DIR__.'/vendor/autoload.php';

$see = require __DIR__.'/config.php';

// Cliente
$client = new Client();
$client->setTipoDoc('1')
    ->setNumDoc('01065945')
    ->setRznSocial('RICARDO ZARRIA BOCANEGRA');

// Emisor
$address = new Address();
$address->setUbigueo('220901')
    ->setDepartamento('SAN MARTIN')
    ->setProvincia('SAN MARTIN')
    ->setDistrito('TARAPOTO')
    ->setUrbanizacion('-')
    ->setDireccion('JR. LIMA NRO. 303');

$company = new Company();
$company->setRuc('20531588119')
    ->setRazonSocial('DETALLES ZAPATERIA E.I.R.L')
    ->setNombreComercial('-')
    ->setAddress($address);

// Venta
$invoice = (new Invoice())
    ->setUblVersion('2.1')
    ->setTipoOperacion('0101') // Catalog. 51
    ->setTipoDoc('03')
    ->setSerie('B020')
    ->setCorrelativo('1')
    ->setFechaEmision(new DateTime())
    ->setTipoMoneda('PEN')
    ->setClient($client)
    // ->setMtoOperGravadas(100.00)
    ->setMtoOperExoneradas(118.00) // sin igv
    ->setMtoIGV(0.00)
    ->setTotalImpuestos(0.00)
    ->setValorVenta(118)
    ->setSubTotal(118.00)
    ->setMtoImpVenta(118.00)
    ->setCompany($company);

$item = (new SaleDetail())
    ->setCodProducto('4010')
    ->setUnidad('ZZ')
    ->setCantidad(1)
    ->setDescripcion('FACTIBILIDAD DEL SERVICIO DE AGUA/DESAGUE')
    ->setMtoBaseIgv(0)
    ->setPorcentajeIgv(18.00) // 18%
    ->setIgv(0)
    ->setTipAfeIgv('20')
    ->setTotalImpuestos(0)
    ->setMtoValorVenta(118.00)
    ->setMtoValorUnitario(118.00)
    ->setMtoPrecioUnitario(118.00);

$legend = (new Legend())
    ->setCode('1000')
    ->setValue('SON CIENTO DIECIOCHO CON 00/100 SOLES');

$invoice->setDetails([$item])
        ->setLegends([$legend]);

$xml = $see->getXmlSigned($invoice);

// Guardar XML
file_put_contents($invoice->getName().'.xml', $xml);