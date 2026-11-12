# Punto de Juego · Base de datos (AEA3)

Análisis del modelo, orden de ejecución, cuenta web, conexión PHP y pruebas. El SQL está en [`sql/`](../sql) y el material de apoyo original, en [`READMEDATABASE.md`](../READMEDATABASE.md).

## 1. Del modelo a las tablas

| Tabla | Qué guarda | Claves y restricciones | Regla del modelo que cumple |
|---|---|---|---|
| `jocs` | Catálogo de juegos | PK `id`, `nom` NOT NULL UNIQUE | No hay dos juegos con el mismo nombre |
| `activitats` | Encuentros y torneos | PK `id`, FK `joc_id` → `jocs`, ENUM `tipus` y `estat`, CHECK `places > 0` | Toda actividad es de un juego que existe; tipo y estado solo pueden tomar los valores previstos |
| `inscripcions` | Alias apuntados a una actividad | PK `id`, FK `activitat_id` → `activitats`, UNIQUE (`activitat_id`, `alias`) | Un alias no se repite dentro de la misma actividad |
| `rondes` | Rondas de un torneo | PK `id`, FK `activitat_id` → `activitats`, CHECK `numero > 0`, UNIQUE (`activitat_id`, `numero`) | No hay dos «ronda 2» en el mismo torneo |
| `partides` | Emparejamientos y resultado | PK `id`, FK `ronda_id` → `rondes`, FK `participant_a` y `participant_b` → `inscripcions`, ENUM `resultat` NULL, CHECK `participant_a <> participant_b` | Nadie juega contra sí mismo; `NULL` = pendiente |

Decisiones comunes:
- `ENGINE=InnoDB` en todas: es el motor que aplica las claves foráneas y las transacciones.
- `utf8mb4` con `utf8mb4_unicode_ci`: acentos y emojis sin problemas.
- IDs explícitos (sin `AUTO_INCREMENT`): el portal busca el torneo con ID 2 y los datos de prueba tienen que ser reproducibles.
- Sin `ON DELETE CASCADE`: borrar una inscripción no borra en silencio sus partidas. MariaDB lo rechaza (error 1451).
- Todos los campos son `NOT NULL` salvo `resultat`, porque una partida pendiente todavía no tiene resultado.

```
jocs ──< activitats ──< inscripcions >──┐ (participant_a, participant_b)
              │                         │
              └──────< rondes ──< partides
```

## 2. Orden de ejecución

Una `FOREIGN KEY` solo se puede declarar si la tabla a la que apunta ya existe. Del mismo modo, un `INSERT` en una tabla hija solo entra si la fila padre ya está cargada. Por eso tanto los `CREATE TABLE` como los `INSERT` siguen este orden:

1. `jocs`: no depende de ninguna tabla.
2. `activitats`: depende de `jocs`.
3. `inscripcions` y `rondes`: las dos dependen solo de `activitats`, así que el orden entre ellas da igual.
4. `partides`: depende de `rondes` y dos veces de `inscripcions`. Tiene que ir la última.

Si se intenta en otro orden, por ejemplo creando `partides` antes que `rondes`, MariaDB devuelve `ERROR 1005 … (errno: 150 "Foreign key constraint is incorrectly formed")`. Al borrar, el orden es el inverso.

Archivos, en el orden en que se ejecutan:

| Archivo | Cuenta | Qué hace |
|---|---|---|
| `sql/01-creacion.sql` | administradora (`sudo mariadb`) | `CREATE DATABASE` + 5 `CREATE TABLE` |
| `sql/02-carga.sql` | administradora | `INSERT` iniciales + recuentos (3, 3, 4, 3, 6) |
| `sql/03-cuenta.sql` | administradora | Cuenta `puntdejoc@localhost` con solo `SELECT` |
| `sql/04-consultas.sql` | web (`puntdejoc`) | Participantes del torneo 2, partidas pendientes, actividades abiertas |
| `sql/05-actualizacion.sql` | administradora | `UPDATE` de la partida 3 |
| `sql/06-pruebas-restricciones.sql` | administradora, con `--force` | Errores esperados y reglas que no se garantizan |

`scripts/instalar.sh` ejecuta del paso 01 al 03, configura FPM y despliega el portal.

## 3. Cuenta web y permisos

```sql
CREATE USER 'puntdejoc'@'localhost' IDENTIFIED BY '…';
GRANT SELECT ON puntdejoc.* TO 'puntdejoc'@'localhost';
```

- **Mínimo privilegio:** el portal solo lee, así que solo tiene `SELECT` y solo sobre `puntdejoc`. Si alguien aprovechara un fallo del portal, no podría modificar datos ni ver otras bases.
- **Solo local:** `@'localhost'` limita la cuenta a conexiones desde la propia máquina, por el socket Unix `/run/mysqld/mysqld.sock`.
- **Sin secretos en Git:** la contraseña no está en el repositorio. `03-cuenta.sql` la recibe en la variable `@pw` y la sentencia se construye con `PREPARE`.
- **Comprobación:** con la cuenta web, un `UPDATE` devuelve `ERROR 1142 … UPDATE command denied to user 'puntdejoc'@'localhost'`.

## 4. Conexión PHP

`src/funciones.php` (proporcionado) crea el `PDO` con `DB_HOST`, `DB_PORT`, `DB_USER` y `DB_PASSWORD` leídas con `getenv()`. Estas variables tienen que existir **en el servicio PHP-FPM**, no en la terminal. FPM arranca los workers con `clear_env = yes`, así que solo ven las variables declaradas con `env[...]` en el pool.

`/etc/php/8.5/fpm/pool.d/zz-puntdejoc.conf` (permisos `600 root`, fuera del repo):

```ini
[www]
env[DB_HOST] = localhost
env[DB_PORT] = 3306
env[DB_USER] = puntdejoc
env[DB_PASSWORD] = "…"
env[ALLOW_LAB_WRITE] = 0
```

- Va en un archivo aparte para no tocar `www.conf`. FPM fusiona las dos secciones `[www]` (se ha comprobado con `php-fpm8.5 -tt`).
- `DB_HOST=localhost` hace que PDO use el socket `pdo_mysql.default_socket`, el mismo que MariaDB.
- `ALLOW_LAB_WRITE=0` mantiene `resultats.php` desactivado (403).

Despliegue: `public/` → `/var/www/html` y `src/` → `/var/www/src`. `index.php` hace `require __DIR__ . '/../src/funciones.php'`, así que `src/` queda **fuera** de la carpeta publicada y Apache no la sirve.

El portal lee las actividades (`estat <> 'borrador'`), los participantes y las partidas del torneo 2 con consultas preparadas, y calcula la clasificación con `classificacio()`. Si la base no responde, registra el error con `error_log()` y devuelve un 503 con un mensaje genérico, sin mostrar el error de PDO.

## 5. Pruebas

Se han ejecutado sobre MariaDB 11.8.6.

### Carga

| Tabla | Registros |
|---|---|
| jocs | 3 |
| activitats | 3 |
| inscripcions | 4 |
| rondes | 3 |
| partides | 6 |

### Consultas (`04-consultas.sql`)

- **Participantes del torneo 2:** Aina, Biel, Cris y Dani.
- **Partidas pendientes:** 3 (Aina–Cris), 4 (Biel–Dani), 5 (Aina–Dani) y 6 (Biel–Cris). Se usa `IS NULL`, porque `= NULL` nunca es cierto.
- **Actividades abiertas:** 1 Tarde de juegos y 3 Torneo de damas.

### Actualización (`05-actualizacion.sql`)

`UPDATE partides SET resultat = 'B' WHERE id = 3;` registra una victoria de Cris sobre Aina. Modifica 1 fila.

| | 1.º | 2.º | 3.º | 4.º |
|---|---|---|---|---|
| Antes | Aina 3 | Cris 1 / Dani 1 (empatados en 2.º) | — | Biel 0 |
| Después | Cris 4 | Aina 3 | Dani 1 | Biel 0 |

En el navegador, el pie de la clasificación pasa de «2 de 6 partidas jugadas» a «3 de 6».

### Restricciones (`06-pruebas-restricciones.sql`)

| Prueba | Resultado |
|---|---|
| Partida con inscripción 99 (no existe) | `ERROR 1452` FK `partides_ibfk_2` |
| Partida 1 contra 1 | `ERROR 4025 CONSTRAINT CONSTRAINT_1 failed` |
| Partida en la ronda 99 | `ERROR 1452` FK `partides_ibfk_1` |
| `resultat = 'X'` | `ERROR 1265 Data truncated` (ENUM, modo estricto) |
| Alias `Aina` repetido en la actividad 2 | `ERROR 1062 Duplicate entry '2-Aina'` |
| Actividad con 0 plazas | `ERROR 4025` CHECK de `activitats` |
| Borrar la inscripción 1 (tiene partidas) | `ERROR 1451` (sin cascada) |

## 6. Reglas que el esquema no garantiza

Las claves foráneas solo comprueban que el ID exista en la tabla padre. No comprueban que las filas relacionadas tengan sentido entre ellas. Estas inserciones **entran sin error** (la prueba las deshace con `ROLLBACK`):

| Regla | Por qué no se cumple | Cómo se podría garantizar |
|---|---|---|
| Los dos participantes están inscritos en la actividad de la ronda | `participant_a` → `inscripcions(id)` acepta cualquier inscripción, también de otra actividad (prueba: Eva, del torneo 3, en una ronda del torneo 2) | Añadir `activitat_id` a `partides`, `UNIQUE (id, activitat_id)` en `inscripcions` y `rondes`, y FK compuestas `(participant_a, activitat_id)`, `(participant_b, activitat_id)` y `(ronda_id, activitat_id)` |
| Cada participante juega una sola vez por ronda | Ningún UNIQUE cruza `participant_a` y `participant_b` (prueba: Aina en dos partidas de la ronda 1) | Tabla intermedia `participacions(ronda_id, inscripcio_id, partida_id)` con `UNIQUE (ronda_id, inscripcio_id)`, o un trigger `BEFORE INSERT/UPDATE` |
| No se repite el mismo enfrentamiento | (1,2) y (2,1) son filas distintas (prueba: Biel–Aina en la ronda 1) | Guardar siempre `participant_a < participant_b` con un CHECK, más `UNIQUE (ronda_id, participant_a, participant_b)` |
| Inscripciones ≤ plazas | `places` no se relaciona con el número de inscripciones | Validación en la aplicación o un trigger |
| Solo los torneos tienen rondas, y solo en curso se editan resultados | Nada impide rondas en una `trobada` | Validación en la aplicación (`resultats.php` ya filtra `estat = 'en_curs'`) o triggers |

En RA1 las partidas ya vienen preparadas y solo se editan resultados, así que estos huecos no afectan al portal. Sí afectarían si en el futuro se crearan emparejamientos desde la web.
