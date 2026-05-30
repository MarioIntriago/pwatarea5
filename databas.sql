CREATE DATABASE IF NOT EXISTS pwapractica5 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pwapractica5;

DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(30) NOT NULL
);

INSERT INTO roles (name) VALUES ('Administrator'), ('Librarian'), ('Reader');

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(100) NOT NULL,
  role_id INT NOT NULL,
  FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(50) NOT NULL,
  author VARCHAR(50) NOT NULL,
  year INT,
  genre VARCHAR(50),
  quantity INT NOT NULL
);

CREATE TABLE transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  book_id INT NOT NULL,
  date_of_issue DATE,
  date_of_return DATE,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (book_id) REFERENCES books(id)
);

INSERT INTO users (username, email, password, role_id) VALUES
('admin', 'admin@biblioteca.com', '$2y$12$kH6yf8ctXczX7VCgGUD.l.0l2Wg8C5EmiLPwjY6JLNdSsM8gEZ3kq', 1),
('bibliotecario', 'biblio@biblioteca.com', '$2y$12$ry90F/kwcbMUWiuzKv6joO8Gwlg4/xU.n1gtSs2zK0DW4J5KJSpBW', 2),
('lector', 'lector@biblioteca.com', '$2y$12$xQVfjZcPzXjuwzqudQ.0U.X33bq.9TFJ7syQSWihxsYTcbgm2gLoe', 3);

INSERT INTO books (title, author, year, genre, quantity) VALUES
('Cien años de soledad', 'Gabriel García Márquez', 1967, 'Novela', 5),
('Don Quijote de la Mancha', 'Miguel de Cervantes', 1605, 'Clásico', 3),
('El principito', 'Antoine de Saint-Exupéry', 1943, 'Fábula', 6),
('La ciudad y los perros', 'Mario Vargas Llosa', 1963, 'Novela', 4),
('1984', 'George Orwell', 1949, 'Distopía', 4),
('Ficciones', 'Jorge Luis Borges', 1944, 'Cuentos', 2);
