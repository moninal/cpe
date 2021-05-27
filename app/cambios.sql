delete from cpe.detalle_resumen;
delete from cpe.resumenes_diarios;
delete from cpe.detalle_baja;
delete from cpe.comunicacion_baja;
delete from cpe.documentos;

--- 06/05/2021
COMMENT ON COLUMN "cobranza"."cabpagos"."fe_facturado" IS '0 -> no emitido en sunat, es decir aun no ha sido aceptado el comprobante en sunat
1 -> emitido en sunat, es decir ya encuentra aceptado el comprobante en sunat';


COMMENT ON COLUMN "cobranza"."cabprepagos"."fe_facturado" IS '0 -> no emitido en sunat, es decir aun no ha sido aceptado el comprobante en sunat
1 -> emitido en sunat, es decir ya encuentra aceptado el comprobante en sunat';