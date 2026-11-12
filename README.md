# Punto de Juego · Portal de juegos

Portal de la asociación Punto de Juego. Desde la etapa 3 (AEA3) los datos (actividades, inscripciones, rondas y partidas) se guardan en MariaDB y el portal los lee con PDO en cada petición.

## Estructura

| Ruta | Contenido |
|---|---|
| `public/index.php` | Portal: consultas a la base, plantilla HTML y CSS en un bloque `<style>` |
| `public/resultats.php` | Registro de resultados de laboratorio, desactivado (`ALLOW_LAB_WRITE=0` → 403) |
| `src/funciones.php` | Conexión PDO, escapado `h()` y cálculo de la clasificación (fuera de la carpeta pública) |
| `sql/` | Creación, carga, cuenta web, consultas, actualización y pruebas de restricciones, numerados por orden de ejecución |
| `scripts/instalar.sh` | Crea y carga la base, crea la cuenta, configura FPM y despliega |
| `docs/BASE-DE-DATOS.md` | Análisis del modelo, orden de dependencias, permisos, pruebas y límites del esquema |
| `docs/evidencias/` | Evidencias individuales de cada integrante (no se sirven por web) |
| `READMEDATABASE.md` | Material de apoyo del encargo 3 (SQL de creación y carga) |

## Versiones

- Entrega estática: etiqueta `v1-estatica` (`git checkout v1-estatica`).
- Versión PHP sin base de datos: commit `91a5b56`.
- Versión PHP + MariaDB: rama `eric/aea3-bdd`.

## Requisitos

Apache 2.4 con PHP-FPM 8.2+ (`proxy_fcgi`), extensión `pdo_mysql` y MariaDB local.

## Instalación y despliegue

```bash
bash scripts/instalar.sh
```

Pide la contraseña de `sudo` y la de la cuenta web `puntdejoc`, que no se guarda en Git. Pasos que ejecuta (detalle en [docs/BASE-DE-DATOS.md](docs/BASE-DE-DATOS.md)):

1. `sql/01-creacion.sql` y `sql/02-carga.sql` con la cuenta administradora.
2. `sql/03-cuenta.sql`: `puntdejoc@localhost` con solo `SELECT`.
3. `/etc/php/8.5/fpm/pool.d/zz-puntdejoc.conf` con `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD` y `ALLOW_LAB_WRITE=0`, y recarga de FPM.
4. `public/` → `/var/www/html` y `src/` → `/var/www/src`.

URL local: `http://localhost/`.

Pruebas del encargo:

```bash
mariadb -u puntdejoc -p puntdejoc < sql/04-consultas.sql            # consultas
sudo mariadb < sql/05-actualizacion.sql                             # UPDATE partida 3
sudo mariadb --force -v puntdejoc < sql/06-pruebas-restricciones.sql # errores esperados
```

## Evidencias

- [Eric](docs/evidencias/Eric/eric.md)
