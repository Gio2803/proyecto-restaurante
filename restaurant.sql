--
-- PostgreSQL database dump
--

\restrict e2wFJpmUqOFH7ojd0K3FmlYEK37d0F0s8VrxIoAfyYGovIcUZF0ZxvtQhzgf1Sl

-- Dumped from database version 17.6
-- Dumped by pg_dump version 17.6

-- Started on 2025-11-26 15:16:14

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 243 (class 1255 OID 17148)
-- Name: proteger_rol_administrador(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.proteger_rol_administrador() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF OLD.id_rol = 1 THEN
        RAISE EXCEPTION 'No se puede eliminar el rol Administrador del sistema';
    END IF;
    RETURN OLD;
END;
$$;


ALTER FUNCTION public.proteger_rol_administrador() OWNER TO postgres;

--
-- TOC entry 256 (class 1255 OID 17194)
-- Name: sp_abrir_caja(integer, numeric, text); Type: PROCEDURE; Schema: public; Owner: postgres
--

CREATE PROCEDURE public.sp_abrir_caja(IN p_id_usuario integer, IN p_monto_inicial numeric, IN p_observaciones text DEFAULT NULL::text)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_nuevo_id INTEGER;
BEGIN
    -- Insertar nueva caja
    INSERT INTO corte_caja (
        id_usuario,
        fecha_apertura,
        monto_inicial,
        observaciones,
        estado
    ) VALUES (
        p_id_usuario,
        CURRENT_TIMESTAMP,
        p_monto_inicial,
        p_observaciones,
        'ABIERTO'  -- Cambiado de 'abierta' a 'ABIERTO'
    ) RETURNING id_corte INTO v_nuevo_id;
    
    RAISE NOTICE 'Caja abierta exitosamente. ID de caja: %', v_nuevo_id;
    
END;
$$;


ALTER PROCEDURE public.sp_abrir_caja(IN p_id_usuario integer, IN p_monto_inicial numeric, IN p_observaciones text) OWNER TO postgres;

--
-- TOC entry 257 (class 1255 OID 17207)
-- Name: sp_cerrar_caja(integer, integer, numeric, text); Type: PROCEDURE; Schema: public; Owner: postgres
--

CREATE PROCEDURE public.sp_cerrar_caja(IN p_id_corte integer, IN p_id_usuario integer, IN p_monto_final numeric, IN p_observaciones text)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_estado_actual VARCHAR(20);
    v_ventas_totales DECIMAL(10,2) := 0;
    v_monto_inicial DECIMAL(10,2);
    v_fecha_apertura TIMESTAMP;
    v_diferencia DECIMAL(10,2);
BEGIN
    -- Verificar si la caja existe y obtener su estado actual y datos
    SELECT estado, monto_inicial, fecha_apertura 
    INTO v_estado_actual, v_monto_inicial, v_fecha_apertura
    FROM corte_caja
    WHERE id_corte = p_id_corte;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'La caja con id % no existe', p_id_corte;
    END IF;
    
    IF v_estado_actual = 'cerrada' THEN
        RAISE EXCEPTION 'La caja ya se encuentra cerrada';
    END IF;
    
    -- Calcular ventas reales desde pedidos
    SELECT COALESCE(SUM(total), 0) INTO v_ventas_totales
    FROM pedidos 
    WHERE fecha_creacion >= v_fecha_apertura 
    AND estado = 'pagado';
    
    -- Calcular diferencia CORRECTA: Efectivo Final - (Monto Inicial + Ventas Reales)
    v_diferencia := p_monto_final - (v_monto_inicial + v_ventas_totales);
    
    -- Actualizar la caja
    UPDATE corte_caja 
    SET fecha_cierre = CURRENT_TIMESTAMP,
        monto_final = p_monto_final,
        ventas_totales = v_ventas_totales,
        diferencia = v_diferencia,
        observaciones = COALESCE(p_observaciones, observaciones),
        estado = 'cerrada'
    WHERE id_corte = p_id_corte
      AND id_usuario = p_id_usuario;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Error al cerrar la caja. Verifique el id_usuario';
    END IF;
    
END;
$$;


ALTER PROCEDURE public.sp_cerrar_caja(IN p_id_corte integer, IN p_id_usuario integer, IN p_monto_final numeric, IN p_observaciones text) OWNER TO postgres;

--
-- TOC entry 244 (class 1255 OID 17197)
-- Name: sp_consultar_caja(integer); Type: PROCEDURE; Schema: public; Owner: postgres
--

CREATE PROCEDURE public.sp_consultar_caja(IN p_id_corte integer)
    LANGUAGE plpgsql
    AS $$
DECLARE
    caja_record corte_caja%ROWTYPE;
BEGIN
    -- Obtener información de la caja
    SELECT * INTO caja_record
    FROM corte_caja
    WHERE id_corte = p_id_corte;
    
    -- Si no encuentra la caja
    IF NOT FOUND THEN
        RAISE EXCEPTION 'La caja con id % no existe', p_id_corte;
    END IF;
    
    -- Mostrar información de la caja
    RAISE NOTICE 'Información de la caja:';
    RAISE NOTICE 'ID: %', caja_record.id_corte;
    RAISE NOTICE 'Usuario: %', caja_record.id_usuario;
    RAISE NOTICE 'Fecha apertura: %', caja_record.fecha_apertura;
    RAISE NOTICE 'Fecha cierre: %', caja_record.fecha_cierre;
    RAISE NOTICE 'Monto inicial: %', caja_record.monto_inicial;
    RAISE NOTICE 'Monto final: %', caja_record.monto_final;
    RAISE NOTICE 'Ventas totales: %', caja_record.ventas_totales;
    RAISE NOTICE 'Estado: %', caja_record.estado;
    RAISE NOTICE 'Observaciones: %', caja_record.observaciones;
    
END;
$$;


ALTER PROCEDURE public.sp_consultar_caja(IN p_id_corte integer) OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 224 (class 1259 OID 16775)
-- Name: categorias; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.categorias (
    id_categoria integer NOT NULL,
    nombre character varying(100) NOT NULL,
    descripcion text,
    fechabaja timestamp without time zone
);


ALTER TABLE public.categorias OWNER TO postgres;

--
-- TOC entry 223 (class 1259 OID 16774)
-- Name: categorias_id_categoria_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.categorias_id_categoria_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.categorias_id_categoria_seq OWNER TO postgres;

--
-- TOC entry 5044 (class 0 OID 0)
-- Dependencies: 223
-- Name: categorias_id_categoria_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.categorias_id_categoria_seq OWNED BY public.categorias.id_categoria;


--
-- TOC entry 222 (class 1259 OID 16658)
-- Name: clientes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.clientes (
    id_cliente integer NOT NULL,
    nombre character varying(100) NOT NULL,
    apellidos character varying(100) NOT NULL,
    telefono character varying(20) NOT NULL,
    fechabaja timestamp without time zone
);


ALTER TABLE public.clientes OWNER TO postgres;

--
-- TOC entry 221 (class 1259 OID 16657)
-- Name: clientes_id_cliente_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.clientes_id_cliente_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.clientes_id_cliente_seq OWNER TO postgres;

--
-- TOC entry 5045 (class 0 OID 0)
-- Dependencies: 221
-- Name: clientes_id_cliente_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.clientes_id_cliente_seq OWNED BY public.clientes.id_cliente;


--
-- TOC entry 242 (class 1259 OID 17178)
-- Name: corte_caja; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.corte_caja (
    id_corte integer NOT NULL,
    id_usuario integer NOT NULL,
    fecha_apertura timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    fecha_cierre timestamp without time zone,
    monto_inicial numeric(10,2) NOT NULL,
    monto_final numeric(10,2),
    ventas_totales numeric(10,2) DEFAULT 0,
    observaciones text,
    estado character varying(20) DEFAULT 'abierta'::character varying,
    diferencia numeric(10,2) DEFAULT 0
);


ALTER TABLE public.corte_caja OWNER TO postgres;

--
-- TOC entry 241 (class 1259 OID 17177)
-- Name: corte_caja_id_corte_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.corte_caja_id_corte_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.corte_caja_id_corte_seq OWNER TO postgres;

--
-- TOC entry 5046 (class 0 OID 0)
-- Dependencies: 241
-- Name: corte_caja_id_corte_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.corte_caja_id_corte_seq OWNED BY public.corte_caja.id_corte;


--
-- TOC entry 232 (class 1259 OID 16856)
-- Name: detalles_pedido; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.detalles_pedido (
    id_detalle integer NOT NULL,
    id_pedido integer,
    id_platillo integer NOT NULL,
    nombre_platillo character varying(200) NOT NULL,
    cantidad integer NOT NULL,
    precio_unitario numeric(10,2) NOT NULL,
    subtotal numeric(10,2) NOT NULL,
    fecha_creacion timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    estado character varying(20) DEFAULT 'pendiente'::character varying,
    nota text,
    CONSTRAINT detalles_pedido_estado_check CHECK (((estado)::text = ANY ((ARRAY['pendiente'::character varying, 'en_preparacion'::character varying, 'terminado'::character varying])::text[])))
);


ALTER TABLE public.detalles_pedido OWNER TO postgres;

--
-- TOC entry 231 (class 1259 OID 16855)
-- Name: detalles_pedido_id_detalle_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.detalles_pedido_id_detalle_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.detalles_pedido_id_detalle_seq OWNER TO postgres;

--
-- TOC entry 5047 (class 0 OID 0)
-- Dependencies: 231
-- Name: detalles_pedido_id_detalle_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.detalles_pedido_id_detalle_seq OWNED BY public.detalles_pedido.id_detalle;


--
-- TOC entry 228 (class 1259 OID 16795)
-- Name: menu; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.menu (
    id_platillo integer NOT NULL,
    nombre character varying(200) NOT NULL,
    descripcion text,
    id_categoria integer,
    precio numeric(10,2) NOT NULL,
    id_unidad integer,
    imagen character varying(255),
    fechabaja timestamp without time zone
);


ALTER TABLE public.menu OWNER TO postgres;

--
-- TOC entry 227 (class 1259 OID 16794)
-- Name: menu_id_platillo_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.menu_id_platillo_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.menu_id_platillo_seq OWNER TO postgres;

--
-- TOC entry 5048 (class 0 OID 0)
-- Dependencies: 227
-- Name: menu_id_platillo_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.menu_id_platillo_seq OWNED BY public.menu.id_platillo;


--
-- TOC entry 238 (class 1259 OID 17043)
-- Name: menu_items; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.menu_items (
    id integer NOT NULL,
    nombre character varying(100) NOT NULL,
    url character varying(255),
    icono character varying(50),
    orden integer DEFAULT 0,
    activo boolean DEFAULT true,
    parent_id integer
);


ALTER TABLE public.menu_items OWNER TO postgres;

--
-- TOC entry 237 (class 1259 OID 17042)
-- Name: menu_items_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.menu_items_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.menu_items_id_seq OWNER TO postgres;

--
-- TOC entry 5049 (class 0 OID 0)
-- Dependencies: 237
-- Name: menu_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.menu_items_id_seq OWNED BY public.menu_items.id;


--
-- TOC entry 234 (class 1259 OID 16952)
-- Name: mesas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mesas (
    id_mesa integer NOT NULL,
    numero_mesa integer NOT NULL,
    capacidad integer NOT NULL,
    estado character varying(20) DEFAULT 'disponible'::character varying,
    ubicacion character varying(100),
    fechabaja timestamp without time zone,
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.mesas OWNER TO postgres;

--
-- TOC entry 233 (class 1259 OID 16951)
-- Name: mesas_id_mesa_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.mesas_id_mesa_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.mesas_id_mesa_seq OWNER TO postgres;

--
-- TOC entry 5050 (class 0 OID 0)
-- Dependencies: 233
-- Name: mesas_id_mesa_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.mesas_id_mesa_seq OWNED BY public.mesas.id_mesa;


--
-- TOC entry 230 (class 1259 OID 16844)
-- Name: pedidos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.pedidos (
    id_pedido integer NOT NULL,
    id_mesero integer NOT NULL,
    nombre_mesero character varying(100) NOT NULL,
    estado character varying(20) DEFAULT 'recibida'::character varying,
    total numeric(10,2) DEFAULT 0,
    fecha_creacion timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    id_cliente integer,
    id_mesa integer,
    nombre_cliente character varying(255),
    CONSTRAINT pedidos_estado_check CHECK (((estado)::text = ANY ((ARRAY['recibida'::character varying, 'en_preparacion'::character varying, 'finalizada'::character varying, 'pagado'::character varying])::text[])))
);


ALTER TABLE public.pedidos OWNER TO postgres;

--
-- TOC entry 229 (class 1259 OID 16843)
-- Name: pedidos_id_pedido_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.pedidos_id_pedido_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.pedidos_id_pedido_seq OWNER TO postgres;

--
-- TOC entry 5051 (class 0 OID 0)
-- Dependencies: 229
-- Name: pedidos_id_pedido_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.pedidos_id_pedido_seq OWNED BY public.pedidos.id_pedido;


--
-- TOC entry 240 (class 1259 OID 17057)
-- Name: permisos_menu; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.permisos_menu (
    id integer NOT NULL,
    id_usuario integer NOT NULL,
    menu_item_id integer NOT NULL,
    activo boolean DEFAULT false,
    permiso_corte boolean DEFAULT false
);


ALTER TABLE public.permisos_menu OWNER TO postgres;

--
-- TOC entry 239 (class 1259 OID 17056)
-- Name: permisos_menu_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.permisos_menu_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.permisos_menu_id_seq OWNER TO postgres;

--
-- TOC entry 5052 (class 0 OID 0)
-- Dependencies: 239
-- Name: permisos_menu_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.permisos_menu_id_seq OWNED BY public.permisos_menu.id;


--
-- TOC entry 220 (class 1259 OID 16624)
-- Name: roles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.roles (
    id_rol integer NOT NULL,
    nombre_rol character varying(50) NOT NULL,
    fechabaja timestamp without time zone
);


ALTER TABLE public.roles OWNER TO postgres;

--
-- TOC entry 219 (class 1259 OID 16623)
-- Name: roles_id_rol_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.roles_id_rol_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.roles_id_rol_seq OWNER TO postgres;

--
-- TOC entry 5053 (class 0 OID 0)
-- Dependencies: 219
-- Name: roles_id_rol_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.roles_id_rol_seq OWNED BY public.roles.id_rol;


--
-- TOC entry 226 (class 1259 OID 16786)
-- Name: unidades_medida; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.unidades_medida (
    id_unidad integer NOT NULL,
    nombre character varying(50) NOT NULL,
    abreviatura character varying(10),
    fechabaja timestamp without time zone
);


ALTER TABLE public.unidades_medida OWNER TO postgres;

--
-- TOC entry 225 (class 1259 OID 16785)
-- Name: unidades_medida_id_unidad_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.unidades_medida_id_unidad_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.unidades_medida_id_unidad_seq OWNER TO postgres;

--
-- TOC entry 5054 (class 0 OID 0)
-- Dependencies: 225
-- Name: unidades_medida_id_unidad_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.unidades_medida_id_unidad_seq OWNED BY public.unidades_medida.id_unidad;


--
-- TOC entry 218 (class 1259 OID 16614)
-- Name: usuarios; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.usuarios (
    id_usuario integer NOT NULL,
    usuario character varying(50) NOT NULL,
    contrasena character varying(255) NOT NULL,
    nombre character varying(100) NOT NULL,
    telefono character varying(20),
    id_rol integer NOT NULL,
    fechabaja timestamp without time zone,
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.usuarios OWNER TO postgres;

--
-- TOC entry 217 (class 1259 OID 16613)
-- Name: usuarios_id_usuario_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.usuarios_id_usuario_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.usuarios_id_usuario_seq OWNER TO postgres;

--
-- TOC entry 5055 (class 0 OID 0)
-- Dependencies: 217
-- Name: usuarios_id_usuario_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.usuarios_id_usuario_seq OWNED BY public.usuarios.id_usuario;


--
-- TOC entry 235 (class 1259 OID 16977)
-- Name: vista_pedidos_detalle; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.vista_pedidos_detalle AS
 SELECT p.id_pedido,
    COALESCE(c.nombre, ''::character varying) AS nombre_cliente,
    COALESCE(c.apellidos, ''::character varying) AS apellidos_cliente,
    m.numero_mesa,
    dp.id_detalle AS id_detalle_pedido,
    dp.nombre_platillo,
    dp.cantidad,
    dp.precio_unitario,
    dp.subtotal,
    p.total,
    p.estado,
    p.fecha_creacion
   FROM (((public.pedidos p
     LEFT JOIN public.clientes c ON ((p.id_cliente = c.id_cliente)))
     LEFT JOIN public.mesas m ON ((p.id_mesa = m.id_mesa)))
     LEFT JOIN public.detalles_pedido dp ON ((p.id_pedido = dp.id_pedido)))
  ORDER BY p.id_pedido DESC;


ALTER VIEW public.vista_pedidos_detalle OWNER TO postgres;

--
-- TOC entry 236 (class 1259 OID 16982)
-- Name: vista_resumen_pedidos; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.vista_resumen_pedidos AS
 SELECT p.id_pedido,
    COALESCE((m.numero_mesa)::text, ''::text) AS numero_mesa,
    (((COALESCE(c.nombre, ''::character varying))::text || ' '::text) || (COALESCE(c.apellidos, ''::character varying))::text) AS cliente,
    COALESCE(u.nombre, ''::character varying) AS mesero,
    p.total,
    p.estado,
    p.fecha_creacion,
    p.fecha_actualizacion
   FROM (((public.pedidos p
     LEFT JOIN public.mesas m ON ((p.id_mesa = m.id_mesa)))
     LEFT JOIN public.clientes c ON ((p.id_cliente = c.id_cliente)))
     LEFT JOIN public.usuarios u ON ((p.id_mesero = u.id_usuario)))
  ORDER BY p.fecha_creacion DESC;


ALTER VIEW public.vista_resumen_pedidos OWNER TO postgres;

--
-- TOC entry 4813 (class 2604 OID 16778)
-- Name: categorias id_categoria; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.categorias ALTER COLUMN id_categoria SET DEFAULT nextval('public.categorias_id_categoria_seq'::regclass);


--
-- TOC entry 4812 (class 2604 OID 16661)
-- Name: clientes id_cliente; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clientes ALTER COLUMN id_cliente SET DEFAULT nextval('public.clientes_id_cliente_seq'::regclass);


--
-- TOC entry 4833 (class 2604 OID 17181)
-- Name: corte_caja id_corte; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.corte_caja ALTER COLUMN id_corte SET DEFAULT nextval('public.corte_caja_id_corte_seq'::regclass);


--
-- TOC entry 4821 (class 2604 OID 16859)
-- Name: detalles_pedido id_detalle; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detalles_pedido ALTER COLUMN id_detalle SET DEFAULT nextval('public.detalles_pedido_id_detalle_seq'::regclass);


--
-- TOC entry 4815 (class 2604 OID 16798)
-- Name: menu id_platillo; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.menu ALTER COLUMN id_platillo SET DEFAULT nextval('public.menu_id_platillo_seq'::regclass);


--
-- TOC entry 4827 (class 2604 OID 17046)
-- Name: menu_items id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.menu_items ALTER COLUMN id SET DEFAULT nextval('public.menu_items_id_seq'::regclass);


--
-- TOC entry 4824 (class 2604 OID 16955)
-- Name: mesas id_mesa; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mesas ALTER COLUMN id_mesa SET DEFAULT nextval('public.mesas_id_mesa_seq'::regclass);


--
-- TOC entry 4816 (class 2604 OID 16847)
-- Name: pedidos id_pedido; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pedidos ALTER COLUMN id_pedido SET DEFAULT nextval('public.pedidos_id_pedido_seq'::regclass);


--
-- TOC entry 4830 (class 2604 OID 17060)
-- Name: permisos_menu id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.permisos_menu ALTER COLUMN id SET DEFAULT nextval('public.permisos_menu_id_seq'::regclass);


--
-- TOC entry 4811 (class 2604 OID 16627)
-- Name: roles id_rol; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles ALTER COLUMN id_rol SET DEFAULT nextval('public.roles_id_rol_seq'::regclass);


--
-- TOC entry 4814 (class 2604 OID 16789)
-- Name: unidades_medida id_unidad; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.unidades_medida ALTER COLUMN id_unidad SET DEFAULT nextval('public.unidades_medida_id_unidad_seq'::regclass);


--
-- TOC entry 4809 (class 2604 OID 16617)
-- Name: usuarios id_usuario; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios ALTER COLUMN id_usuario SET DEFAULT nextval('public.usuarios_id_usuario_seq'::regclass);


--
-- TOC entry 4849 (class 2606 OID 16784)
-- Name: categorias categorias_nombre_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.categorias
    ADD CONSTRAINT categorias_nombre_key UNIQUE (nombre);


--
-- TOC entry 4851 (class 2606 OID 16782)
-- Name: categorias categorias_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.categorias
    ADD CONSTRAINT categorias_pkey PRIMARY KEY (id_categoria);


--
-- TOC entry 4847 (class 2606 OID 16663)
-- Name: clientes clientes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clientes
    ADD CONSTRAINT clientes_pkey PRIMARY KEY (id_cliente);


--
-- TOC entry 4872 (class 2606 OID 17188)
-- Name: corte_caja corte_caja_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.corte_caja
    ADD CONSTRAINT corte_caja_pkey PRIMARY KEY (id_corte);


--
-- TOC entry 4861 (class 2606 OID 16862)
-- Name: detalles_pedido detalles_pedido_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detalles_pedido
    ADD CONSTRAINT detalles_pedido_pkey PRIMARY KEY (id_detalle);


--
-- TOC entry 4866 (class 2606 OID 17050)
-- Name: menu_items menu_items_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.menu_items
    ADD CONSTRAINT menu_items_pkey PRIMARY KEY (id);


--
-- TOC entry 4857 (class 2606 OID 16802)
-- Name: menu menu_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.menu
    ADD CONSTRAINT menu_pkey PRIMARY KEY (id_platillo);


--
-- TOC entry 4864 (class 2606 OID 16959)
-- Name: mesas mesas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mesas
    ADD CONSTRAINT mesas_pkey PRIMARY KEY (id_mesa);


--
-- TOC entry 4859 (class 2606 OID 16854)
-- Name: pedidos pedidos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pedidos
    ADD CONSTRAINT pedidos_pkey PRIMARY KEY (id_pedido);


--
-- TOC entry 4868 (class 2606 OID 17065)
-- Name: permisos_menu permisos_menu_id_usuario_menu_item_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.permisos_menu
    ADD CONSTRAINT permisos_menu_id_usuario_menu_item_id_key UNIQUE (id_usuario, menu_item_id);


--
-- TOC entry 4870 (class 2606 OID 17063)
-- Name: permisos_menu permisos_menu_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.permisos_menu
    ADD CONSTRAINT permisos_menu_pkey PRIMARY KEY (id);


--
-- TOC entry 4845 (class 2606 OID 16629)
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id_rol);


--
-- TOC entry 4853 (class 2606 OID 16793)
-- Name: unidades_medida unidades_medida_nombre_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.unidades_medida
    ADD CONSTRAINT unidades_medida_nombre_key UNIQUE (nombre);


--
-- TOC entry 4855 (class 2606 OID 16791)
-- Name: unidades_medida unidades_medida_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.unidades_medida
    ADD CONSTRAINT unidades_medida_pkey PRIMARY KEY (id_unidad);


--
-- TOC entry 4841 (class 2606 OID 16620)
-- Name: usuarios usuarios_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_pkey PRIMARY KEY (id_usuario);


--
-- TOC entry 4843 (class 2606 OID 16622)
-- Name: usuarios usuarios_usuario_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_usuario_key UNIQUE (usuario);


--
-- TOC entry 4873 (class 1259 OID 17190)
-- Name: idx_corte_caja_estado; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_corte_caja_estado ON public.corte_caja USING btree (estado);


--
-- TOC entry 4874 (class 1259 OID 17191)
-- Name: idx_corte_caja_fecha; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_corte_caja_fecha ON public.corte_caja USING btree (fecha_apertura);


--
-- TOC entry 4875 (class 1259 OID 17189)
-- Name: idx_corte_caja_usuario; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_corte_caja_usuario ON public.corte_caja USING btree (id_usuario);


--
-- TOC entry 4862 (class 1259 OID 17031)
-- Name: mesas_numero_mesa_unique; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX mesas_numero_mesa_unique ON public.mesas USING btree (numero_mesa) WHERE (fechabaja IS NULL);


--
-- TOC entry 4891 (class 2620 OID 17150)
-- Name: roles tr_proteger_rol_administrador; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER tr_proteger_rol_administrador BEFORE DELETE ON public.roles FOR EACH ROW EXECUTE FUNCTION public.proteger_rol_administrador();


--
-- TOC entry 4885 (class 2606 OID 16863)
-- Name: detalles_pedido detalles_pedido_id_pedido_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detalles_pedido
    ADD CONSTRAINT detalles_pedido_id_pedido_fkey FOREIGN KEY (id_pedido) REFERENCES public.pedidos(id_pedido) ON DELETE CASCADE;


--
-- TOC entry 4886 (class 2606 OID 16931)
-- Name: detalles_pedido fk_detalle_pedido; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detalles_pedido
    ADD CONSTRAINT fk_detalle_pedido FOREIGN KEY (id_pedido) REFERENCES public.pedidos(id_pedido);


--
-- TOC entry 4887 (class 2606 OID 16936)
-- Name: detalles_pedido fk_detalle_platillo; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detalles_pedido
    ADD CONSTRAINT fk_detalle_platillo FOREIGN KEY (id_platillo) REFERENCES public.menu(id_platillo);


--
-- TOC entry 4877 (class 2606 OID 16916)
-- Name: menu fk_menu_categoria; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.menu
    ADD CONSTRAINT fk_menu_categoria FOREIGN KEY (id_categoria) REFERENCES public.categorias(id_categoria);


--
-- TOC entry 4878 (class 2606 OID 16921)
-- Name: menu fk_menu_unidad; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.menu
    ADD CONSTRAINT fk_menu_unidad FOREIGN KEY (id_unidad) REFERENCES public.unidades_medida(id_unidad);


--
-- TOC entry 4881 (class 2606 OID 16946)
-- Name: pedidos fk_pedido_cliente; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pedidos
    ADD CONSTRAINT fk_pedido_cliente FOREIGN KEY (id_cliente) REFERENCES public.clientes(id_cliente);


--
-- TOC entry 4882 (class 2606 OID 16962)
-- Name: pedidos fk_pedidos_mesas; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pedidos
    ADD CONSTRAINT fk_pedidos_mesas FOREIGN KEY (id_mesa) REFERENCES public.mesas(id_mesa);


--
-- TOC entry 4883 (class 2606 OID 16926)
-- Name: pedidos fk_pedidos_usuario; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pedidos
    ADD CONSTRAINT fk_pedidos_usuario FOREIGN KEY (id_mesero) REFERENCES public.usuarios(id_usuario);


--
-- TOC entry 4876 (class 2606 OID 16941)
-- Name: usuarios fk_usuario_rol; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT fk_usuario_rol FOREIGN KEY (id_rol) REFERENCES public.roles(id_rol);


--
-- TOC entry 4879 (class 2606 OID 16803)
-- Name: menu menu_id_categoria_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.menu
    ADD CONSTRAINT menu_id_categoria_fkey FOREIGN KEY (id_categoria) REFERENCES public.categorias(id_categoria);


--
-- TOC entry 4880 (class 2606 OID 16808)
-- Name: menu menu_id_unidad_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.menu
    ADD CONSTRAINT menu_id_unidad_fkey FOREIGN KEY (id_unidad) REFERENCES public.unidades_medida(id_unidad);


--
-- TOC entry 4888 (class 2606 OID 17051)
-- Name: menu_items menu_items_parent_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.menu_items
    ADD CONSTRAINT menu_items_parent_id_fkey FOREIGN KEY (parent_id) REFERENCES public.menu_items(id) ON DELETE CASCADE;


--
-- TOC entry 4884 (class 2606 OID 17011)
-- Name: pedidos pedidos_id_cliente_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pedidos
    ADD CONSTRAINT pedidos_id_cliente_fkey FOREIGN KEY (id_cliente) REFERENCES public.clientes(id_cliente);


--
-- TOC entry 4889 (class 2606 OID 17066)
-- Name: permisos_menu permisos_menu_id_usuario_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.permisos_menu
    ADD CONSTRAINT permisos_menu_id_usuario_fkey FOREIGN KEY (id_usuario) REFERENCES public.usuarios(id_usuario) ON DELETE CASCADE;


--
-- TOC entry 4890 (class 2606 OID 17071)
-- Name: permisos_menu permisos_menu_menu_item_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.permisos_menu
    ADD CONSTRAINT permisos_menu_menu_item_id_fkey FOREIGN KEY (menu_item_id) REFERENCES public.menu_items(id) ON DELETE CASCADE;


-- Completed on 2025-11-26 15:16:14

--
-- PostgreSQL database dump complete
--

\unrestrict e2wFJpmUqOFH7ojd0K3FmlYEK37d0F0s8VrxIoAfyYGovIcUZF0ZxvtQhzgf1Sl

