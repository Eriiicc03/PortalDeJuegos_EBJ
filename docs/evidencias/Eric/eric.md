# Evidencias de Eric · AEA2 (PHP con Apache + PHP-FPM)

## Entorno y requisitos

| Elemento              | Valor                                  |
| --------------------- | -------------------------------------- |
| Sistema               | Ubuntu 26.04.1 LTS                     |
| Apache                | 2.4.66                                 |
| PHP                   | 8.5.4 con `php8.5-fpm`                 |
| mod_php               | No instalado                           |
| Carpeta de despliegue | `/var/www/html`                        |
| Repositorio           | `~/AEA2` (fuera de la carpeta pública) |

```bash
lsb_release -a                # versión de Ubuntu
apache2 -v                    # versión de Apache
dpkg -l | grep php            # paquetes de PHP instalados
apache2ctl -M | grep php      # comprobar si hay mod_php
```

```
Description:	Ubuntu 26.04.1 LTS
Server version: Apache/2.4.66 (Ubuntu)
ii  php8.5-cli    8.5.4-0ubuntu1.5   amd64   command-line interpreter for the PHP scripting language
ii  php8.5-fpm    8.5.4-0ubuntu1.5   amd64   server-side, HTML-embedded scripting language (FPM-CGI binary)
```

`apache2ctl -M | grep php` no devuelve nada, así que no hay `mod_php`. Requisitos: Apache, `php-fpm`, los módulos `proxy_fcgi` y `setenvif`, y Git.

## 1. Preparar la versión

Estructura del repositorio:

```
README.md
public/index.php
docs/evidencias/Eric/eric.md
```

- **PHP:** las primeras 80 líneas de `public/index.php` (datos y funciones) y las etiquetas `<?php ... ?>` y `<?= ... ?>` de la plantilla.
- **HTML:** la plantilla de `public/index.php`.
- **CSS:** dentro de `public/index.php`, en un bloque `<style>`. No hay un archivo `.css` aparte.

La entrega estática ya estaba marcada con una etiqueta:

```bash
git tag      # etiquetas del repositorio
```

```
v1-estatica
```

Con `git checkout v1-estatica` vuelvo a la versión con `index.html` y `style.css`.

Las actividades están en el array `$activitats` (línea 5) y se pintan con un `foreach` (líneas 881-895), que genera un `<article>` por elemento. Hay 3 actividades. Si añado un elemento al array, aparece otro bloque sin copiar HTML.

He movido `index.php` a `public/` para separar lo que se publica de la documentación:

```bash
mkdir public
git mv index.php public/index.php   # lo que se publica va en public/
```

## 2. PHP-FPM y Apache

```bash
systemctl status php8.5-fpm --no-pager            # estado del servicio
grep -E "^listen" /etc/php/8.5/fpm/pool.d/www.conf # dónde escucha FPM
apache2ctl -M | grep -E "proxy|fcgi"               # módulos de proxy
grep SetHandler /etc/apache2/conf-available/php8.5-fpm.conf  # a dónde manda Apache los .php
systemctl is-active apache2 php8.5-fpm             # los dos servicios
```

```
Active: active (running) since Mon 2026-11-09 15:58:53 CET
listen = /run/php/php8.5-fpm.sock
 proxy_module (shared)
 proxy_fcgi_module (shared)
        SetHandler "proxy:unix:/run/php/php8.5-fpm.sock|fcgi://localhost"
active
active
```

FPM escucha en el socket `/run/php/php8.5-fpm.sock`, y Apache manda los `.php` a ese mismo socket. No he tenido que activar nada porque los módulos y `php8.5-fpm.conf` ya estaban activos.

```bash
sudo apache2ctl configtest   # sintaxis de Apache
sudo php-fpm8.5 -t           # sintaxis de FPM
```

```
Syntax OK
NOTICE: configuration file /etc/php/8.5/fpm/php-fpm.conf test is successful
```

### Incidencia AH00558

**Síntoma:** al ejecutar `apache2ctl` sale un aviso.

```
AH00558: apache2: Could not reliably determine the server's fully qualified domain name, using 127.0.1.1. Set the 'ServerName' directive globally to suppress this message
```

**Causa:** no hay `ServerName` global. No impide que funcione, pero ensucia las pruebas.

**Solución:**

```bash
echo "ServerName localhost" | sudo tee /etc/apache2/conf-available/servername.conf  # creo la conf
sudo a2enconf servername         # la activo
sudo apache2ctl configtest       # compruebo la sintaxis
sudo systemctl reload apache2    # aplico sin cortar el servicio
```

```
ServerName localhost
Conf servername already enabled
Syntax OK
```

Sale `already enabled` porque ya la había activado en un intento anterior.

**Nueva prueba:**

```bash
sudo apache2ctl configtest              # vuelvo a comprobar
apache2ctl -M 2>&1 | grep -c AH00558    # cuento los avisos
```

```
Syntax OK
0
```

El aviso ya no aparece.

## 3. Despliegue

Al principio el repositorio estaba en `/var/www/html/php/AEA2`, y desde la web se podían descargar el Markdown y el `.git`:

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost/php/AEA2/.git/HEAD  # .git expuesto
```

```
200
```

Por eso he sacado el repositorio de la carpeta pública:

```bash
mv /var/www/html/php/AEA2 ~/AEA2   # el repo fuera de /var/www/html
rmdir /var/www/html/php            # borro la carpeta vacía
```

Antes de copiar, comparo lo que hay publicado con `public/`:

```bash
ls -la /var/www/html            # qué hay publicado
diff -rq public/ /var/www/html  # diferencias con public/
```

```
-rw-rw-r-- 1 eriiicc03 eriiicc03 10672 Oct 29 17:41 index.html
Sólo en /var/www/html: index.html
Sólo en public/: index.php
```

El `index.html` era la página por defecto de Apache. Como `DirectoryIndex` busca `index.html` antes que `index.php`, lo taparía, así que lo renombro y copio el portal:

```bash
mv /var/www/html/index.html /var/www/html/index.html.bak  # que no tape el index.php
cp public/index.php /var/www/html/                         # publico el portal
ls -la /var/www/html                                       # compruebo
find /var/www/html -name "*.md" | wc -l                    # ningún Markdown publicado
```

```
-rw-rw-r-- 1 eriiicc03 eriiicc03 10672 Oct 29 17:41 index.html.bak
-rw-rw-r-- 1 eriiicc03 eriiicc03 26131 Nov  9 16:38 index.php
0
```

## 4. Pruebas

| Prueba                        | Resultado esperado            | Resultado |
| ----------------------------- | ----------------------------- | --------- |
| 4.1 Acceso HTTP               | 200 y `text/html`             | OK        |
| 4.2 Contenido generado        | 3 bloques = 3 elementos       | OK        |
| 4.3 Comparación de respuestas | Hora distinta                 | OK        |
| 4.4 Código fuente recibido    | 0 y 0                         | OK        |
| 4.5 Entorno de ejecución      | `fpm-fcgi` en la web          | OK        |
| 4.6 URL del directorio        | Sirve la versión PHP          | OK        |
| 4.7 Incidencia y recuperación | 503 sin FPM, 200 al arrancar  | OK        |

### 4.1 Acceso HTTP

```bash
curl -sI http://localhost/index.php   # cabeceras de la respuesta
```

```
HTTP/1.1 200 OK
Server: Apache/2.4.66 (Ubuntu)
Content-Type: text/html; charset=UTF-8
```

Apache responde 200 con HTML. El CSS va dentro de esta misma respuesta (bloque `<style>`), así que el 200 también vale para el CSS.

### 4.2 Contenido generado

```bash
grep -c "'titol' =>" /var/www/html/index.php        # elementos del array
curl -s http://localhost/index.php | grep -c "<article"  # bloques en el HTML
```

```
3
3
```

Hay tantos bloques como elementos en el array, porque el `foreach` genera uno por cada actividad.

### 4.3 Comparación de respuestas

```bash
curl -s -H "Cache-Control: no-cache" http://localhost/ | grep -o "Respuesta generada en el servidor: [0-9:]*"
sleep 5   # espero 5 segundos
curl -s -H "Cache-Control: no-cache" http://localhost/ | grep -o "Respuesta generada en el servidor: [0-9:]*"
```

```
Respuesta generada en el servidor: 16:38:49
Respuesta generada en el servidor: 16:38:54
```

La hora cambia porque PHP genera la página de nuevo en cada petición.

### 4.4 Código fuente recibido

```bash
curl -s http://localhost/ | grep -c "<?php"    # etiquetas PHP en la respuesta
curl -s http://localhost/ | grep -c "foreach"  # bucles en la respuesta
```

```
0
0
```

El navegador solo recibe HTML. El código PHP se queda en el servidor.

### 4.5 Entorno de ejecución

```bash
echo '<?php echo php_sapi_name();' > /var/www/html/sapi.php  # archivo temporal
curl -s http://localhost/sapi.php      # lo que ejecuta la web
php -r 'echo php_sapi_name();'         # lo que ejecuta la terminal
php -v                                 # versión CLI
php-fpm8.5 -v                          # versión FPM
rm /var/www/html/sapi.php              # borro el archivo temporal
```

```
fpm-fcgi
cli
PHP 8.5.4 (cli) (built: Oct 19 2026 14:35:18) (NTS)
PHP 8.5.4 (fpm-fcgi) (built: Oct 19 2026 14:35:18) (NTS)
```

La web la ejecuta PHP-FPM (`fpm-fcgi`) y la terminal usa `cli`, por eso `php -v` no demuestra cómo se ejecuta la web. Después he borrado `sapi.php`.

### 4.6 URL del directorio

```bash
curl -sI http://localhost/ | grep -E "HTTP|Content-Type"  # sin escribir index.php
curl -s http://localhost/ | grep -o "<title>[^<]*"       # qué página sirve
```

```
HTTP/1.1 200 OK
Content-Type: text/html; charset=UTF-8
<title>Punt de Joc
```

Al pedir el directorio, Apache busca el documento inicial y, como ya no hay `index.html`, sirve `index.php`.

### 4.7 Incidencia y recuperación

```bash
sudo systemctl stop php8.5-fpm               # paro FPM
curl -sI http://localhost/index.php | head -n 1   # compruebo
tail -n 3 /var/log/apache2/error.log         # miro el error
sudo systemctl start php8.5-fpm              # lo arranco
curl -sI http://localhost/index.php | head -n 1   # nueva prueba
```

```
HTTP/1.1 503 Service Unavailable
[proxy:error] (2)No such file or directory: AH02454: FCGI: attempt to connect to Unix domain socket /run/php/php8.5-fpm.sock (localhost:8000) failed
[proxy_fcgi:error] [client ::1:41496] AH01079: failed to make connection to backend: localhost
HTTP/1.1 200 OK
```

Sin FPM no existe el socket, Apache no tiene a quién pasar los `.php` y devuelve 503. Al arrancarlo, vuelve a dar 200.

## 5. Explicación

### Recorrido de la petición

```
Navegador → Apache (puerto 80) → index.php → proxy_fcgi → /run/php/php8.5-fpm.sock → PHP-FPM → HTML → Apache → Navegador
```

Apache recibe la petición y, como es un `.php`, se la pasa a PHP-FPM por el socket. FPM ejecuta el código y devuelve HTML, que Apache envía al navegador.

### Código fuente, respuesta HTML y datos persistentes

- **Código fuente:** `index.php`, que está en el servidor. El navegador no lo ve.
- **Respuesta HTML:** el resultado de ejecutarlo, que es lo que recibe el navegador.
- **Datos persistentes:** aquí no hay. El array está en el código y se vuelve a crear en cada petición, no hay base de datos.

## Decisiones

- Uso PHP-FPM con Apache (`proxy_fcgi`) y no `mod_php`.
- FPM escucha en un socket Unix, así que no queda expuesto a la red.
- Saco el repositorio de `/var/www/html` porque se veían el `.git` y los Markdown. Solo publico `public/`.
- Renombro el `index.html` por defecto a `.bak` en lugar de borrarlo.
- Mantengo la entrega estática con la etiqueta `v1-estatica`, que ya existía.

## Fuentes

- Ubuntu Server, Apache: https://documentation.ubuntu.com/server/
- Apache, mod_proxy_fcgi: https://httpd.apache.org/docs/2.4/mod/mod_proxy_fcgi.html
- PHP, FPM: https://www.php.net/manual/es/install.fpm.php