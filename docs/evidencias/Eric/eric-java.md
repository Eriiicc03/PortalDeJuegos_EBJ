# Evidencias — Eric (AEA4 · Java + Tomcat)

## Versiones

| Componente | Versión |
|---|---|
| Java | OpenJDK 21.0.13 (`openjdk-21-jdk-headless`) |
| Tomcat | 10.1.55 (`tomcat10`) |
| MariaDB | 11.8.6 |
| Aplicación | `tornejos.war`, sha256 `9777a162…db336`, con MariaDB Connector/J 3.5.11 |

```
$ readlink -f /usr/bin/java            # Java que usa el sistema y Tomcat
/usr/lib/jvm/java-21-openjdk-amd64/bin/java
$ dpkg -l tomcat10 openjdk-21-jdk-headless | grep ^ii
openjdk-21-jdk-headless:amd64 21.0.13.0.3+1-1~26.04.1
tomcat10 10.1.55-1ubuntu2~26.04.1
$ mariadb --version
mariadb from 11.8.6-MariaDB, client 15.2 for debian-linux-gnu (x86_64)
```

## Diagrama de servicios

```
Navegador ──► Apache :80 ──► PHP-FPM (socket) ──┐
                                                ├──► MariaDB 127.0.0.1:3306 (puntdejoc)
Navegador ──► Tomcat 127.0.0.1:8080 ── JDBC ────┘
              /tornejos  (cuenta "tornejos")       PHP usa la cuenta "puntdejoc" (solo lectura)
```

## Configuración (anonimizada)

| Qué | Dónde |
|---|---|
| Servicio | `tomcat10` (systemd), `CATALINA_BASE=/var/lib/tomcat10` |
| Puerto | `/etc/tomcat10/server.xml`: `<Connector port="8080" address="127.0.0.1" …>` |
| Despliegue | `/var/lib/tomcat10/webapps/tornejos.war` → contexto `/tornejos` |
| JNDI | `/etc/tomcat10/Catalina/localhost/tornejos.xml` (root:tomcat, 640), fuera del WAR y de Git |
| Logs | `/var/log/tomcat10/` (`catalina.out`, `localhost_access_log.*.txt`) |

`tornejos.xml`, con la contraseña tapada (plantilla en `java/tornejos.xml.example`):
```xml
<Environment name="jdbcUrl" value="jdbc:mariadb://127.0.0.1:3306/puntdejoc"/>
<Environment name="jdbcUser" value="tornejos"/>
<Environment name="jdbcPassword" value="********"/>
```

Cuenta Java (`sql/07-cuenta-java.sql`): `SELECT` en `puntdejoc.*` y `UPDATE (resultat)` en `partides`. No uso root y no toco la cuenta de PHP.

## Procedimiento

1. `sudo apt install openjdk-21-jdk-headless tomcat10`
2. Añadir `address="127.0.0.1"` al conector 8080 en `server.xml`.
3. `sudo mariadb < sql/07-cuenta-java.sql` (con la contraseña cambiada).
4. Copiar `java/tornejos.xml.example` en `/etc/tomcat10/Catalina/localhost/tornejos.xml` y poner la contraseña.
5. `sudo cp java/tornejos.war /var/lib/tomcat10/webapps/`
6. Abrir http://127.0.0.1:8080/tornejos/

## Pruebas

| Prueba | Resultado |
|---|---|
| Aplicación activa | ✅ 200, Java 21.0.13, contexto `/tornejos` |
| Torneo 2 | ✅ 3 rondas, 6 partidas |
| Cambio desde Java visto en PHP | ✅ Cris 1 → 4 |
| Protección del formulario | ✅ sin token 403, partida inexistente 409 |
| 404 | ✅ ruta inexistente |
| WEB-INF y credenciales | ✅ no descargables (404) |
| Reinicio de Tomcat | pendiente |
| Permisos de la cuenta | pendiente |
| MariaDB parado | pendiente |

**Aplicación y torneo 2**
```
$ curl -s 'http://127.0.0.1:8080/tornejos/?torneo=2' | grep -c '<article'
6
$ curl -s 'http://127.0.0.1:8080/tornejos/?torneo=2' | grep -oE '<h3>[^<]*</h3>|Resultado: [^(]*|Java [^<]*'
<h3>Ronda 1 · Aina / Biel</h3>   Resultado: A
<h3>Ronda 1 · Cris / Dani</h3>   Resultado: E
<h3>Ronda 2 · Aina / Cris</h3>   Resultado: B
<h3>Ronda 2 · Biel / Dani</h3>   Resultado: Pendiente
<h3>Ronda 3 · Aina / Dani</h3>   Resultado: E
<h3>Ronda 3 · Biel / Cris</h3>   Resultado: Pendiente
Java 21.0.13.0.3 · Contexto /tornejos
```

**Estado inicial y cambio en PHP.** Las partidas 3 y 5 estaban modificadas desde el encargo 3, así que primero las devolví a pendiente desde la propia aplicación Java (POST del formulario):
```
Estado inicial (PHP):         Aina 4 Cris 4 Dani 2 Biel 0
POST partida=3 pendiente  ->  HTTP 303
POST partida=5 pendiente  ->  HTTP 303
Punto de partida (PHP):       Aina 3 Cris 1 Dani 1 Biel 0
POST partida=3 B          ->  HTTP 303
Después (PHP):                Cris 4 Aina 3 Dani 1 Biel 0
```
Java guarda el resultado en MariaDB y PHP lo lee en la siguiente petición: la clasificación cambia sin tocar PHP.

**Protección del formulario**
```
POST sin token csrf   -> HTTP 403
POST partida=999      -> HTTP 409
```

**404 y archivos internos**
```
$ curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:8080/tornejos/noexiste                 # 404
$ curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:8080/tornejos/WEB-INF/web.xml          # 404
$ curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:8080/tornejos/WEB-INF/lib/mariadb-java-client-3.5.11.jar  # 404
$ curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:8080/tornejos/META-INF/context.xml     # 404
$ curl -s -o /dev/null -w '%{http_code}' http://localhost/tornejos.war                           # 404
```
Las credenciales no están en el WAR: están en `/etc/tomcat10`, fuera de cualquier carpeta servida.

## Incidencia resuelta

**Tomcat accesible desde la red**
- **Síntoma:** Tomcat escuchaba en todas las interfaces.
  ```
  $ ss -ltn | grep 8080
  LISTEN 0      100                *:8080             *:*
  ```
- **Causa:** el conector 8080 de `server.xml` no tenía `address`.
- **Corrección:** añadir `address="127.0.0.1"` y reiniciar Tomcat. Nueva prueba pendiente.

## Recorrido Tomcat → JDBC → MariaDB

1. El navegador pide `http://127.0.0.1:8080/tornejos/`; Tomcat ejecuta `index.jsp` con Java 21.
2. La aplicación lee `jdbcUrl`, `jdbcUser` y `jdbcPassword` por JNDI (`java:comp/env`), definidos en `tornejos.xml`.
3. Con el driver MariaDB Connector/J abre una conexión JDBC a `127.0.0.1:3306/puntdejoc` con la cuenta `tornejos`.
4. Lee o actualiza `partides.resultat` y devuelve HTML.
5. El portal PHP lee las mismas tablas con su cuenta de solo lectura, así que muestra el cambio en la clasificación.

## Fuentes

- https://tomcat.apache.org/tomcat-10.1-doc/jndi-resources-howto.html
- https://tomcat.apache.org/tomcat-10.1-doc/config/http.html
- https://mariadb.com/docs/connectors/mariadb-connector-j/about-mariadb-connector-j
