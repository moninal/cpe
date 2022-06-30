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
CREATE VIEW "cpe"."vista_documentos_electronicos" AS SELECT m.nropago AS idmovimiento, suc.descripcion AS sucursal, m.fechareg AS documentofecha, (((((co.abreviado)::text || ' '::text) || (m.serie)::text) || '-'::text) || btrim(to_char(m.nrodocumentotri, '00000000'::text))) AS doc_sun, m.propietario AS razonsocial, round((m.imptotal)::numeric, 2) AS total, CASE WHEN ((m.anulado)::numeric = (1)::numeric) THEN 'ANULADO'::text ELSE 'PAGADO'::text END AS condicion, m.anulado AS estado, m.coddocumento, m.serie, btrim(to_char(m.nrodocumentotri, '00000000'::text)) AS nrodocumentotri, co.codsunat, 1 AS origen, suc.codsuc, m.nroinscripcion, doc.documento_cdr_response, m.codciclo, m.subtotal, m.igv, m.imptotal, m.redondeo, CASE WHEN ((cl.codtipodocumento)::smallint = 1) THEN 1 WHEN ((cl.codtipodocumento)::smallint = 2) THEN 4 WHEN ((cl.codtipodocumento)::smallint = 3) THEN 6 ELSE cl.codtipodocumento END AS tdi_id, CASE WHEN ((cl.nrodocumento IS NULL) OR ((cl.nrodocumento)::text = ''::text)) THEN '00000000'::character varying ELSE (cl.nrodocumento)::character varying END AS cliente_numero_documento, 'cobranza.cabpagos' AS tabla, m.codemp, CASE WHEN ((doc.documento_code = 0) AND (doc.documento_estado = 'A'::bpchar)) THEN 'ACEPTADO'::text WHEN ((doc.documento_code = 0) AND (doc.documento_estado = 'I'::bpchar)) THEN 'ACEPTADO(BAJA)'::text WHEN ((doc.documento_code >= 2000) AND (doc.documento_code <= 3999)) THEN 'RECHAZADO'::text ELSE 'PENDIENTE'::text END AS estado_cpe, doc.documento_observaciones, cl.direcciondistribucion, m.nrodocumento, m.direccion, doc.documento_nombre_xml, doc.documento_nombre_cdr, doc.documento_id, doc.documento_estado, doc.estado AS estado_documento, doc.documento_nombre FROM ((((cobranza.cabpagos m JOIN reglasnegocio.documentos co ON ((((m.coddocumento)::smallint = (co.coddocumento)::smallint) AND ((m.codsuc)::smallint = co.codsuc)))) JOIN admin.sucursales suc ON ((((suc.codemp)::smallint = (m.codemp)::smallint) AND ((m.codsuc)::smallint = (suc.codsuc)::smallint)))) LEFT JOIN catastro.clientes cl ON (((((cl.codemp)::smallint = (m.codemp)::smallint) AND ((cl.codsuc)::smallint = (m.codsuc)::smallint)) AND (cl.nroinscripcion = m.nroinscripcion)))) LEFT JOIN cpe.documentos doc ON (((((((doc.codemp = (m.codemp)::smallint) AND (doc.codsuc = (m.codsuc)::smallint)) AND (doc.nrooperacion = m.nropago)) AND (doc.codciclo = m.codciclo)) AND (doc.nroinscripcion = m.nroinscripcion)) AND ((doc.tabla)::text = 'cobranza.cabpagos'::text)))) WHERE ((co.codsunat IS NOT NULL) AND ((m.coddocumento)::smallint = ANY (ARRAY[13, 14]))) UNION ALL SELECT m.nroprepago AS idmovimiento, suc.descripcion AS sucursal, m.fechareg AS documentofecha, (((((co.abreviado)::text || ' '::text) || (md.serie)::text) || '-'::text) || btrim(to_char(md.nrodocumento, '00000000'::text))) AS doc_sun, m.propietario AS razonsocial, round(sum((md.importe)::numeric), 2) AS total, CASE WHEN ((m.estado)::numeric = (0)::numeric) THEN 'ANULADO'::text WHEN ((m.estado)::numeric = (1)::numeric) THEN 'PENDIENTE'::text WHEN ((m.estado)::numeric = (2)::numeric) THEN 'CANCELADO'::text WHEN ((m.estado)::numeric = (3)::numeric) THEN 'CREDITO'::text WHEN ((m.estado)::numeric = (4)::numeric) THEN 'REFINANCIAMIENTO'::text ELSE NULL::text END AS condicion, CASE WHEN ((m.estado)::numeric = (0)::numeric) THEN 1 ELSE 0 END AS estado, md.coddocumento, md.serie, btrim(to_char(md.nrodocumento, '00000000'::text)) AS nrodocumentotri, co.codsunat, 2 AS origen, suc.codsuc, m.nroinscripcion, doc.documento_cdr_response, m.codciclo, sum(m.subtotal) AS subtotal, sum((m.igv)::numeric) AS igv, sum((m.imptotal)::numeric) AS imptotal, sum((m.redondeo)::numeric) AS redondeo, CASE WHEN ((cl.codtipodocumento)::smallint = 1) THEN 1 WHEN ((cl.codtipodocumento)::smallint = 2) THEN 4 WHEN ((cl.codtipodocumento)::smallint = 3) THEN 6 ELSE cl.codtipodocumento END AS tdi_id, CASE WHEN ((cl.nrodocumento IS NULL) OR ((cl.nrodocumento)::text = ''::text)) THEN '00000000'::character varying ELSE (cl.nrodocumento)::character varying END AS cliente_numero_documento, 'cobranza.cabprepagos' AS tabla, m.codemp, CASE WHEN ((doc.documento_code = 0) AND (doc.documento_estado = 'A'::bpchar)) THEN 'ACEPTADO'::text WHEN ((doc.documento_code = 0) AND (doc.documento_estado = 'I'::bpchar)) THEN 'ACEPTADO(BAJA)'::text WHEN ((doc.documento_code >= 2000) AND (doc.documento_code <= 3999)) THEN 'RECHAZADO'::text ELSE 'PENDIENTE'::text END AS estado_cpe, doc.documento_observaciones, cl.direcciondistribucion, m.documento AS nrodocumento, m.direccion, doc.documento_nombre_xml, doc.documento_nombre_cdr, doc.documento_id, doc.documento_estado, doc.estado AS estado_documento, doc.documento_nombre FROM ((((((cobranza.cabprepagos m JOIN cobranza.detprepagos md ON (((((((m.codemp)::smallint = (md.codemp)::smallint) AND ((m.codsuc)::smallint = (md.codsuc)::smallint)) AND (m.nroprepago = md.nroprepago)) AND (m.nroinscripcion = md.nroinscripcion)) AND ((m.codzona)::smallint = (md.codzona)::smallint)))) JOIN reglasnegocio.documentos co ON ((((md.coddocumento)::smallint = (co.coddocumento)::smallint) AND ((m.codsuc)::smallint = co.codsuc)))) JOIN admin.sucursales suc ON ((((suc.codemp)::smallint = (m.codemp)::smallint) AND ((m.codsuc)::smallint = (suc.codsuc)::smallint)))) LEFT JOIN catastro.clientes cl ON (((((cl.codemp)::smallint = (m.codemp)::smallint) AND ((cl.codsuc)::smallint = (m.codsuc)::smallint)) AND (cl.nroinscripcion = m.nroinscripcion)))) LEFT JOIN cpe.documentos doc ON (((((((doc.codemp = (m.codemp)::smallint) AND (doc.codsuc = (m.codsuc)::smallint)) AND (doc.nrooperacion = m.nroprepago)) AND (doc.codciclo = (m.codciclo)::smallint)) AND (doc.nroinscripcion = m.nroinscripcion)) AND ((doc.tabla)::text = 'cobranza.cabprepagos'::text)))) LEFT JOIN (SELECT c2.serie, c2.nrodocumentotri, c2.codsuc, c2.codemp, c2.nroinscripcion, c2.codzona FROM cobranza.cabpagos c2 WHERE ((c2.coddocumento)::smallint = ANY (ARRAY[13, 14]))) ccab ON ((((((((ccab.serie)::text = (md.serie)::text) AND (ccab.nrodocumentotri = md.nrodocumento)) AND ((ccab.codsuc)::smallint = (m.codsuc)::smallint)) AND ((ccab.codemp)::smallint = (m.codemp)::smallint)) AND ((ccab.codzona)::smallint = (m.codzona)::smallint)) AND (ccab.nroinscripcion = m.nroinscripcion)))) WHERE (((co.codsunat IS NOT NULL) AND (ccab.nrodocumentotri IS NULL)) AND ((md.coddocumento)::smallint = ANY (ARRAY[13, 14]))) GROUP BY m.nroprepago, m.fechareg, co.abreviado, md.serie, md.nrodocumento, m.estado, co.codsunat, md.coddocumento, suc.codsuc, m.nroinscripcion, suc.descripcion, doc.documento_cdr_response, m.codciclo, cl.codtipodocumento, cl.nrodocumento, m.codemp, doc.documento_code, doc.documento_observaciones, cl.direcciondistribucion, m.propietario, m.documento, m.direccion, doc.documento_nombre_xml, doc.documento_nombre_cdr, doc.documento_id, doc.documento_estado, doc.estado, doc.documento_nombre;

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
