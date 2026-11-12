-- Punto de Juego · Creación de la base y las tablas (MariaDB, utf8mb4)
-- Ejecutar con una cuenta administradora: sudo mariadb < sql/01-creacion.sql
--
-- Orden de dependencias: una tabla con FOREIGN KEY solo se puede crear
-- cuando ya existe la tabla a la que apunta.
--   jocs          (no depende de nadie)
--   activitats    -> jocs
--   inscripcions  -> activitats
--   rondes        -> activitats
--   partides      -> rondes, inscripcions (x2)
-- inscripcions y rondes solo dependen de activitats, así que su orden entre
-- ellas es indiferente; partides tiene que ser la última.

CREATE DATABASE puntdejoc
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE puntdejoc;

-- Catálogo de juegos. nom es obligatorio y único.
CREATE TABLE jocs (
    id INT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- Encuentros y torneos. Cada actividad es de un juego (FK joc_id).
CREATE TABLE activitats (
    id INT PRIMARY KEY,
    joc_id INT NOT NULL,
    titol VARCHAR(150) NOT NULL,
    tipus ENUM('trobada', 'torneig') NOT NULL,
    estat ENUM('borrador', 'oberta', 'en_curs', 'finalitzada') NOT NULL,
    data DATE NOT NULL,
    lloc VARCHAR(150) NOT NULL,
    places INT NOT NULL,
    CHECK (places > 0),
    FOREIGN KEY (joc_id) REFERENCES jocs(id)
) ENGINE=InnoDB;

-- Participantes apuntados a una actividad. Un alias no se repite en la misma actividad.
CREATE TABLE inscripcions (
    id INT PRIMARY KEY,
    activitat_id INT NOT NULL,
    alias VARCHAR(60) NOT NULL,
    UNIQUE (activitat_id, alias),
    FOREIGN KEY (activitat_id) REFERENCES activitats(id)
) ENGINE=InnoDB;

-- Rondas de un torneo. El número de ronda no se repite dentro de la actividad.
CREATE TABLE rondes (
    id INT PRIMARY KEY,
    activitat_id INT NOT NULL,
    numero INT NOT NULL,
    CHECK (numero > 0),
    UNIQUE (activitat_id, numero),
    FOREIGN KEY (activitat_id) REFERENCES activitats(id)
) ENGINE=InnoDB;

-- Partidas de una ronda entre dos inscripciones. resultat NULL = pendiente.
CREATE TABLE partides (
    id INT PRIMARY KEY,
    ronda_id INT NOT NULL,
    participant_a INT NOT NULL,
    participant_b INT NOT NULL,
    resultat ENUM('A', 'B', 'E') NULL,
    CHECK (participant_a <> participant_b),
    FOREIGN KEY (ronda_id) REFERENCES rondes(id),
    FOREIGN KEY (participant_a) REFERENCES inscripcions(id),
    FOREIGN KEY (participant_b) REFERENCES inscripcions(id)
) ENGINE=InnoDB;

SHOW TABLES;
