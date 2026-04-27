-- =======================================================
-- MIGRACION A MARKETPLACE P2P
-- Ejecutar este archivo despues del sql.sql original
-- =======================================================


-- ALTERACIONES A LA TABLA DE CLIENTES
-- Anadimos campos para roles, saldo y estadisticas del usuario

ALTER TABLE info_clientes ADD rol VARCHAR(20) NOT NULL DEFAULT 'comprador';
-- rol puede ser: comprador, vendedor o admin

ALTER TABLE info_clientes ADD saldo DECIMAL(10,2) NOT NULL DEFAULT 0.00;
-- saldo disponible que el usuario puede gastar o retirar

ALTER TABLE info_clientes ADD saldo_holding DECIMAL(10,2) NOT NULL DEFAULT 0.00;
-- saldo del vendedor retenido durante el periodo de seguridad (3-7 dias)

ALTER TABLE info_clientes ADD fecha_registro DATETIME NOT NULL DEFAULT current_timestamp();

ALTER TABLE info_clientes ADD valoracion_media DECIMAL(3,2) NOT NULL DEFAULT 0.00;
-- nota media de las valoraciones que ha recibido el vendedor (0 a 5)

ALTER TABLE info_clientes ADD ventas_completadas INT NOT NULL DEFAULT 0;


-- TABLA DE CATEGORIAS
-- Cada categoria representa una moneda de un videojuego
-- Ejemplos: Robux, V-Bucks, FIFA Coins, Gold WoW, etc.

CREATE TABLE categorias (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(50) NOT NULL,
  slug VARCHAR(50) NOT NULL,
  imagen VARCHAR(255) DEFAULT NULL,
  descripcion TEXT DEFAULT NULL,
  activa TINYINT NOT NULL DEFAULT 1
);

-- Insertamos algunas categorias de ejemplo
INSERT INTO categorias (nombre, slug, imagen, descripcion) VALUES
('Robux', 'robux', 'robux.png', 'Moneda virtual del juego Roblox'),
('V-Bucks', 'vbucks', 'vbucks.png', 'Moneda virtual de Fortnite'),
('FIFA Coins', 'fifa-coins', 'fifa.png', 'Monedas de FIFA Ultimate Team'),
('Gold WoW', 'gold-wow', 'wow.png', 'Oro de World of Warcraft'),
('CSGO Skins', 'csgo-skins', 'csgo.png', 'Skins de Counter-Strike');


-- TABLA DE OFERTAS
-- Cada vendedor crea sus propias ofertas dentro de una categoria

CREATE TABLE ofertas (
  id INT PRIMARY KEY AUTO_INCREMENT,
  vendedor_id INT NOT NULL,
  categoria_id INT NOT NULL,
  titulo VARCHAR(100) NOT NULL,
  descripcion TEXT NOT NULL,
  instrucciones TEXT DEFAULT NULL,
  -- instrucciones que el vendedor da al comprador despues de la compra
  precio_unitario DECIMAL(10,4) NOT NULL,
  -- precio por unidad (ej: 0.0054 EUR por 1 robux)
  unidad_minima INT NOT NULL DEFAULT 1,
  -- cantidad minima que se puede comprar de una vez
  stock_disponible INT NOT NULL DEFAULT 0,
  -- cantidad total que el vendedor tiene disponible
  estado VARCHAR(20) NOT NULL DEFAULT 'activa',
  -- estado: activa, pausada o agotada
  fecha_publicacion DATETIME NOT NULL DEFAULT current_timestamp(),
  FOREIGN KEY (vendedor_id) REFERENCES info_clientes(id),
  FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);


-- TABLA DE PEDIDOS (ORDERS)
-- Cuando un comprador compra a un vendedor se crea un pedido

CREATE TABLE pedidos (
  id INT PRIMARY KEY AUTO_INCREMENT,
  comprador_id INT NOT NULL,
  vendedor_id INT NOT NULL,
  oferta_id INT NOT NULL,
  cantidad INT NOT NULL,
  -- cantidad de unidades compradas (ej: 1000 robux)
  precio_total DECIMAL(10,2) NOT NULL,
  -- precio total en EUR que paga el comprador
  estado VARCHAR(30) NOT NULL DEFAULT 'esperando_info',
  -- estados posibles:
  -- esperando_info  - el comprador aun no ha mandado la URL del gamepass
  -- en_proceso      - el vendedor esta procesando el pago
  -- entregado       - el vendedor dice que ha entregado
  -- completado      - el comprador confirmo recepcion y se libero el saldo
  -- holding         - en periodo de seguridad antes de liberar
  -- en_disputa      - el comprador o vendedor abrio una disputa
  -- cancelado       - se cancelo el pedido
  gamepass_url VARCHAR(500) DEFAULT NULL,
  -- URL del gamepass que el comprador comparte con el vendedor
  gamepass_robux INT DEFAULT NULL,
  -- cantidad de robux detectada automaticamente del gamepass
  captura_pago VARCHAR(255) DEFAULT NULL,
  -- imagen que sube el vendedor como prueba de pago
  fecha_creacion DATETIME NOT NULL DEFAULT current_timestamp(),
  fecha_entregado DATETIME DEFAULT NULL,
  fecha_recibido DATETIME DEFAULT NULL,
  fecha_liberacion DATETIME DEFAULT NULL,
  -- fecha en la que se liberaran los fondos al vendedor automaticamente
  FOREIGN KEY (comprador_id) REFERENCES info_clientes(id),
  FOREIGN KEY (vendedor_id) REFERENCES info_clientes(id),
  FOREIGN KEY (oferta_id) REFERENCES ofertas(id)
);


-- TABLA DE MENSAJES (CHAT DEL PEDIDO)
-- Cada pedido tiene un chat entre comprador y vendedor

CREATE TABLE mensajes (
  id INT PRIMARY KEY AUTO_INCREMENT,
  pedido_id INT NOT NULL,
  emisor_id INT NOT NULL,
  -- id del usuario que envia el mensaje
  mensaje TEXT NOT NULL,
  tipo VARCHAR(20) NOT NULL DEFAULT 'texto',
  -- tipo: texto, imagen o sistema (mensaje automatico)
  fecha DATETIME NOT NULL DEFAULT current_timestamp(),
  FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
  FOREIGN KEY (emisor_id) REFERENCES info_clientes(id)
);


-- TABLA DE VALORACIONES
-- El comprador puede valorar al vendedor despues de completar el pedido

CREATE TABLE valoraciones (
  id INT PRIMARY KEY AUTO_INCREMENT,
  pedido_id INT NOT NULL,
  comprador_id INT NOT NULL,
  vendedor_id INT NOT NULL,
  estrellas INT NOT NULL,
  -- de 1 a 5 estrellas
  comentario TEXT DEFAULT NULL,
  fecha DATETIME NOT NULL DEFAULT current_timestamp(),
  FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
  FOREIGN KEY (comprador_id) REFERENCES info_clientes(id),
  FOREIGN KEY (vendedor_id) REFERENCES info_clientes(id)
);


-- TABLA DE TRANSACCIONES
-- Registra todos los movimientos de saldo de los usuarios

CREATE TABLE transacciones (
  id INT PRIMARY KEY AUTO_INCREMENT,
  usuario_id INT NOT NULL,
  tipo VARCHAR(30) NOT NULL,
  -- tipos: deposito, compra, venta, holding, liberacion, retiro, devolucion
  importe DECIMAL(10,2) NOT NULL,
  -- positivo si suma saldo, negativo si lo resta
  pedido_id INT DEFAULT NULL,
  -- referencia al pedido relacionado (si aplica)
  descripcion VARCHAR(255) DEFAULT NULL,
  fecha DATETIME NOT NULL DEFAULT current_timestamp(),
  FOREIGN KEY (usuario_id) REFERENCES info_clientes(id)
);


-- TABLA DE PAGOS CRYPTO
-- Cada vez que un usuario hace un deposito con NOWPayments

CREATE TABLE pagos_crypto (
  id INT PRIMARY KEY AUTO_INCREMENT,
  usuario_id INT NOT NULL,
  payment_id VARCHAR(100) NOT NULL,
  -- id que devuelve NOWPayments
  importe_eur DECIMAL(10,2) NOT NULL,
  -- importe en EUR que se le acreditara al usuario
  importe_crypto DECIMAL(20,8) DEFAULT NULL,
  -- importe en la moneda crypto elegida
  moneda_crypto VARCHAR(10) NOT NULL,
  -- ETH, LTC, USDC, USDT, SOL
  direccion_pago VARCHAR(255) DEFAULT NULL,
  -- direccion crypto donde el usuario debe enviar el pago
  tx_hash VARCHAR(255) DEFAULT NULL,
  estado VARCHAR(30) NOT NULL DEFAULT 'pendiente',
  -- estados: pendiente, esperando_confirmacion, confirmado, fallido, expirado
  fecha_creacion DATETIME NOT NULL DEFAULT current_timestamp(),
  fecha_confirmacion DATETIME DEFAULT NULL,
  FOREIGN KEY (usuario_id) REFERENCES info_clientes(id)
);


-- TABLA DE RETIROS
-- Cuando el vendedor solicita retirar su saldo a una wallet crypto

CREATE TABLE retiros (
  id INT PRIMARY KEY AUTO_INCREMENT,
  vendedor_id INT NOT NULL,
  importe_eur DECIMAL(10,2) NOT NULL,
  moneda_crypto VARCHAR(10) NOT NULL,
  direccion_wallet VARCHAR(255) NOT NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'pendiente',
  -- estados: pendiente, procesado, rechazado
  tx_hash VARCHAR(255) DEFAULT NULL,
  fecha_solicitud DATETIME NOT NULL DEFAULT current_timestamp(),
  fecha_procesado DATETIME DEFAULT NULL,
  FOREIGN KEY (vendedor_id) REFERENCES info_clientes(id)
);
