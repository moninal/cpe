-- vista_documentos_electronicos

SELECT 
M.nropago AS idmovimiento,
suc.descripcion AS sucursal,
M.fechareg AS documentofecha,
co.abreviado || ' ' || M.serie || '-' || btrim( to_char( M.nrodocumentotri, '00000000' ) ) AS doc_sun,
M.propietario AS razonsocial,
ROUND( M.imptotal, 2 ) AS total,
CASE WHEN M.anulado = 1 THEN 'ANULADO' ELSE'PAGADO' END AS condicion,
M.anulado AS estado,
M.coddocumento,
M.serie,
btrim( to_char( M.nrodocumentotri, '00000000' ) ) AS nrodocumentotri,
co.codsunat,
1 AS origen,
suc.codsuc,
M.nroinscripcion,
/*CASE
WHEN doc.documento_success = 1 THEN 'EMITIDO'
WHEN doc.documento_success = 0 THEN 'RECHAZADO'
WHEN doc.documento_success IS NULL THEN 'NO EMITIDO' END AS estado_cpe,*/
doc.documento_cdr_response,

m.codciclo,
m.subtotal AS subtotal,
m.igv AS igv,
m.imptotal AS imptotal,
m.redondeo,
CASE 
WHEN cl.codtipodocumento = 1 THEN 1
WHEN cl.codtipodocumento = 2 THEN 4
WHEN cl.codtipodocumento = 3 THEN 6 
ELSE 0 END AS tdi_id,
CASE WHEN cl.nrodocumento IS NULL OR cl.nrodocumento = '' THEN '00000000' ELSE  cl.nrodocumento END AS cliente_numero_documento,
'cobranza.cabpagos' AS tabla,
m.codemp,
CASE 
WHEN doc.documento_code=0 THEN 'ACEPTADO' 
WHEN doc.documento_code >= 2000 AND doc.documento_code <=3999 THEN  'RECHAZADO'
ELSE 'PENDIENTE' END AS estado_cpe,
doc.documento_observaciones,
/*CASE 
WHEN doc.documento_success=1 THEN 'RESUELTO' 
WHEN doc.documento_success=0 THEN 'ENVIADO' 
ELSE 'PENDIENTE'
END AS documento_success,*/
cl.direcciondistribucion,

m.nrodocumento,
m.direccion,
doc.documento_nombre_xml,
doc.documento_nombre_cdr,
doc.documento_id,
doc.documento_estado ,
doc.estado AS estado_documento,
doc.documento_nombre
FROM cobranza.cabpagos M
INNER JOIN reglasnegocio.documentos co ON ( M.coddocumento = co.coddocumento AND M.codsuc = co.codsuc )
INNER JOIN ADMIN.sucursales suc ON ( suc.codemp = M.codemp AND M.codsuc = suc.codsuc ) 
INNER JOIN catastro.clientes AS cl ON(cl.codemp=m.codemp AND cl.codsuc=m.codsuc AND cl.nroinscripcion=m.nroinscripcion)
LEFT JOIN cpe.documentos AS doc ON (doc.codemp=m.codemp AND doc.codsuc=m.codsuc AND doc.nrooperacion=m.nropago AND doc.codciclo=m.codciclo AND doc.nroinscripcion=m.nroinscripcion AND doc.tabla='cobranza.cabpagos')
WHERE co.codsunat IS NOT NULL AND M.coddocumento IN ( 13, 14 )

UNION ALL

SELECT 
M.nroprepago AS idmovimiento,
suc.descripcion AS sucursal,
M.fechareg AS documentofecha,
co.abreviado || ' ' || md.serie || '-' || btrim( to_char( md.nrodocumento, '00000000' ) ) AS doc_sun,
M.propietario AS razonsocial,
ROUND( SUM ( md.importe ), 2 ) AS total,
CASE
WHEN M.estado = 0 THEN
'ANULADO' 
WHEN M.estado = 1 THEN
'PENDIENTE' 
WHEN M.estado = 2 THEN
'CANCELADO' 
WHEN M.estado = 3 THEN
'CREDITO' 
WHEN M.estado = 4 THEN
'REFINANCIAMIENTO' 
END AS condicion,
CASE WHEN M.estado = 0 THEN 1 ELSE 0 END AS estado,
md.coddocumento,
md.serie,
btrim( to_char( md.nrodocumento, '00000000' ) ) AS nrodocumentotri,
co.codsunat,
2 AS origen,
suc.codsuc,
M.nroinscripcion,
/*CASE
WHEN doc.documento_success = 1 THEN 'EMITIDO'
WHEN doc.documento_success = 0 THEN 'RECHAZADO'
WHEN doc.documento_success IS NULL THEN 'NO EMITIDO' END AS estado_cpe,*/
doc.documento_cdr_response,

m.codciclo,
SUM(m.subtotal) AS subtotal,
SUM(m.igv) AS igv,
SUM(m.imptotal) AS imptotal,
SUM(m.redondeo) AS redondeo,
CASE 
WHEN cl.codtipodocumento = 1 THEN 1
WHEN cl.codtipodocumento = 2 THEN 4
WHEN cl.codtipodocumento = 3 THEN 6 
ELSE 0 END AS tdi_id,
CASE WHEN cl.nrodocumento IS NULL OR cl.nrodocumento = '' THEN '00000000' ELSE  cl.nrodocumento END AS cliente_numero_documento,
'cobranza.cabprepagos' AS tabla,
m.codemp,
CASE 
WHEN doc.documento_code=0 THEN 'ACEPTADO' 
WHEN doc.documento_code >= 2000 AND doc.documento_code <=3999 THEN  'RECHAZADO'
ELSE 'PENDIENTE' END AS estado_cpe,
doc.documento_observaciones,
/*CASE 
WHEN doc.documento_success=1 THEN 'RESUELTO' 
WHEN doc.documento_success=0 THEN 'ENVIADO' 
ELSE 'PENDIENTE'
END AS documento_success,*/
cl.direcciondistribucion,

m.documento AS nrodocumento,
m.direccion,
doc.documento_nombre_xml,
doc.documento_nombre_cdr,
doc.documento_id,
doc.documento_estado ,
doc.estado AS estado_documento,
doc.documento_nombre
FROM cobranza.cabprepagos M 
INNER JOIN cobranza.detprepagos md ON ( M.codemp = md.codemp AND M.codsuc = md.codsuc AND M.nroprepago = md.nroprepago AND M.nroinscripcion = md.nroinscripcion AND M.codzona = md.codzona )
INNER JOIN reglasnegocio.documentos co ON ( md.coddocumento = co.coddocumento AND M.codsuc = co.codsuc )
INNER JOIN ADMIN.sucursales suc ON ( suc.codemp = M.codemp AND M.codsuc = suc.codsuc )
INNER JOIN catastro.clientes AS cl ON(cl.codemp=m.codemp AND cl.codsuc=m.codsuc AND cl.nroinscripcion=m.nroinscripcion)
LEFT JOIN cpe.documentos AS doc ON (doc.codemp=m.codemp AND doc.codsuc=m.codsuc AND doc.nrooperacion=m.nroprepago AND doc.codciclo=m.codciclo AND doc.nroinscripcion=m.nroinscripcion AND doc.tabla='cobranza.cabprepagos')

LEFT JOIN ( SELECT c2.serie, c2.nrodocumentotri, c2.codsuc, c2.codemp, c2.nroinscripcion, c2.codzona 
FROM cobranza.cabpagos c2 
WHERE c2.coddocumento IN ( 13, 14 ) ) AS ccab ON ( ccab.serie = md.serie AND ccab.nrodocumentotri = md.nrodocumento AND ccab.codsuc = M.codsuc AND ccab.codemp = M.codemp AND ccab.codzona = M.codzona AND ccab.nroinscripcion = M.nroinscripcion ) 
WHERE co.codsunat IS NOT NULL AND ccab.nrodocumentotri IS NULL AND md.coddocumento IN ( 13, 14 ) 
GROUP BY
M.nroprepago,
M.fechareg,
co.abreviado,
md.serie,
md.nrodocumento,

M.estado,
co.codsunat,
md.coddocumento,
suc.codsuc,
M.nroinscripcion,
suc.descripcion,
doc.documento_cdr_response,

m.codciclo,
cl.codtipodocumento,
cl.nrodocumento,
m.codemp,
doc.documento_code,
doc.documento_observaciones,
cl.direcciondistribucion,
m.propietario,
m.documento,
m.direccion,
doc.documento_nombre_xml,
doc.documento_nombre_cdr,
doc.documento_id,
doc.documento_estado,
doc.estado,
doc.documento_nombre
