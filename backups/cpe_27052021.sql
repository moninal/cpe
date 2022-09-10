/*
 Navicat Premium Data Transfer

 Source Server         : PostgreSql
 Source Server Type    : PostgreSQL
 Source Server Version : 80421
 Source Host           : localhost:5432
 Source Catalog        : emapica_20210331_1945
 Source Schema         : cpe

 Target Server Type    : PostgreSQL
 Target Server Version : 80421
 File Encoding         : 65001

 Date: 27/05/2021 21:38:50
*/
CREATE SCHEMA cpe;

-- ----------------------------
-- Sequence structure for comunicacion_baja_cb_id_seq
-- ----------------------------
DROP SEQUENCE IF EXISTS "cpe"."comunicacion_baja_cb_id_seq";
CREATE SEQUENCE "cpe"."comunicacion_baja_cb_id_seq" 
INCREMENT 1
MINVALUE  1
MAXVALUE 9223372036854775807
START 1
CACHE 1;

-- ----------------------------
-- Sequence structure for documentos_documento_id_seq
-- ----------------------------
DROP SEQUENCE IF EXISTS "cpe"."documentos_documento_id_seq";
CREATE SEQUENCE "cpe"."documentos_documento_id_seq" 
INCREMENT 1
MINVALUE  1
MAXVALUE 9223372036854775807
START 1
CACHE 1;

-- ----------------------------
-- Sequence structure for resumenes_diarios_rd_id_seq
-- ----------------------------
DROP SEQUENCE IF EXISTS "cpe"."resumenes_diarios_rd_id_seq";
CREATE SEQUENCE "cpe"."resumenes_diarios_rd_id_seq" 
INCREMENT 1
MINVALUE  1
MAXVALUE 9223372036854775807
START 1
CACHE 1;

-- ----------------------------
-- Table structure for comunicacion_baja
-- ----------------------------
DROP TABLE IF EXISTS "cpe"."comunicacion_baja";
CREATE TABLE "cpe"."comunicacion_baja" (
  "cb_id" int4 NOT NULL DEFAULT nextval('"cpe".comunicacion_baja_cb_id_seq'::regclass),
  "codemp" int2 NOT NULL,
  "cb_correlativo" int2 NOT NULL,
  "cb_fecha" date,
  "cb_fecha_generacion" date NOT NULL,
  "cb_ticket" varchar(50),
  "cb_cdr_response" text,
  "cb_cdr_descripcion" text,
  "cb_code" int2,
  "cb_observaciones" text,
  "cb_nombre_xml" varchar(255),
  "cb_nombre_cdr" varchar(255),
  "cb_nombre_documento" varchar(255),
  "estado" char(1) DEFAULT 'A'::bpchar
)
;

-- ----------------------------
-- Table structure for detalle_baja
-- ----------------------------
DROP TABLE IF EXISTS "cpe"."detalle_baja";
CREATE TABLE "cpe"."detalle_baja" (
  "cb_id" int4 NOT NULL,
  "documento_id" int2 NOT NULL
)
;

-- ----------------------------
-- Table structure for detalle_resumen
-- ----------------------------
DROP TABLE IF EXISTS "cpe"."detalle_resumen";
CREATE TABLE "cpe"."detalle_resumen" (
  "rd_id" int2 NOT NULL,
  "documento_id" int2 NOT NULL,
  "dr_estado" char(1)
)
;
COMMENT ON COLUMN "cpe"."detalle_resumen"."dr_estado" IS '1 -> Adicionar
2 -> Modificar
3 -> Anulado
';

-- ----------------------------
-- Table structure for documentos
-- ----------------------------
DROP TABLE IF EXISTS "cpe"."documentos";
CREATE TABLE "cpe"."documentos" (
  "documento_id" int4 NOT NULL DEFAULT nextval('"cpe".documentos_documento_id_seq'::regclass),
  "codemp" int2 NOT NULL,
  "codsuc" int2 NOT NULL,
  "nrooperacion" numeric(10) NOT NULL,
  "codciclo" int2 NOT NULL,
  "nroinscripcion" numeric(10) NOT NULL,
  "documento_fecha" date,
  "documento_cdr_response" text,
  "tabla" varchar(50) NOT NULL,
  "documento_code" int2,
  "documento_observaciones" text,
  "documento_nombre" varchar(255),
  "documento_nombre_xml" varchar(255),
  "documento_nombre_cdr" varchar(255),
  "documento_estado" char(1),
  "estado" char(1) DEFAULT 'A'::bpchar
)
;
COMMENT ON COLUMN "cpe"."documentos"."documento_estado" IS 'A -> ACTIVO
I -> ANULADO
';
COMMENT ON COLUMN "cpe"."documentos"."estado" IS 'A -> ACTIVO
I -> INACTIVO';

-- ----------------------------
-- Table structure for resumenes_diarios
-- ----------------------------
DROP TABLE IF EXISTS "cpe"."resumenes_diarios";
CREATE TABLE "cpe"."resumenes_diarios" (
  "rd_id" int4 NOT NULL DEFAULT nextval('"cpe".resumenes_diarios_rd_id_seq'::regclass),
  "codemp" int2 NOT NULL,
  "rd_correlativo" int2 NOT NULL,
  "rd_tipo" char(5) NOT NULL,
  "rd_fecha" date NOT NULL,
  "rd_fecha_generacion" date NOT NULL,
  "rd_ticket" varchar(50),
  "rd_cdr_response" text,
  "rd_code" int2,
  "rd_observaciones" text,
  "rd_nombre_xml" varchar(255),
  "rd_nombre_cdr" varchar(255),
  "rd_nombre_documento" varchar(255),
  "estado" char(1) DEFAULT 'A'::bpchar
)
;
COMMENT ON COLUMN "cpe"."resumenes_diarios"."rd_tipo" IS 'RN -> resumen normal
RB -> resumen bajas';
COMMENT ON COLUMN "cpe"."resumenes_diarios"."rd_fecha" IS 'Fecha de generación del resumen
Fecha en la cual se generó el resumen diario de boletas de venta electrónicas y notas
electrónicas. El tipo DateType corresponde al tipo Date del XML, el formato deberá ser
yyyy-mm-dd.
La fecha de generación del resumen no podrá ser menor a la fecha de emisión de los
documentos informados.
Ubicación
/SummaryDocuments/cbc:IssueDate';
COMMENT ON COLUMN "cpe"."resumenes_diarios"."rd_fecha_generacion" IS 'Fecha de emisión de los documentos
Corresponde a la fecha de emisión de las boletas de venta electrónicas y notas
electrónicas, contenidas en el resumen diario.
El Resumen diario de boletas de venta y notas se podrá incluir uno o más documentos,
siempre que todos hayan sido generados o emitidos en un mismo día.
Ubicación
/SummaryDocuments/cbc:ReferenceDate';

-- ----------------------------
-- View structure for vista_documentos_electronicos
-- ----------------------------
DROP VIEW IF EXISTS "cpe"."vista_documentos_electronicos";
CREATE VIEW "cpe"."vista_documentos_electronicos" AS select
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
	c.codestrebaja as estado,
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



-- ----------------------------
-- Alter sequences owned by
-- ----------------------------
-- ALTER SEQUENCE "cpe"."comunicacion_baja_cb_id_seq"
-- OWNED BY "cpe"."comunicacion_baja"."cb_id";
-- SELECT setval('"cpe"."comunicacion_baja_cb_id_seq"', 18, true);

-- -- ----------------------------
-- -- Alter sequences owned by
-- -- ----------------------------
-- ALTER SEQUENCE "cpe"."documentos_documento_id_seq"
-- OWNED BY "cpe"."documentos"."documento_id";
-- SELECT setval('"cpe"."documentos_documento_id_seq"', 513, true);

-- -- ----------------------------
-- -- Alter sequences owned by
-- -- ----------------------------
-- ALTER SEQUENCE "cpe"."resumenes_diarios_rd_id_seq"
-- OWNED BY "cpe"."resumenes_diarios"."rd_id";
-- SELECT setval('"cpe"."resumenes_diarios_rd_id_seq"', 1140, true);

-- ----------------------------
-- Uniques structure for table comunicacion_baja
-- ----------------------------
ALTER TABLE "cpe"."comunicacion_baja" ADD CONSTRAINT "uc_comunicacion_baja" UNIQUE ("cb_id");

-- ----------------------------
-- Primary Key structure for table comunicacion_baja
-- ----------------------------
ALTER TABLE "cpe"."comunicacion_baja" ADD CONSTRAINT "comunicacion_baja_pkey" PRIMARY KEY ("cb_id", "codemp", "cb_correlativo", "cb_fecha_generacion");

-- ----------------------------
-- Primary Key structure for table detalle_baja
-- ----------------------------
ALTER TABLE "cpe"."detalle_baja" ADD CONSTRAINT "detalle_baja_pkey" PRIMARY KEY ("cb_id", "documento_id");

-- ----------------------------
-- Primary Key structure for table detalle_resumen
-- ----------------------------
ALTER TABLE "cpe"."detalle_resumen" ADD CONSTRAINT "detalle_resumen_pkey" PRIMARY KEY ("rd_id", "documento_id");

-- ----------------------------
-- Uniques structure for table documentos
-- ----------------------------
ALTER TABLE "cpe"."documentos" ADD CONSTRAINT "uc_documentos" UNIQUE ("documento_id");

-- ----------------------------
-- Primary Key structure for table documentos
-- ----------------------------
ALTER TABLE "cpe"."documentos" ADD CONSTRAINT "documentos_pkey" PRIMARY KEY ("documento_id", "codemp", "codsuc", "nrooperacion", "codciclo", "nroinscripcion", "tabla");

-- ----------------------------
-- Uniques structure for table resumenes_diarios
-- ----------------------------
ALTER TABLE "cpe"."resumenes_diarios" ADD CONSTRAINT "uc_resumenes_diarios" UNIQUE ("rd_id");

-- ----------------------------
-- Primary Key structure for table resumenes_diarios
-- ----------------------------
ALTER TABLE "cpe"."resumenes_diarios" ADD CONSTRAINT "resumenes_diarios_pkey" PRIMARY KEY ("rd_id", "codemp", "rd_correlativo", "rd_tipo", "rd_fecha_generacion");

-- ----------------------------
-- Foreign Keys structure for table detalle_baja
-- ----------------------------
ALTER TABLE "cpe"."detalle_baja" ADD CONSTRAINT "fk_comunicacion_baja_detalle_ baja" FOREIGN KEY ("cb_id") REFERENCES "cpe"."comunicacion_baja" ("cb_id") ON DELETE NO ACTION ON UPDATE NO ACTION;
ALTER TABLE "cpe"."detalle_baja" ADD CONSTRAINT "fk_documentos_detalle_baja" FOREIGN KEY ("documento_id") REFERENCES "cpe"."documentos" ("documento_id") ON DELETE NO ACTION ON UPDATE NO ACTION;

-- ----------------------------
-- Foreign Keys structure for table detalle_resumen
-- ----------------------------
ALTER TABLE "cpe"."detalle_resumen" ADD CONSTRAINT "fk_documentos_detalle_resumen" FOREIGN KEY ("documento_id") REFERENCES "cpe"."documentos" ("documento_id") ON DELETE NO ACTION ON UPDATE NO ACTION;
ALTER TABLE "cpe"."detalle_resumen" ADD CONSTRAINT "fk_resumenes_diarios_detalle_resumen" FOREIGN KEY ("rd_id") REFERENCES "cpe"."resumenes_diarios" ("rd_id") ON DELETE NO ACTION ON UPDATE NO ACTION;
