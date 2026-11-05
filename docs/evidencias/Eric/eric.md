# Evidencias individuales — Eric (AEA2 · Portal de Juegos)

Fecha de las pruebas: 5 de noviembre de 2026.

## Entorno

| Elemento | Valor (salida real) |
|---|---|
| Sistema | Ubuntu 26.04.1 LTS (resolute) |
| Servidor web | Apache/2.4.66 (Ubuntu) |
| PHP | php8.5-fpm 8.5.4-0ubuntu1.5 y php8.5-cli 8.5.4-0ubuntu1.5 |
| Repositorio | `https://github.com/Eriiicc03/PortalDeJuegos_EBJ.git`, clonado en `/var/www/html/php/AEA2` |

```
$ lsb_release -a
Distributor ID:	Ubuntu
Description:	Ubuntu 26.04.1 LTS
Release:	26.04
Codename:	resolute
```
*Muestra la distribución y la versión del sistema.*

```
$ apache2 -v
Server version: Apache/2.4.66 (Ubuntu)
Server built:   2026-10-08T19:55:44
```
*Muestra la versión de Apache instalada.*

```
$ dpkg -l | grep php     (extracto: paquetes php8.5 principales)
ii  php-fpm      2:8.5+99ubuntu1        all    server-side, HTML-embedded scripting language (FPM-CGI binary) (default)
ii  php8.5-cli   8.5.4-0ubuntu1.5       amd64  command-line interpreter for the PHP scripting language
ii  php8.5-common 8.5.4-0ubuntu1.5      amd64  documentation, examples and common module for PHP
ii  php8.5-fpm   8.5.4-0ubuntu1.5       amd64  server-side, HTML-embedded scripting language (FPM-CGI binary)
```
*Lista los paquetes PHP instalados. No aparece ningún paquete `libapache2-mod-php`.*

## Requisitos

- Apache 2.4 con los módulos `proxy`, `proxy_fcgi` y `setenvif`.
- PHP 8.5 en modo FPM (`php8.5-fpm`) y la configuración `php8.5-fpm.conf` habilitada en Apache.
- Git para clonar el repositorio.
- `curl` para hacer las pruebas desde mi ordenador.

## Configuración (PHP-FPM y Apache)

### mod_php no está cargado

```
$ apache2ctl -M | grep php
AH00558: apache2: Could not reliably determine the server's fully qualified domain name, using 127.0.1.1. Set the 'ServerName' directive globally to suppress this message
```
*Lista los módulos cargados y filtra los de PHP. Aparte del aviso AH00558 (ver Incidencias), no sale nada: **mod_php no está cargado** y PHP no se ejecuta dentro de Apache.*

### Servicio PHP-FPM

```
$ systemctl status php8.5-fpm --no-pager
● php8.5-fpm.service - The PHP 8.5 FastCGI Process Manager
     Loaded: loaded (/usr/lib/systemd/system/php8.5-fpm.service; enabled; preset: enabled)
     Active: active (running) since Thu 2026-11-05 15:54:40 CET; 1h 24min ago
       Docs: man:php-fpm8.5(8)
   Main PID: 2005 (php-fpm8.5)
     Status: "Processes active: 0, idle: 2, Requests: 0, slow: 0, Traffic: 0.00req/sec"
      Tasks: 3 (limit: 15193)
     Memory: 21.9M (peak: 24.4M)
        CPU: 366ms
     CGroup: /system.slice/php8.5-fpm.service
             ├─2005 "php-fpm: master process (/etc/php/8.5/fpm/php-fpm.conf)"
             ├─2115 "php-fpm: pool www"
             └─2116 "php-fpm: pool www"
```
*Muestra el estado del servicio: está activo, arranca con el sistema y tiene un proceso maestro con dos workers del pool `www`.*

```
$ grep -E "^listen" /etc/php/8.5/fpm/pool.d/www.conf
listen = /run/php/php8.5-fpm.sock
listen.owner = www-data
listen.group = www-data
```
*Muestra dónde escucha el pool `www`: en un socket Unix y no en un puerto TCP.*

```
$ ls -l /run/php/
total 4
lrwxrwxrwx 1 root     root     30 Nov  5 15:54 php-fpm.sock -> /etc/alternatives/php-fpm.sock
-rw-r--r-- 1 root     root      4 Nov  5 15:54 php8.5-fpm.pid
srw-rw---- 1 www-data www-data  0 Nov  5 15:54 php8.5-fpm.sock
```
*Lista el socket. La `s` indica que es un socket. Sus permisos son `rw-rw----` para `www-data`, el usuario de Apache. Al ser un socket Unix, no está expuesto a la red: solo se usa desde la propia máquina.*

### Módulos de Apache y handler

```
$ apache2ctl -M | grep -E "proxy|fcgi|setenvif"
 proxy_module (shared)
 proxy_fcgi_module (shared)
 setenvif_module (shared)
```
*Comprueba los módulos que hacen falta para pasar las peticiones a FPM (aparte del aviso AH00558). No los activé a mano: ya estaban activos porque el paquete `php8.5-fpm` los habilita en su script de postinstalación.*

```
$ grep SetHandler /etc/apache2/conf-available/php8.5-fpm.conf
        SetHandler "proxy:unix:/run/php/php8.5-fpm.sock|fcgi://localhost"
#            SetHandler "proxy:unix:/run/php/php8.5-fpm.sock|fcgi://localhost"
```
*Muestra a dónde manda Apache los `.php`. La ruta del socket coincide exactamente con el `listen` del pool `www`. La segunda línea está comentada.*

```
$ ls -l /etc/apache2/conf-enabled/ | grep php
lrwxrwxrwx 1 root root 33 Oct 29 17:37 php8.5-fpm.conf -> ../conf-available/php8.5-fpm.conf
```
*Confirma que la configuración de FPM está habilitada (es un enlace en `conf-enabled`).*

```
$ systemctl is-active apache2 php8.5-fpm
active
active
```
*Comprueba que los dos servicios están en marcha.*

```
$ grep DirectoryIndex /etc/apache2/mods-enabled/dir.conf
DirectoryIndex index.html index.cgi index.pl index.php index.xhtml index.htm
```
*Muestra el orden en que Apache busca el archivo índice: `index.html` va antes que `index.php`. En `/var/www/html/php/AEA2/` no hay ningún `index.html`, así que no hay nada que tape el `index.php`.*

## Procedimiento reproducible

1. Clonar el repositorio dentro del DocumentRoot:
   `git clone https://github.com/Eriiicc03/PortalDeJuegos_EBJ.git /var/www/html/php/AEA2`
2. Comprobar que `php8.5-fpm` está activo y que `php8.5-fpm.conf` está habilitado (ver Configuración).
3. Abrir `http://localhost/php/AEA2/`.
4. Para volver a la versión estática: `git checkout v1-estatica` dentro del repositorio. Para volver a la versión PHP: `git checkout master`.

Estado del repositorio:

```
$ git log --oneline --decorate
3065445 (HEAD -> master, origin/master, origin/HEAD) cambio a php
1e5b01a (tag: v1-estatica) Estilo visual temático de juegos de mesa para Punt de Joc
8492cb3 proyecto base
```
*Muestra el historial. La entrega estática no tenía etiqueta, así que le puse la etiqueta local `v1-estatica`, que apunta al commit `1e5b01a`.*

```
$ git show --stat --format='%h %s' v1-estatica | tail -8
1e5b01a Estilo visual temático de juegos de mesa para Punt de Joc

 activitats.html |  36 ++++-
 index.html      |  35 ++++-
 script.js       |  77 ++++++++++
 style.css       | 459 ++++++++++++++++++++++++++++++++++++++++++++++++++++++--
 torneig.html    |  48 +++++-
 5 files changed, 628 insertions(+), 27 deletions(-)
```
*Muestra los archivos de la versión estática: varias páginas HTML, `style.css` y `script.js`.*

```
$ ls -la /var/www/html/php/AEA2
total 44
drwxrwxr-x 4 eriiicc03 eriiicc03  4096 Oct 29 17:34 .
drwxr-xr-x 3 eriiicc03 eriiicc03  4096 Oct 29 17:06 ..
drwxrwxr-x 7 eriiicc03 eriiicc03  4096 Nov  5 17:19 .git
drwxrwxr-x 3 eriiicc03 eriiicc03  4096 Oct 29 17:34 docs
-rw-rw-r-- 1 eriiicc03 eriiicc03 25968 Oct 29 17:35 index.php
```
*Contenido desplegado. La versión PHP es un único `index.php`: el PHP está al principio (datos y funciones), luego va el HTML y el CSS está dentro de un bloque `<style>`. No hay ningún archivo `.css` aparte.*

## URL local

- Versión PHP: **http://localhost/php/AEA2/**
- `http://localhost/` muestra la página por defecto de Apache (`/var/www/html/index.html`) y no el portal.

## Pruebas

### Tabla resumen

| # | Prueba | Esperado | Resultado |
|---|---|---|---|
| 1 | Acceso HTTP a `/php/AEA2/` e `index.php` | 200, `text/html` | ✅ 200, `text/html; charset=UTF-8` |
| 2 | CSS | 200 | ✅ El CSS va inline (1 bloque `<style>`) y llega con el HTML |
| 3 | Contenido generado | 1 bloque por actividad | ✅ 2 `<article>` con 2 elementos → 3 `<article>` al añadir el tercero |
| 4 | Hora | Cambia entre peticiones | ✅ 17:19:51 → 17:19:56 y 17:28:06 → 17:28:11 |
| 5 | Código fuente no visible | 0 `<?php`, 0 `foreach` | ✅ 0 y 0 |
| 6 | Entorno de ejecución | `fpm-fcgi` por web, `cli` por consola | ✅ |
| 7 | URL del directorio | Sirve `index.php` | ✅ 200; sin `/` final da 301 |

### 1. Acceso HTTP

```
$ curl -sI http://localhost/php/AEA2/
HTTP/1.1 200 OK
Date: Thu, 05 Nov 2026 16:19:51 GMT
Server: Apache/2.4.66 (Ubuntu)
Content-Type: text/html; charset=UTF-8
```
```
$ curl -sI http://localhost/php/AEA2/index.php
HTTP/1.1 200 OK
Date: Thu, 05 Nov 2026 16:19:51 GMT
Server: Apache/2.4.66 (Ubuntu)
Content-Type: text/html; charset=UTF-8
```
*`-I` pide solo las cabeceras. Las dos URL responden 200 con HTML. No llevan `Content-Length` ni `Last-Modified` porque la respuesta se genera en el momento y no es un archivo fijo.*

### 2. CSS

```
$ curl -s http://localhost/php/AEA2/ | grep -c '<style'
1
```
*Cuenta los bloques `<style>` en la respuesta. La versión PHP lleva el CSS dentro del propio HTML, así que no hay una URL `.css` propia a la que pedir un 200: el CSS llega con el HTML, que ya da 200. Las tipografías se cargan desde Google Fonts.*

### 3. Contenido generado

```php
$activitats = [
  ['titol' => 'Tarde de juegos', 'tipus' => 'Encuentro', 'data' => '2026-11-12', 'lloc' => 'Local de la asociación', 'places' => 16, 'torneig' => false],
  ['titol' => 'Liga de demostración', 'tipus' => 'Torneo', 'data' => '2026-11-19', 'lloc' => 'Local de la asociación', 'places' => 4, 'torneig' => true],
];
```
*Esta es la lista de actividades (líneas 5–8 de `index.php`). El bucle `<?php foreach ($activitats as $activitat): ?>` (línea 880) crea un `<article>` por cada elemento. Si añado un elemento al array, aparece una tarjeta más sin tocar el HTML: el bucle pinta el título, el tipo, la fecha larga, la cuenta atrás, el lugar y las plazas. Si `torneig` es `true`, la tarjeta lleva además la clase `torneo`.*

```
$ curl -s http://localhost/php/AEA2/ | grep -c '<article'
2
```
```
$ curl -s http://localhost/php/AEA2/ | grep -o '<h2>[^<]*</h2>'
<h2>Nuestra comunidad</h2>
<h2>Tarde de juegos</h2>
<h2>Liga de demostración</h2>
<h2>Participantes</h2>
<h2>Ronda 1</h2>
<h2>Clasificación</h2>
```
*Cuenta las tarjetas y saca los títulos del HTML recibido. Con la lista original hay 2 `<article>` para 2 elementos del array, y los títulos coinciden.*

**Añadir una actividad.** El encargo pide al menos tres. Añadí un solo elemento al array, sin tocar el HTML de la plantilla:

```diff
   ['titol' => 'Liga de demostración', 'tipus' => 'Torneo', 'data' => '2026-11-19', 'lloc' => 'Local de la asociación', 'places' => 4, 'torneig' => true],
+  ['titol' => 'Iniciación a juegos de rol', 'tipus' => 'Taller', 'data' => '2026-11-26', 'lloc' => 'Local de la asociación', 'places' => 8, 'torneig' => false],
 ];
```

```
$ php -l index.php
No syntax errors detected in index.php
```
*Comprueba la sintaxis del archivo antes de pedirlo por web.*

```
$ curl -s http://localhost/php/AEA2/ | grep -c '<article'
3
$ curl -s http://localhost/php/AEA2/ | grep -o '<h2>[^<]*</h2>'
<h2>Nuestra comunidad</h2>
<h2>Tarde de juegos</h2>
<h2>Liga de demostración</h2>
<h2>Iniciación a juegos de rol</h2>
<h2>Participantes</h2>
<h2>Ronda 1</h2>
<h2>Clasificación</h2>
```
*Ahora hay 3 `<article>`: el mismo `foreach` ha generado un bloque más a partir del nuevo elemento.*

### 4. Hora generada en cada petición

```
$ curl -s -H 'Cache-Control: no-cache' http://localhost/php/AEA2/ | grep -o 'Respuesta generada en el servidor: [0-9:]*'
Respuesta generada en el servidor: 17:19:51
```
*(espera de 5 segundos)*
```
$ curl -s -H 'Cache-Control: no-cache' http://localhost/php/AEA2/ | grep -o 'Respuesta generada en el servidor: [0-9:]*'
Respuesta generada en el servidor: 17:19:56
```
*Pido la página dos veces sin caché y saco solo la línea de la hora. La hora cambia: `date('H:i:s')` se ejecuta en el servidor con cada petición.*

Repetida con la tercera actividad ya añadida:

```
$ curl -s -H 'Cache-Control: no-cache' http://localhost/php/AEA2/ | grep -o 'Respuesta generada en el servidor: [0-9:]*'
Respuesta generada en el servidor: 17:28:06
$ curl -s -H 'Cache-Control: no-cache' http://localhost/php/AEA2/ | grep -o 'Respuesta generada en el servidor: [0-9:]*'
Respuesta generada en el servidor: 17:28:11
$ curl -s http://localhost/php/AEA2/ | grep -c '<?php'
0
$ curl -s http://localhost/php/AEA2/ | grep -c 'foreach'
0
```

### 5. El código fuente no llega al navegador

```
$ curl -s http://localhost/php/AEA2/ | grep -c '<?php'
0
$ curl -s http://localhost/php/AEA2/ | grep -c 'foreach'
0
```
*Cuenta las etiquetas PHP y los bucles en la respuesta. Las dos dan 0: el navegador solo recibe el HTML ya generado.*

### 6. Entorno de ejecución (SAPI)

```
$ echo '<?php echo php_sapi_name(); ?>' > /var/www/html/sapi.php && curl -s http://localhost/sapi.php; echo; rm -v /var/www/html/sapi.php
fpm-fcgi
'/var/www/html/sapi.php' borrado
```
*Creo un script temporal que imprime la SAPI, lo pido por web y lo borro en el mismo comando. Da `fpm-fcgi`: el PHP web lo ejecuta PHP-FPM a través de FastCGI.*

```
$ php -r 'echo php_sapi_name();'
cli
$ ls /var/www/html/sapi.php
ls: no se puede acceder a ''/var/www/html/sapi.php'': No existe el archivo o directorio
```
*El mismo código desde la terminal da `cli`, y el `ls` confirma que el archivo temporal ya no está. Por eso `php -v` no demuestra la ejecución web: usa el binario CLI, que es otro proceso con su propio `php.ini`. Solo prueba que PHP está instalado, no que Apache le pase las peticiones a FPM.*

### 7. URL del directorio

```
$ curl -sI http://localhost/php/AEA2 | head -3
HTTP/1.1 301 Moved Permanently
Date: Thu, 05 Nov 2026 16:24:10 GMT
Server: Apache/2.4.66 (Ubuntu)
```
*Sin la barra final, Apache redirige (301) a `/php/AEA2/`. Con la barra (prueba 1) responde 200 con la versión PHP, porque `DirectoryIndex` encuentra `index.php` y no hay ningún `index.html` delante.*

## Decisiones

- **PHP-FPM en lugar de mod_php:** PHP corre en su propio servicio y Apache solo le pasa las peticiones `.php`.
- **Socket Unix y no TCP:** no abre ningún puerto de red y los permisos limitan el acceso a `www-data`.
- **Etiqueta `v1-estatica`:** permite recuperar la entrega estática sin mantener una carpeta duplicada.
- **Tercera actividad:** «Iniciación a juegos de rol» (taller, 26/11/2026, 8 plazas). Encaja con la propuesta de Punto de Juego, que es una asociación de juegos de mesa y de rol.
- **Rama e integración:** mis cambios van en la rama `eric/aea2-php`. Criterio: cada integrante trabaja en su rama, solo toca sus datos o su carpeta de evidencias y se integra en `master` mediante pull request revisado por otro miembro. Así las tres copias ejecutan la misma versión al hacer `git pull` de `master`.
- **Documentación protegida:** el repositorio está clonado dentro del DocumentRoot, así que `docs/`, `.git/` y `README.md` quedan dentro de la carpeta servida. Para que no se publiquen, preparé una regla de Apache (`aea2-privado.conf`) que deniega esas rutas. Su prueba está en Incidencias.

## Incidencias

### AH00558 (ServerName)

- **Síntoma:** cada `apache2ctl` muestra
  `AH00558: apache2: Could not reliably determine the server's fully qualified domain name, using 127.0.1.1. Set the 'ServerName' directive globally to suppress this message`
- **Causa:** no hay ninguna directiva `ServerName` global.
- **Corrección y nueva prueba:** pendientes de ejecutar.

### Carpetas internas servidas por la web

Mientras hacía las pruebas vi que el repositorio, al estar dentro del DocumentRoot, deja ver archivos que no deberían ser públicos:

```
$ curl -sI http://localhost/php/AEA2/.git/config | head -1
HTTP/1.1 200 OK
$ curl -sI http://localhost/php/AEA2/docs/evidencias/Eric/eric.md | head -1
HTTP/1.1 200 OK
```
*Cualquiera podría descargar la configuración de git y la documentación. La corrección está pendiente.*

### PHP-FPM parado

Pendiente de ejecutar.

## Recorrido de la petición

```
Navegador (curl)
   │  GET /php/AEA2/  (HTTP, puerto 80)
   ▼
Apache 2.4  ── DirectoryIndex → index.php
   │  <FilesMatch \.php$>  SetHandler "proxy:unix:/run/php/php8.5-fpm.sock|fcgi://localhost"
   ▼
mod_proxy + mod_proxy_fcgi  (convierte la petición a FastCGI)
   │
   ▼
Socket Unix /run/php/php8.5-fpm.sock  (www-data, rw-rw----)
   │
   ▼
PHP-FPM, pool www  (SAPI fpm-fcgi) → ejecuta index.php
   │  HTML generado
   ▼
Apache → Navegador  (200, text/html; charset=UTF-8)
```

## Código fuente vs respuesta HTML vs datos persistentes

| | Dónde está | Qué contiene |
|---|---|---|
| **Código fuente** | `/var/www/html/php/AEA2/index.php` (servidor) | PHP: arrays, funciones y bucles `foreach` |
| **Respuesta HTML** | Lo que recibe el navegador | Solo HTML y CSS ya generados (0 `<?php`, 0 `foreach`) |
| **Datos persistentes** | No hay | — |

Aquí no hay datos persistentes: no hay base de datos ni ficheros de datos. El array `$activitats` se vuelve a crear en cada petición, se usa para generar el HTML y desaparece cuando acaba el script. Para cambiar las actividades hay que editar el código.

## Fuentes

- Apache HTTP Server, *mod_proxy_fcgi*: https://httpd.apache.org/docs/2.4/mod/mod_proxy_fcgi.html
- Manual de PHP, *FastCGI Process Manager (FPM)*: https://www.php.net/manual/es/install.fpm.php
- Ubuntu Server, *documentación de PHP / servidores web*: https://documentation.ubuntu.com/server/
