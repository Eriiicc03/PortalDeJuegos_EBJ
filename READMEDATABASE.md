# Punto de Juego · Creación de la base de datos y datos iniciales

Material de apoyo del encargo 3. Las sentencias están preparadas para **MariaDB** y para el modelo que consulta el portal PHP.

## Antes de ejecutar

- Utiliza una cuenta autorizada para crear la base y sus tablas y cargar datos. La cuenta de lectura del portal no tiene esos permisos.
- Ejecuta los bloques en orden, en una base `puntdejoc` nueva. Si ya tienes una base con ese nombre, revisa su contenido antes de continuar: estas sentencias no borran ni sustituyen tablas existentes.
- No repitas la carga sobre tablas con estos mismos IDs: provocará errores por duplicados. No necesitas borrar tu trabajo para hacer las pruebas del encargo.
- Guarda el SQL de creación y el de carga en los archivos de tu repositorio indicados en el encargo.

## 1. Crear y seleccionar la base

```sql
CREATE DATABASE puntdejoc
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE puntdejoc;
```

## 2. Crear las tablas

Primero se crean las tablas referenciadas y después las que dependen de ellas. Se utilizan IDs explícitos y no hay borrado en cascada.

```sql
CREATE TABLE jocs (
    id INT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

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

CREATE TABLE inscripcions (
    id INT PRIMARY KEY,
    activitat_id INT NOT NULL,
    alias VARCHAR(60) NOT NULL,
    UNIQUE (activitat_id, alias),
    FOREIGN KEY (activitat_id) REFERENCES activitats(id)
) ENGINE=InnoDB;

CREATE TABLE rondes (
    id INT PRIMARY KEY,
    activitat_id INT NOT NULL,
    numero INT NOT NULL,
    CHECK (numero > 0),
    UNIQUE (activitat_id, numero),
    FOREIGN KEY (activitat_id) REFERENCES activitats(id)
) ENGINE=InnoDB;

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
```

## 3. Insertar los datos iniciales

Los nombres internos coinciden con el código proporcionado. `trobada` significa encuentro; `torneig`, torneo; `oberta`, inscripción abierta; y `en_curs`, en curso. Conserva el ID 2 del torneo: el prototipo lo utiliza para consultar su clasificación. Fechas y lugares son datos ficticios de demostración.

```sql
USE puntdejoc;

INSERT INTO jocs (id, nom) VALUES
    (1, 'Ajedrez'),
    (2, 'Damas'),
    (3, 'Juego de cartas');

INSERT INTO activitats
    (id, joc_id, titol, tipus, estat, data, lloc, places)
VALUES
    (1, 3, 'Tarde de juegos', 'trobada', 'oberta',
     '2026-11-12', 'Local de la asociación', 16),
    (2, 1, 'Liga de demostración', 'torneig', 'en_curs',
     '2026-11-19', 'Local de la asociación', 4),
    (3, 2, 'Torneo de damas', 'torneig', 'oberta',
     '2026-11-26', 'Biblioteca', 8);

INSERT INTO inscripcions (id, activitat_id, alias) VALUES
    (1, 2, 'Aina'),
    (2, 2, 'Biel'),
    (3, 2, 'Cris'),
    (4, 2, 'Dani');

INSERT INTO rondes (id, activitat_id, numero) VALUES
    (1, 2, 1),
    (2, 2, 2),
    (3, 2, 3);

INSERT INTO partides
    (id, ronda_id, participant_a, participant_b, resultat)
VALUES
    (1, 1, 1, 2, 'A'),
    (2, 1, 3, 4, 'E'),
    (3, 2, 1, 3, NULL),
    (4, 2, 2, 4, NULL),
    (5, 3, 1, 4, NULL),
    (6, 3, 2, 3, NULL);
```

`A` indica victoria del participante A, `B` victoria del B y `E` empate. `NULL`, sin comillas, indica una partida pendiente.

## 4. Comprobar la carga

```sql
SHOW TABLES;

SELECT 'jocs' AS tabla, COUNT(*) AS registros FROM jocs
UNION ALL
SELECT 'activitats', COUNT(*) FROM activitats
UNION ALL
SELECT 'inscripcions', COUNT(*) FROM inscripcions
UNION ALL
SELECT 'rondes', COUNT(*) FROM rondes
UNION ALL
SELECT 'partides', COUNT(*) FROM partides;
```

Recuentos esperados: **3 juegos, 3 actividades, 4 inscripciones, 3 rondas y 6 partidas**.

En el portal, antes de modificar resultados: Aina tiene 3 puntos; Cris y Dani, 1; Biel, 0. Las posiciones son 1, 2, 2 y 4. El programa aplica victoria 3 puntos, empate 1 y derrota 0; las partidas pendientes no cuentan.

## Qué debes completar tú

La creación y la carga se proporcionan como apoyo. Debes leerlas, relacionarlas con el modelo y justificar el orden de ejecución. Siguen siendo trabajo del equipo las consultas del encargo, la actualización de resultados, las pruebas de restricciones, la cuenta y sus permisos, la conexión PHP y las evidencias individuales.

Las claves foráneas no garantizan por sí solas que los dos participantes de una partida estén inscritos en la actividad de esa ronda ni que jueguen una sola vez por ronda. Reconoce esos límites al analizar el modelo.
