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
| `java/tornejos.war` | Aplicación Java de torneos (etapa 4) para Tomcat 10.1 |
| `java/tornejos.xml.example` | Plantilla de la configuración JNDI, sin credenciales |
| `READMEDATABASE.md` | Material de apoyo del encargo 3 (SQL de creación y carga) |

## Versiones

- Entrega estática: etiqueta `v1-estatica` (`git checkout v1-estatica`).
- Versión PHP sin base de datos: commit `91a5b56`.
- Versión PHP + MariaDB: rama `eric/aea3-bdd`.
- Aplicación Java de torneos: `java/tornejos.war`, sha256 `9777a16281051ab6a289939abd364edc2583544c3c26edb9571a4407e30db336` (MariaDB Connector/J 3.5.11 incluido), rama `eric/aea4-java`.

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

## Aplicación Java (Tomcat)

Requisitos: JDK 17 o 21 y Tomcat 10.1 (en Ubuntu: `openjdk-21-jdk-headless` y `tomcat10`). Apache, PHP-FPM y MariaDB se mantienen.

1. Tomcat solo en local: en `/etc/tomcat10/server.xml`, `<Connector port="8080" address="127.0.0.1" ...>`.
2. Cuenta Java (`SELECT` en `puntdejoc.*` y `UPDATE` solo de `resultat` en `partides`): editar la contraseña de `sql/07-cuenta-java.sql` y ejecutar `sudo mariadb < sql/07-cuenta-java.sql`.
3. Configuración JNDI fuera del WAR: copiar `java/tornejos.xml.example` en `/etc/tomcat10/Catalina/localhost/tornejos.xml`, poner la contraseña, y `sudo chown root:tomcat` + `sudo chmod 640`.
4. Despliegue: `sudo cp java/tornejos.war /var/lib/tomcat10/webapps/`. No va en la raíz de Apache.
5. Comprobar `http://127.0.0.1:8080/tornejos/`. Logs en `/var/log/tomcat10/`.

Las credenciales reales no se guardan en Git (`tornejos.xml` está en `.gitignore`).

## Evidencias

- Eric: [PHP + MariaDB](docs/evidencias/Eric/eric.md) · [Java + Tomcat](docs/evidencias/Eric/eric-java.md)
