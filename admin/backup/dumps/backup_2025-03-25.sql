--
-- PostgreSQL database dump
--

-- Dumped from database version 17.2
-- Dumped by pg_dump version 17.2

-- Started on 2025-03-25 12:43:50

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
-- TOC entry 4 (class 2615 OID 2200)
-- Name: public; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA public;


--
-- TOC entry 4934 (class 0 OID 0)
-- Dependencies: 4
-- Name: SCHEMA public; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON SCHEMA public IS 'standard public schema';


--
-- TOC entry 233 (class 1255 OID 33032)
-- Name: add_order_line(integer, integer, integer, numeric); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.add_order_line(p_order_id integer, p_produit_id integer, p_quantite integer, p_prix_unitaire numeric) RETURNS void
    LANGUAGE plpgsql
    AS '
BEGIN
    INSERT INTO order_lines(order_id, produit_id, quantite, prix_unitaire)
    VALUES (p_order_id, p_produit_id, p_quantite, p_prix_unitaire);
    
    -- Mise à jour du stock du produit
    PERFORM update_product_stock(p_produit_id, p_quantite);
END;
';


--
-- TOC entry 232 (class 1255 OID 33031)
-- Name: create_order(integer, numeric); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.create_order(p_utilisateur_id integer, p_montant_total numeric) RETURNS integer
    LANGUAGE plpgsql
    AS '
DECLARE
    new_order_id INTEGER;
BEGIN
    INSERT INTO orders(utilisateur_id, montant_total)
    VALUES (p_utilisateur_id, p_montant_total)
    RETURNING id INTO new_order_id;
    RETURN new_order_id;
END;
';


--
-- TOC entry 231 (class 1255 OID 33030)
-- Name: update_product_stock(integer, integer); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.update_product_stock(p_produit_id integer, p_quantite integer) RETURNS void
    LANGUAGE plpgsql
    AS '
BEGIN
    UPDATE products
    SET stock = stock - p_quantite
    WHERE id = p_produit_id AND stock >= p_quantite;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION ''Stock insuffisant pour le produit %'', p_produit_id;
    END IF;
END;
';


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 220 (class 1259 OID 32946)
-- Name: categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.categories (
    id integer NOT NULL,
    nom character varying(100) NOT NULL,
    description text
);


--
-- TOC entry 219 (class 1259 OID 32945)
-- Name: categories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.categories_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 4935 (class 0 OID 0)
-- Dependencies: 219
-- Name: categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.categories_id_seq OWNED BY public.categories.id;


--
-- TOC entry 230 (class 1259 OID 33034)
-- Name: images_products; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.images_products (
    id integer NOT NULL,
    produit_id integer NOT NULL,
    url_image character varying(255) NOT NULL,
    ordre integer DEFAULT 0,
    date_ajout timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- TOC entry 229 (class 1259 OID 33033)
-- Name: images_products_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.images_products_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 4936 (class 0 OID 0)
-- Dependencies: 229
-- Name: images_products_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.images_products_id_seq OWNED BY public.images_products.id;


--
-- TOC entry 226 (class 1259 OID 32999)
-- Name: order_lines; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.order_lines (
    id integer NOT NULL,
    order_id integer NOT NULL,
    produit_id integer NOT NULL,
    quantite integer NOT NULL,
    prix_unitaire numeric(10,2) NOT NULL,
    CONSTRAINT order_lines_quantite_check CHECK ((quantite > 0))
);


--
-- TOC entry 225 (class 1259 OID 32998)
-- Name: order_lines_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.order_lines_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 4937 (class 0 OID 0)
-- Dependencies: 225
-- Name: order_lines_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.order_lines_id_seq OWNED BY public.order_lines.id;


--
-- TOC entry 224 (class 1259 OID 32985)
-- Name: orders; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.orders (
    id integer NOT NULL,
    utilisateur_id integer NOT NULL,
    date_commande timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    montant_total numeric(10,2) NOT NULL,
    statut character varying(50) DEFAULT 'en cours'::character varying NOT NULL
);


--
-- TOC entry 223 (class 1259 OID 32984)
-- Name: orders_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.orders_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 4938 (class 0 OID 0)
-- Dependencies: 223
-- Name: orders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.orders_id_seq OWNED BY public.orders.id;


--
-- TOC entry 228 (class 1259 OID 33017)
-- Name: payments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.payments (
    id integer NOT NULL,
    order_id integer NOT NULL,
    mode_paiement character varying(50) NOT NULL,
    reference_transaction character varying(100),
    statut character varying(50) DEFAULT 'en attente'::character varying NOT NULL,
    date_paiement timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- TOC entry 227 (class 1259 OID 33016)
-- Name: payments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.payments_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 4939 (class 0 OID 0)
-- Dependencies: 227
-- Name: payments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.payments_id_seq OWNED BY public.payments.id;


--
-- TOC entry 222 (class 1259 OID 32955)
-- Name: products; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.products (
    id integer NOT NULL,
    titre character varying(150) NOT NULL,
    description text,
    prix numeric(10,2) NOT NULL,
    stock integer DEFAULT 0 NOT NULL,
    categorie_id integer,
    image_principale character varying(255),
    actif boolean DEFAULT true NOT NULL,
    date_creation timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT products_prix_check CHECK ((prix > (0)::numeric))
);


--
-- TOC entry 221 (class 1259 OID 32954)
-- Name: products_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.products_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 4940 (class 0 OID 0)
-- Dependencies: 221
-- Name: products_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.products_id_seq OWNED BY public.products.id;


--
-- TOC entry 218 (class 1259 OID 32933)
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id integer NOT NULL,
    nom character varying(100) NOT NULL,
    email character varying(150) NOT NULL,
    mot_de_passe character varying(255) NOT NULL,
    role character varying(20) DEFAULT 'client'::character varying NOT NULL,
    adresse text,
    telephone character varying(20),
    date_inscription timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- TOC entry 217 (class 1259 OID 32932)
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 4941 (class 0 OID 0)
-- Dependencies: 217
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- TOC entry 4731 (class 2604 OID 32949)
-- Name: categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories ALTER COLUMN id SET DEFAULT nextval('public.categories_id_seq'::regclass);


--
-- TOC entry 4743 (class 2604 OID 33037)
-- Name: images_products id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.images_products ALTER COLUMN id SET DEFAULT nextval('public.images_products_id_seq'::regclass);


--
-- TOC entry 4739 (class 2604 OID 33002)
-- Name: order_lines id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_lines ALTER COLUMN id SET DEFAULT nextval('public.order_lines_id_seq'::regclass);


--
-- TOC entry 4736 (class 2604 OID 32988)
-- Name: orders id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders ALTER COLUMN id SET DEFAULT nextval('public.orders_id_seq'::regclass);


--
-- TOC entry 4740 (class 2604 OID 33020)
-- Name: payments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments ALTER COLUMN id SET DEFAULT nextval('public.payments_id_seq'::regclass);


--
-- TOC entry 4732 (class 2604 OID 32958)
-- Name: products id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products ALTER COLUMN id SET DEFAULT nextval('public.products_id_seq'::regclass);


--
-- TOC entry 4728 (class 2604 OID 32936)
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- TOC entry 4918 (class 0 OID 32946)
-- Dependencies: 220
-- Data for Name: categories; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.categories (id, nom, description) VALUES (1, 'Furniture Assises', 'Inclut : chaises, fauteuils, canapés, tabourets…');
INSERT INTO public.categories (id, nom, description) VALUES (2, 'Furniture Tables & Bureaux', 'Inclut : tables à manger, tables basses, bureaux, consoles…');
INSERT INTO public.categories (id, nom, description) VALUES (3, 'Furniture Rangement', 'Inclut : armoires, commodes, bibliothèques, étagères, buffets…');
INSERT INTO public.categories (id, nom, description) VALUES (4, 'Furniture Décoration & Accessoires', 'Inclut : luminaires, tapis, miroirs, coussins, petits objets déco…');


--
-- TOC entry 4928 (class 0 OID 33034)
-- Dependencies: 230
-- Data for Name: images_products; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- TOC entry 4924 (class 0 OID 32999)
-- Dependencies: 226
-- Data for Name: order_lines; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- TOC entry 4922 (class 0 OID 32985)
-- Dependencies: 224
-- Data for Name: orders; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- TOC entry 4926 (class 0 OID 33017)
-- Dependencies: 228
-- Data for Name: payments; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- TOC entry 4920 (class 0 OID 32955)
-- Dependencies: 222
-- Data for Name: products; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (5, 'Lampe d''arabe', 'ccsqcsqcqcs', 2.00, 1, 1, '/public/uploads/products/https://dl.dropboxusercontent.com/scl/fi/m5kad2hlbn12utiguqxjd/product_1742681456_67df3570a799c.png?rlkey=zn3g7v3djdoz78ajiuxi1x4pn&dl=0', true, '2025-03-22 23:11:05.646021');


--
-- TOC entry 4916 (class 0 OID 32933)
-- Dependencies: 218
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.users (id, nom, email, mot_de_passe, role, adresse, telephone, date_inscription) VALUES (2, 'Naima Boudhakite', 'Naima@gmail', '$2y$10$/t6.HbgNEUkNBhgC1IEXK.TC62aEQ7geKxAl4mln39dhYCYviuIKG', 'client', NULL, NULL, '2025-03-18 14:39:34.211287');
INSERT INTO public.users (id, nom, email, mot_de_passe, role, adresse, telephone, date_inscription) VALUES (3, 'Paule', 'client@client.com', '$2y$10$LnQbsxEBvOLKrLHEtJsaF.9Ncn.B6Ri3sAEEaW7gv3aMUMrEr7iLe', 'client', NULL, NULL, '2025-03-18 17:52:12.821601');
INSERT INTO public.users (id, nom, email, mot_de_passe, role, adresse, telephone, date_inscription) VALUES (1, 'Younes Kouza', 'younes.kouza01@gmail.com', '$2y$10$yrkgZ0arp4ro1geSTTP3budkn3TCsyyz8FGRP1vKuYchSQKyMkXMu', 'admin', NULL, NULL, '2025-03-13 18:16:52.08185');


--
-- TOC entry 4942 (class 0 OID 0)
-- Dependencies: 219
-- Name: categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.categories_id_seq', 4, true);


--
-- TOC entry 4943 (class 0 OID 0)
-- Dependencies: 229
-- Name: images_products_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.images_products_id_seq', 9, true);


--
-- TOC entry 4944 (class 0 OID 0)
-- Dependencies: 225
-- Name: order_lines_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.order_lines_id_seq', 1, false);


--
-- TOC entry 4945 (class 0 OID 0)
-- Dependencies: 223
-- Name: orders_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.orders_id_seq', 1, false);


--
-- TOC entry 4946 (class 0 OID 0)
-- Dependencies: 227
-- Name: payments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.payments_id_seq', 1, false);


--
-- TOC entry 4947 (class 0 OID 0)
-- Dependencies: 221
-- Name: products_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.products_id_seq', 5, true);


--
-- TOC entry 4948 (class 0 OID 0)
-- Dependencies: 217
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.users_id_seq', 3, true);


--
-- TOC entry 4753 (class 2606 OID 32953)
-- Name: categories categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_pkey PRIMARY KEY (id);


--
-- TOC entry 4763 (class 2606 OID 33041)
-- Name: images_products images_products_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.images_products
    ADD CONSTRAINT images_products_pkey PRIMARY KEY (id);


--
-- TOC entry 4759 (class 2606 OID 33005)
-- Name: order_lines order_lines_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_lines
    ADD CONSTRAINT order_lines_pkey PRIMARY KEY (id);


--
-- TOC entry 4757 (class 2606 OID 32992)
-- Name: orders orders_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_pkey PRIMARY KEY (id);


--
-- TOC entry 4761 (class 2606 OID 33024)
-- Name: payments payments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_pkey PRIMARY KEY (id);


--
-- TOC entry 4755 (class 2606 OID 32966)
-- Name: products products_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_pkey PRIMARY KEY (id);


--
-- TOC entry 4749 (class 2606 OID 32944)
-- Name: users users_email_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_key UNIQUE (email);


--
-- TOC entry 4751 (class 2606 OID 32942)
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- TOC entry 4769 (class 2606 OID 33042)
-- Name: images_products images_products_produit_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.images_products
    ADD CONSTRAINT images_products_produit_id_fkey FOREIGN KEY (produit_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- TOC entry 4766 (class 2606 OID 33006)
-- Name: order_lines order_lines_order_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_lines
    ADD CONSTRAINT order_lines_order_id_fkey FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- TOC entry 4767 (class 2606 OID 33011)
-- Name: order_lines order_lines_produit_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_lines
    ADD CONSTRAINT order_lines_produit_id_fkey FOREIGN KEY (produit_id) REFERENCES public.products(id);


--
-- TOC entry 4765 (class 2606 OID 32993)
-- Name: orders orders_utilisateur_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_utilisateur_id_fkey FOREIGN KEY (utilisateur_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- TOC entry 4768 (class 2606 OID 33025)
-- Name: payments payments_order_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_order_id_fkey FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- TOC entry 4764 (class 2606 OID 32967)
-- Name: products products_categorie_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_categorie_id_fkey FOREIGN KEY (categorie_id) REFERENCES public.categories(id) ON DELETE SET NULL;


-- Completed on 2025-03-25 12:43:50

--
-- PostgreSQL database dump complete
--

