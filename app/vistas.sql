-- vista_documentos_electronicos


select
	m.nropago as idmovimiento,
	suc.descripcion as sucursal,
	m.fechareg as documentofecha,
	(((co.abreviado::text || ' '::text) || m.serie::text) || '-'::text) || btrim(to_char(m.nrodocumentotri, '00000000'::text)) as doc_sun,
	m.propietario as razonsocial,
	round(m.imptotal::numeric, 2) as total,
	case
		when m.anulado::numeric = 1::numeric then 'ANULADO'::text
		else 'PAGADO'::text
	end as condicion,
	m.anulado as estado,
	m.coddocumento,
	m.serie,
	btrim(to_char(m.nrodocumentotri, '00000000'::text)) as nrodocumentotri,
	co.codsunat,
	1 as origen,
	suc.codsuc,
	m.nroinscripcion,
	doc.documento_cdr_response,
	m.codciclo,
	m.subtotal,
	m.igv,
	m.imptotal,
	m.redondeo,
	case
		when cl.codtipodocumento::smallint = 1 then 1
		when cl.codtipodocumento::smallint = 2 then 4
		when cl.codtipodocumento::smallint = 3 then 6
		else 0
	end as tdi_id,
	case
		when cl.nrodocumento is null
		or cl.nrodocumento::text = ''::text then '00000000'::character varying
		else cl.nrodocumento::character varying
	end as cliente_numero_documento,
	'cobranza.cabpagos' as tabla,
	m.codemp,
	case
		when doc.documento_code = 0
		and doc.documento_estado = 'A'::bpchar then 'ACEPTADO'::text
		when doc.documento_code = 0
		and doc.documento_estado = 'I'::bpchar then 'ACEPTADO(BAJA)'::text
		when doc.documento_code = 98 then 'EN PROCESO'::text
		when doc.documento_code = 99 then 'PROCESADO CON ERRORES'::text
		when doc.documento_code >= 2000
		and doc.documento_code <= 3999 then 'RECHAZADO'::text
		else 'PENDIENTE'::text
	end as estado_cpe,
	doc.documento_observaciones,
	cl.direcciondistribucion,
	m.nrodocumento,
	m.direccion,
	doc.documento_nombre_xml,
	doc.documento_nombre_cdr,
	doc.documento_id,
	doc.documento_estado,
	doc.estado as estado_documento,
	doc.documento_nombre
from
	cobranza.cabpagos m
join reglasnegocio.documentos co on
	m.coddocumento::smallint = co.coddocumento::smallint
	and m.codsuc::smallint = co.codsuc
join admin.sucursales suc on
	suc.codemp::smallint = m.codemp::smallint
	and m.codsuc::smallint = suc.codsuc::smallint
left join catastro.clientes cl on
	cl.codemp::smallint = m.codemp::smallint
	and cl.codsuc::smallint = m.codsuc::smallint
	and cl.nroinscripcion = m.nroinscripcion
left join cpe.documentos doc on
	doc.codemp = m.codemp::smallint
	and doc.codsuc = m.codsuc::smallint
	and doc.nrooperacion = m.nropago
	and doc.codciclo = m.codciclo
	and doc.nroinscripcion = m.nroinscripcion
	and doc.tabla::text = 'cobranza.cabpagos'::text
where
	co.codsunat is not null
	and (m.coddocumento::smallint = any (array[13,
	14]))
union all 
         select
	m.nroprepago as idmovimiento,
	suc.descripcion as sucursal,
	m.fechareg as documentofecha,
	(((co.abreviado::text || ' '::text) || md.serie::text) || '-'::text) || btrim(to_char(md.nrodocumento, '00000000'::text)) as doc_sun,
	m.propietario as razonsocial,
	round(sum(md.importe::numeric), 2) as total,
	case
		when m.estado::numeric = 0::numeric then 'ANULADO'::text
		when m.estado::numeric = 1::numeric then 'PENDIENTE'::text
		when m.estado::numeric = 2::numeric then 'CANCELADO'::text
		when m.estado::numeric = 3::numeric then 'CREDITO'::text
		when m.estado::numeric = 4::numeric then 'REFINANCIAMIENTO'::text
		else null::text
	end as condicion,
	case
		when m.estado::numeric = 0::numeric then 1
		else 0
	end as estado,
	md.coddocumento,
	md.serie,
	btrim(to_char(md.nrodocumento, '00000000'::text)) as nrodocumentotri,
	co.codsunat,
	2 as origen,
	suc.codsuc,
	m.nroinscripcion,
	doc.documento_cdr_response,
	m.codciclo,
	round(avg(m.subtotal), 2) as subtotal,
	round(avg(m.igv::numeric), 2) as igv,
	round(avg(m.imptotal::numeric), 2) as imptotal,
	round(avg(m.redondeo::numeric), 2) as redondeo,
	case
		when cl.codtipodocumento::smallint = 1 then 1
		when cl.codtipodocumento::smallint = 2 then 4
		when cl.codtipodocumento::smallint = 3 then 6
		else 0
	end as tdi_id,
	case
		when cl.nrodocumento is null
		or cl.nrodocumento::text = ''::text then '00000000'::character varying
		else cl.nrodocumento::character varying
	end as cliente_numero_documento,
	'cobranza.cabprepagos' as tabla,
	m.codemp,
	case
		when doc.documento_code = 0
		and doc.documento_estado = 'A'::bpchar then 'ACEPTADO'::text
		when doc.documento_code = 0
		and doc.documento_estado = 'I'::bpchar then 'ACEPTADO(BAJA)'::text
		when doc.documento_code = 98 then 'EN PROCESO'::text
		when doc.documento_code = 99 then 'PROCESADO CON ERRORES'::text
		when doc.documento_code >= 2000
		and doc.documento_code <= 3999 then 'RECHAZADO'::text
		else 'PENDIENTE'::text
	end as estado_cpe,
	doc.documento_observaciones,
	cl.direcciondistribucion,
	m.documento as nrodocumento,
	m.direccion,
	doc.documento_nombre_xml,
	doc.documento_nombre_cdr,
	doc.documento_id,
	doc.documento_estado,
	doc.estado as estado_documento,
	doc.documento_nombre
from
	cobranza.cabprepagos m
join cobranza.detprepagos md on
	m.codemp::smallint = md.codemp::smallint
	and m.codsuc::smallint = md.codsuc::smallint
	and m.nroprepago = md.nroprepago
	and m.nroinscripcion = md.nroinscripcion
	and m.codzona::smallint = md.codzona::smallint
join reglasnegocio.documentos co on
	md.coddocumento::smallint = co.coddocumento::smallint
	and m.codsuc::smallint = co.codsuc
join admin.sucursales suc on
	suc.codemp::smallint = m.codemp::smallint
	and m.codsuc::smallint = suc.codsuc::smallint
left join catastro.clientes cl on
	cl.codemp::smallint = m.codemp::smallint
	and cl.codsuc::smallint = m.codsuc::smallint
	and cl.nroinscripcion = m.nroinscripcion
left join cpe.documentos doc on
	doc.codemp = m.codemp::smallint
	and doc.codsuc = m.codsuc::smallint
	and doc.nrooperacion = m.nroprepago
	and doc.codciclo = m.codciclo::smallint
	and doc.nroinscripcion = m.nroinscripcion
	and doc.tabla::text = 'cobranza.cabprepagos'::text
left join (
	select
		c2.serie,
		c2.nrodocumentotri,
		c2.codsuc,
		c2.codemp,
		c2.nroinscripcion,
		c2.codzona
	from
		cobranza.cabpagos c2
	where
		c2.coddocumento::smallint = any (array[13,
		14])) ccab on
	ccab.serie::text = md.serie::text
	and ccab.nrodocumentotri = md.nrodocumento
	and ccab.codsuc::smallint = m.codsuc::smallint
	and ccab.codemp::smallint = m.codemp::smallint
	and ccab.codzona::smallint = m.codzona::smallint
	and ccab.nroinscripcion = m.nroinscripcion
where
	co.codsunat is not null
	and ccab.nrodocumentotri is null
	and (md.coddocumento::smallint = any (array[13,
	14]))
group by
	m.nroprepago,
	m.fechareg,
	co.abreviado,
	md.serie,
	md.nrodocumento,
	m.estado,
	co.codsunat,
	md.coddocumento,
	suc.codsuc,
	m.nroinscripcion,
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
union all 
	select
	cast(c.nrorebaja as numeric(10)) as idmovimiento,
	suc.descripcion as sucursal,
	c.fechareg as documentofecha,
	(((co.abreviado::text || ' '::text) || d.seriedocumentoabono::text) || '-'::text) || 
btrim(to_char(d.nrodocumentoabono, '00000000'::text)) as doc_sun,
	case
		when c.nropago = 0 then cpp.propietario
		else cp.propietario
	end as razonsocial,
	ROUND(SUM(d.imprebajado)) as total,
	er.descripcion as condicion,
	case
		when c.codestrebaja = 0 then 1
		else 0
	end as estado,
	d.coddocumento as coddocumento,
	d.seriedocumentoabono as serie,
	btrim(to_char(d.nrodocumentoabono, '00000000'::text)) as nrodocumentotri,
	co.codsunat,
	3 as origen,
	suc.codsuc,
	c.nroinscripcion,
	doc.documento_cdr_response,
	0 as codciclo,
	round(SUM(case when d.codconcepto not in (5, 7, 8) then d.imprebajado else 0 end), 2) as subtotal,
	round(SUM(case when d.codconcepto in (5) then d.imprebajado else 0 end), 2) as igv,
	ROUND(SUM(d.imprebajado)) as imptotal,
	round(SUM(case when d.codconcepto in (7, 8) then d.imprebajado else 0 end), 2) as redondeo,
	case
		when cl.codtipodocumento::smallint = 1 then 1
		when cl.codtipodocumento::smallint = 2 then 4
		when cl.codtipodocumento::smallint = 3 then 6
		else 0
	end as tdi_id,
	case
		when cl.nrodocumento is null
		or cl.nrodocumento::text = ''::text then '00000000'::character varying
		else cl.nrodocumento::character varying
	end as cliente_numero_documento,
	'facturacion.cabrebajas' as tabla,
	c.codemp,
	case
		when doc.documento_code = 0
		and doc.documento_estado = 'A'::bpchar then 'ACEPTADO'::text
		when doc.documento_code = 0
		and doc.documento_estado = 'I'::bpchar then 'ACEPTADO(BAJA)'::text
		when doc.documento_code = 98 then 'EN PROCESO'::text
		when doc.documento_code = 99 then 'PROCESADO CON ERRORES'::text
		when doc.documento_code >= 2000
		and doc.documento_code <= 3999 then 'RECHAZADO'::text
		else 'PENDIENTE'::text
	end as estado_cpe,
	doc.documento_observaciones,
	cl.direcciondistribucion,
	case
		when c.nropago = 0 then cpp.documento
		else cp.nrodocumento
	end as nrodocumento,
	case
		when c.nropago = 0 then cpp.direccion
		else cp.direccion
	end as direccion,
	doc.documento_nombre_xml,
	doc.documento_nombre_cdr,
	doc.documento_id,
	doc.documento_estado,
	doc.estado as estado_documento,
	doc.documento_nombre
from
	facturacion.cabrebajas as c
inner join facturacion.detrebajas as d on
	(c.codemp = d.codemp
		and c.codsuc = d.codsuc
		and c.nrorebaja= d.nrorebaja)
inner join admin.sucursales as suc on
	suc.codemp::smallint = c.codemp::smallint
	and c.codsuc::smallint = suc.codsuc::smallint
inner join reglasnegocio.documentos as co on
	d.coddocumentoabono ::smallint = co.coddocumento::smallint
	and d.codsuc::smallint = co.codsuc
left join catastro.clientes cl on
	cl.codemp::smallint = c.codemp::smallint
	and cl.codsuc::smallint = c.codsuc::smallint
	and cl.nroinscripcion = c.nroinscripcion
inner join public.estadorebaja as er on
	(er.codestrebaja = c.codestrebaja)
left join cpe.documentos doc on
	doc.codemp = c.codemp::smallint
	and doc.codsuc = c.codsuc::smallint
	and doc.nrooperacion = c.nrorebaja
	and doc.nroinscripcion = c.nroinscripcion
	and doc.tabla::text = 'facturacion.cabrebajas'::text
left join cobranza.cabpagos as cp on
	(cp.codemp = c.codemp
		and cp.codsuc = c.codsuc
		and cp.nropago = c.nropago)
left join cobranza.cabprepagos as cpp on
	(cpp.codemp = c.codemp
		and cpp.codsuc = c.codsuc
		and cpp.nroprepago = c.nroprepago)
where
	co.codsunat is not null
	and d.coddocumento in( 13, 14)
group by
	cpp.propietario,
	cp.propietario,
	c.nropago,
	c.nroprepago,
	c.codemp,
	c.nrorebaja,
	suc.descripcion,
	c.fechareg,
	co.abreviado,
	d.seriedocumentoabono,
	d.nrodocumentoabono,
	er.descripcion,
	c.codestrebaja,
	d.coddocumento,
	d.seriedocumentoabono,
	d.nrodocumentoabono,
	co.codsunat,
	suc.codsuc,
	c.nroinscripcion,
	doc.documento_cdr_response,
	cl.codtipodocumento,
	cl.nrodocumento,
	doc.documento_code,
	doc.documento_observaciones,
	cl.direcciondistribucion,
	cpp.documento,
	cp.nrodocumento,
	cpp.direccion,
	cp.direccion,
	doc.documento_nombre_xml,
	doc.documento_nombre_cdr,
	doc.documento_id,
	doc.documento_estado,
	doc.estado,
	doc.documento_nombre;