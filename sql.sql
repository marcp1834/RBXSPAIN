CREATE TABLE info_clientes (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(50) NOT NULL,
  apellido VARCHAR(50) NOT NULL,
  correo VARCHAR(100) NOT NULL,
  contrasena VARCHAR(255) NOT NULL,
  usuario VARCHAR(50) NOT NULL,
  telefono VARCHAR(20) NOT NULL,
  sexo VARCHAR(10) NOT NULL
);

CREATE TABLE productos (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(50) NOT NULL,
  cantidad INT NOT NULL,
  precio DECIMAL(6,2) NOT NULL
);

INSERT INTO `productos` (`id`, `nombre`, `cantidad`, `precio`) VALUES
(1, '100 Robux', 100, 1.00),
(2, '500 Robux', 500, 2.20),
(3, '1000 Robux', 1000, 5.40),
(4, '2000 Robux', 2000, 11.00),
(5, '5000 Robux', 5000, 27.00),
(6, '8000 Robux', 8000, 44.00),
(7, '10000 Robux', 10000, 54.00),
(8, '22500 Robux', 22500, 120.00);

CREATE TABLE carrito (
  id INT PRIMARY KEY AUTO_INCREMENT,
  usuario_id INT NOT NULL,
  producto_id INT NOT NULL,
  cantidad INT NOT NULL DEFAULT 1,
  FOREIGN KEY (usuario_id) REFERENCES info_clientes(id),
  FOREIGN KEY (producto_id) REFERENCES productos(id)
);

CREATE TABLE comentarios (
  id INT PRIMARY KEY AUTO_INCREMENT,
  usuario VARCHAR(50) NOT NULL,
  comentario TEXT NOT NULL,
  valoracion INT NOT NULL,
  fecha TIMESTAMP NOT NULL DEFAULT current_timestamp()
);

CREATE TABLE compras (
  id INT PRIMARY KEY AUTO_INCREMENT,
  id_producto INT NOT NULL,
  usuario_id INT NOT NULL,
  fecha DATETIME DEFAULT current_timestamp(),
  FOREIGN KEY (id_producto) REFERENCES productos(id),
  FOREIGN KEY (usuario_id) REFERENCES info_clientes(id)
);

CREATE TABLE recuperaciones (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT DEFAULT NULL,
  token VARCHAR(64) DEFAULT NULL,
  expira DATETIME DEFAULT NULL
);