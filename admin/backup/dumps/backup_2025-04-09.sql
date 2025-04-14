--
-- PostgreSQL database dump
--

-- Dumped from database version 17.2
-- Dumped by pg_dump version 17.2

-- Started on 2025-04-09 15:18:57

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
-- TOC entry 231 (class 1255 OID 33032)
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
-- TOC entry 230 (class 1255 OID 33031)
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
-- TOC entry 229 (class 1255 OID 33030)
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
-- TOC entry 4921 (class 0 OID 0)
-- Dependencies: 219
-- Name: categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.categories_id_seq OWNED BY public.categories.id;


--
-- TOC entry 228 (class 1259 OID 33034)
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
-- TOC entry 227 (class 1259 OID 33033)
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
-- TOC entry 4922 (class 0 OID 0)
-- Dependencies: 227
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
-- TOC entry 4923 (class 0 OID 0)
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
-- TOC entry 4924 (class 0 OID 0)
-- Dependencies: 223
-- Name: orders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.orders_id_seq OWNED BY public.orders.id;


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
-- TOC entry 4925 (class 0 OID 0)
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
-- TOC entry 4926 (class 0 OID 0)
-- Dependencies: 217
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- TOC entry 4726 (class 2604 OID 32949)
-- Name: categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories ALTER COLUMN id SET DEFAULT nextval('public.categories_id_seq'::regclass);


--
-- TOC entry 4735 (class 2604 OID 33037)
-- Name: images_products id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.images_products ALTER COLUMN id SET DEFAULT nextval('public.images_products_id_seq'::regclass);


--
-- TOC entry 4734 (class 2604 OID 33002)
-- Name: order_lines id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_lines ALTER COLUMN id SET DEFAULT nextval('public.order_lines_id_seq'::regclass);


--
-- TOC entry 4731 (class 2604 OID 32988)
-- Name: orders id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders ALTER COLUMN id SET DEFAULT nextval('public.orders_id_seq'::regclass);


--
-- TOC entry 4727 (class 2604 OID 32958)
-- Name: products id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products ALTER COLUMN id SET DEFAULT nextval('public.products_id_seq'::regclass);


--
-- TOC entry 4723 (class 2604 OID 32936)
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- TOC entry 4907 (class 0 OID 32946)
-- Dependencies: 220
-- Data for Name: categories; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.categories (id, nom, description) VALUES (2, 'Furniture Tables & Bureaux', 'Inclut : tables à manger, tables basses, bureaux, consoles…');
INSERT INTO public.categories (id, nom, description) VALUES (3, 'Furniture Rangement', 'Inclut : armoires, commodes, bibliothèques, étagères, buffets…');
INSERT INTO public.categories (id, nom, description) VALUES (4, 'Furniture Décoration & Accessoires', 'Inclut : luminaires, tapis, miroirs, coussins, petits objets déco…');
INSERT INTO public.categories (id, nom, description) VALUES (1, 'Furniture Assises', 'Inclut : chaises, fauteuils, canapés, tabourets….$
');


--
-- TOC entry 4915 (class 0 OID 33034)
-- Dependencies: 228
-- Data for Name: images_products; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.images_products (id, produit_id, url_image, ordre, date_ajout) VALUES (14, 37, 'admin/public/images/Furniture Décoration & Accessoires/product_1744033256_67f3d5e80b67c_0.jpg', 2, '2025-04-07 15:40:56.047438');
INSERT INTO public.images_products (id, produit_id, url_image, ordre, date_ajout) VALUES (15, 37, 'admin/public/images/Furniture Décoration & Accessoires/product_1744033271_67f3d5f725593_0.jpg', 1, '2025-04-07 15:41:11.153788');


--
-- TOC entry 4913 (class 0 OID 32999)
-- Dependencies: 226
-- Data for Name: order_lines; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.order_lines (id, order_id, produit_id, quantite, prix_unitaire) VALUES (1, 5, 25, 1, 749.99);
INSERT INTO public.order_lines (id, order_id, produit_id, quantite, prix_unitaire) VALUES (2, 6, 25, 1, 749.99);
INSERT INTO public.order_lines (id, order_id, produit_id, quantite, prix_unitaire) VALUES (3, 7, 12, 1, 349.99);
INSERT INTO public.order_lines (id, order_id, produit_id, quantite, prix_unitaire) VALUES (4, 8, 12, 1, 349.99);
INSERT INTO public.order_lines (id, order_id, produit_id, quantite, prix_unitaire) VALUES (5, 9, 21, 1, 419.99);
INSERT INTO public.order_lines (id, order_id, produit_id, quantite, prix_unitaire) VALUES (6, 10, 11, 1, 899.99);
INSERT INTO public.order_lines (id, order_id, produit_id, quantite, prix_unitaire) VALUES (7, 11, 14, 1, 399.99);
INSERT INTO public.order_lines (id, order_id, produit_id, quantite, prix_unitaire) VALUES (8, 12, 11, 1, 899.99);


--
-- TOC entry 4911 (class 0 OID 32985)
-- Dependencies: 224
-- Data for Name: orders; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.orders (id, utilisateur_id, date_commande, montant_total, statut) VALUES (6, 3, '2025-04-03 13:21:32.50131', 749.99, 'completed');
INSERT INTO public.orders (id, utilisateur_id, date_commande, montant_total, statut) VALUES (5, 3, '2025-04-03 13:15:32.447229', 749.99, 'completed');
INSERT INTO public.orders (id, utilisateur_id, date_commande, montant_total, statut) VALUES (7, 3, '2025-04-03 18:59:32.240408', 349.99, 'livré');
INSERT INTO public.orders (id, utilisateur_id, date_commande, montant_total, statut) VALUES (8, 3, '2025-04-03 19:33:36.848644', 349.99, 'livré');
INSERT INTO public.orders (id, utilisateur_id, date_commande, montant_total, statut) VALUES (9, 1, '2025-04-04 13:37:54.51717', 419.99, 'livré');
INSERT INTO public.orders (id, utilisateur_id, date_commande, montant_total, statut) VALUES (10, 3, '2025-04-07 09:52:06.620178', 899.99, 'livré');
INSERT INTO public.orders (id, utilisateur_id, date_commande, montant_total, statut) VALUES (11, 3, '2025-04-07 18:31:58.977538', 399.99, 'livré');
INSERT INTO public.orders (id, utilisateur_id, date_commande, montant_total, statut) VALUES (12, 3, '2025-04-07 23:08:55.150074', 899.99, 'livré');


--
-- TOC entry 4909 (class 0 OID 32955)
-- Dependencies: 222
-- Data for Name: products; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (12, 'Banquette Élégante', 'Banquette élégante avec revêtement en velours et pieds en laiton', 349.99, 6, 1, 'admin/public/images/Furniture Assises/6.png', true, '2025-03-31 19:24:52.22058');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (13, 'Table à Manger Extensible', 'Table à manger extensible pouvant accueillir jusqu''à 10 personnes', 699.99, 7, 2, 'admin/public/images/Furniture Tables & Bureaux/1.png', true, '2025-03-31 19:24:52.22058');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (14, 'Bureau Moderne', 'Bureau moderne avec plateau en verre trempé et structure en acier', 399.99, 6, 2, 'admin/public/images/Furniture Tables & Bureaux/2.png', true, '2025-03-31 19:24:52.22058');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (11, 'Canapé d''Angle', 'Canapé d''angle modulable en tissu premium avec coussins décoratifs', 899.99, 1, 1, 'admin/public/images/Furniture Assises/5.png', true, '2025-03-31 19:24:52.22058');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (9, 'Fauteuil Lounge', 'Fauteuil lounge vintage en cuir véritable avec piètement en bois massif', 599.99, 5, 1, 'admin/public/images/Furniture Assises/3.png', true, '2025-03-31 19:24:52.22058');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (10, 'Chaise Design', 'Chaise design minimaliste avec structure légère et assise ergonomique', 179.99, 20, 1, 'admin/public/images/Furniture Assises/4.png', true, '2025-03-31 19:24:52.22058');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (22, 'Bibliothèque Modulaire', 'Bibliothèque modulaire et évolutive avec compartiments réglables', 449.99, 7, 3, 'admin/public/images/Furniture Rangement/1.png', true, '2025-03-31 19:24:52.22058');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (23, 'Commode Vintage', 'Commode vintage en bois massif avec trois tiroirs et finition patinée', 399.99, 5, 3, 'admin/public/images/Furniture Rangement/2.png', true, '2025-03-31 19:24:52.22058');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (24, 'Étagère Murale', 'Étagère murale design avec structure en métal et tablettes en bois', 199.99, 12, 3, 'admin/public/images/Furniture Rangement/3.png', true, '2025-03-31 19:24:52.22058');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (32, 'Tapis Contemporain', 'Tapis contemporain à poils courts et motifs abstraits', 249.99, 7, 4, 'admin/public/images/Furniture Décoration & Accessoires/4.png', true, '2025-03-31 19:24:52.22058');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (26, 'Buffet Scandinave', 'Buffet scandinave en chêne massif avec portes et tiroirs', 599.99, 6, 3, 'admin/public/images/Furniture Rangement/5.png', true, '2025-03-31 19:24:52.22058');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (27, 'Meuble TV Minimaliste', 'Meuble TV minimaliste avec espaces de rangement et passage de câbles', 329.99, 9, 3, 'admin/public/images/Furniture Rangement/6.png', true, '2025-03-31 19:24:52.22058');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (28, 'Console d''Entrée', 'Console d''entrée élégante avec tiroirs et tablette inférieure', 279.99, 8, 3, 'admin/public/images/Furniture Rangement/7.png', true, '2025-03-31 19:24:52.22058');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (25, 'Armoire Contemporaine', 'Armoire contemporaine avec portes coulissantes et rangements intérieurs', 749.99, 3, 3, 'admin/public/images/Furniture Rangement/4.png', true, '2025-03-31 19:24:52.22058');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (33, 'Miroir Rond Doré', 'Miroir rond avec cadre doré pour une touche d''élégance', 159.99, 9, 4, 'admin/public/images/Furniture Décoration & Accessoires/5.png', true, '2025-03-31 19:24:52.22058');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (34, 'Miroir Mural Design', 'Grand miroir mural design avec cadre en bois naturel', 219.99, 6, 4, 'admin/public/images/Furniture Décoration & Accessoires/6.png', true, '2025-03-31 19:24:52.22058');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (35, 'Miroir Mural Design', 'Grand miroir mural design avec cadre en bois naturel', 219.99, 6, 4, 'admin/public/images/Furniture Décoration & Accessoires/6.png', true, '2025-03-31 19:24:56.563196');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (15, 'Table Basse Design', 'Table basse design avec plateau en marbre et piètement en métal doré', 349.99, 6, 2, 'admin/public/images/Furniture Tables & Bureaux/3.png', true, '2025-03-31 19:24:52.22058');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (16, 'Bureau d''Angle', 'Bureau d''angle ergonomique avec espace de rangement intégré', 449.99, 5, 2, 'admin/public/images/Furniture Tables & Bureaux/4.png', true, '2025-03-31 19:24:52.22058');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (17, 'Table d''Appoint', 'Table d''appoint minimaliste parfaite pour le salon ou la chambre', 149.99, 15, 2, 'admin/public/images/Furniture Tables & Bureaux/5.png', true, '2025-03-31 19:24:52.22058');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (37, 'Tapis maroccain', 'Originaire du Maroc du sud.', 48.56, 86, 4, 'admin/public/images/Furniture Décoration & Accessoires/product_1744032990_67f3d4de13d8f.jpg', true, '2025-04-07 15:36:30.082193');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (29, 'Lampe de Table Artisanale', 'Lampe de table artisanale avec abat-jour en tissu et pied en céramique', 129.99, 12, 4, 'admin/public/images/Furniture Décoration & Accessoires/1.png', true, '2025-03-31 19:24:52.22058');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (30, 'Lampadaire Design', 'Lampadaire design avec variateur d''intensité et abat-jour orientable', 199.99, 8, 4, 'admin/public/images/Furniture Décoration & Accessoires/2.png', true, '2025-03-31 19:24:52.22058');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (31, 'Tapis Ethnique', 'Tapis ethnique aux motifs géométriques et couleurs chaudes', 179.99, 10, 4, 'admin/public/images/Furniture Décoration & Accessoires/3.png', true, '2025-03-31 19:24:52.22058');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (18, 'Table Console', 'Table console polyvalente pour l''entrée ou le salon', 229.99, 10, 2, 'admin/public/images/Furniture Tables & Bureaux/6.png', true, '2025-03-31 19:24:52.22058');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (19, 'Bureau Secrétaire', 'Bureau secrétaire compact avec abattant et tiroirs de rangement', 379.99, 6, 2, 'admin/public/images/Furniture Tables & Bureaux/7.png', true, '2025-03-31 19:24:52.22058');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (20, 'Table Ronde', 'Table ronde en bois massif avec finition naturelle', 329.99, 8, 2, 'admin/public/images/Furniture Tables & Bureaux/8.png', true, '2025-03-31 19:24:52.22058');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (7, 'Fauteuil Scandinave', 'Fauteuil au design scandinave avec accoudoirs en bois naturel et tissu confortable', 299.99, 10, 1, 'admin/public/images/Furniture Assises/1.png', true, '2025-03-31 19:24:52.22058');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (8, 'Chaise Moderne', 'Chaise moderne au design épuré avec piètement en métal et assise rembourrée', 149.99, 15, 1, 'admin/public/images/Furniture Assises/2.png', true, '2025-03-31 19:24:52.22058');
INSERT INTO public.products (id, titre, description, prix, stock, categorie_id, image_principale, actif, date_creation) VALUES (21, 'Bureau Industriel', 'Bureau de style industriel avec plateau en bois recyclé et structure métallique', 419.99, 4, 2, 'admin/public/images/Furniture Tables & Bureaux/9.png', true, '2025-03-31 19:24:52.22058');


--
-- TOC entry 4905 (class 0 OID 32933)
-- Dependencies: 218
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.users (id, nom, email, mot_de_passe, role, adresse, telephone, date_inscription) VALUES (2, 'Naima Boudhakite', 'Naima@gmail', '$2y$10$/t6.HbgNEUkNBhgC1IEXK.TC62aEQ7geKxAl4mln39dhYCYviuIKG', 'client', NULL, NULL, '2025-03-18 14:39:34.211287');
INSERT INTO public.users (id, nom, email, mot_de_passe, role, adresse, telephone, date_inscription) VALUES (3, 'Paule', 'client@client.com', '$2y$10$LnQbsxEBvOLKrLHEtJsaF.9Ncn.B6Ri3sAEEaW7gv3aMUMrEr7iLe', 'client', 'Rue Emile Vandervelde 42', '0488853432', '2025-04-07 18:31:53.246475');
INSERT INTO public.users (id, nom, email, mot_de_passe, role, adresse, telephone, date_inscription) VALUES (1, 'Younes Kouza', 'younes.kouza01@gmail.com', '$2y$10$yrkgZ0arp4ro1geSTTP3budkn3TCsyyz8FGRP1vKuYchSQKyMkXMu', 'admin', 'Rue Emile Vandervelde 42', '0488853432', '2025-04-07 18:32:30.460711');


--
-- TOC entry 4927 (class 0 OID 0)
-- Dependencies: 219
-- Name: categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.categories_id_seq', 4, true);


--
-- TOC entry 4928 (class 0 OID 0)
-- Dependencies: 227
-- Name: images_products_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.images_products_id_seq', 15, true);


--
-- TOC entry 4929 (class 0 OID 0)
-- Dependencies: 225
-- Name: order_lines_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.order_lines_id_seq', 8, true);


--
-- TOC entry 4930 (class 0 OID 0)
-- Dependencies: 223
-- Name: orders_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.orders_id_seq', 12, true);


--
-- TOC entry 4931 (class 0 OID 0)
-- Dependencies: 221
-- Name: products_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.products_id_seq', 37, true);


--
-- TOC entry 4932 (class 0 OID 0)
-- Dependencies: 217
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.users_id_seq', 3, true);


--
-- TOC entry 4745 (class 2606 OID 32953)
-- Name: categories categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_pkey PRIMARY KEY (id);


--
-- TOC entry 4753 (class 2606 OID 33041)
-- Name: images_products images_products_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.images_products
    ADD CONSTRAINT images_products_pkey PRIMARY KEY (id);


--
-- TOC entry 4751 (class 2606 OID 33005)
-- Name: order_lines order_lines_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_lines
    ADD CONSTRAINT order_lines_pkey PRIMARY KEY (id);


--
-- TOC entry 4749 (class 2606 OID 32992)
-- Name: orders orders_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_pkey PRIMARY KEY (id);


--
-- TOC entry 4747 (class 2606 OID 32966)
-- Name: products products_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_pkey PRIMARY KEY (id);


--
-- TOC entry 4741 (class 2606 OID 32944)
-- Name: users users_email_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_key UNIQUE (email);


--
-- TOC entry 4743 (class 2606 OID 32942)
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- TOC entry 4758 (class 2606 OID 33042)
-- Name: images_products images_products_produit_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.images_products
    ADD CONSTRAINT images_products_produit_id_fkey FOREIGN KEY (produit_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- TOC entry 4756 (class 2606 OID 33006)
-- Name: order_lines order_lines_order_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_lines
    ADD CONSTRAINT order_lines_order_id_fkey FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- TOC entry 4757 (class 2606 OID 33011)
-- Name: order_lines order_lines_produit_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_lines
    ADD CONSTRAINT order_lines_produit_id_fkey FOREIGN KEY (produit_id) REFERENCES public.products(id);


--
-- TOC entry 4755 (class 2606 OID 32993)
-- Name: orders orders_utilisateur_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_utilisateur_id_fkey FOREIGN KEY (utilisateur_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- TOC entry 4754 (class 2606 OID 32967)
-- Name: products products_categorie_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_categorie_id_fkey FOREIGN KEY (categorie_id) REFERENCES public.categories(id) ON DELETE SET NULL;


-- Completed on 2025-04-09 15:18:57

--
-- PostgreSQL database dump complete
--

