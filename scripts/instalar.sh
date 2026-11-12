#!/bin/bash
# Punto de Juego · Instalación local de la etapa 3 (MariaDB + PHP-FPM + Apache)
# Uso, desde la raíz del repositorio:  bash scripts/instalar.sh
# Pide la contraseña de sudo y la de la cuenta web; ninguna se guarda en Git.
set -euo pipefail
cd "$(dirname "$0")/.."

FPM_CONF=/etc/php/8.5/fpm/pool.d/zz-puntdejoc.conf
WEB=/var/www/html
SRC=/var/www/src

paso() { printf '\n==> %s\n' "$1"; }

paso "1. Base de datos y tablas (sql/01-creacion.sql)"
if sudo mariadb -N -e "SHOW DATABASES LIKE 'puntdejoc'" | grep -q puntdejoc; then
  echo "La base puntdejoc ya existe: no se vuelve a crear ni a cargar."
else
  sudo mariadb < sql/01-creacion.sql
  paso "2. Datos iniciales (sql/02-carga.sql)"
  sudo mariadb < sql/02-carga.sql > /dev/null
  sudo mariadb -t puntdejoc -e "SELECT (SELECT COUNT(*) FROM jocs) jocs, (SELECT COUNT(*) FROM activitats) activitats,
    (SELECT COUNT(*) FROM inscripcions) inscripcions, (SELECT COUNT(*) FROM rondes) rondes, (SELECT COUNT(*) FROM partides) partides"
fi

paso "3. Cuenta web de solo lectura (sql/03-cuenta.sql)"
if sudo mariadb -N -e "SELECT 1 FROM mysql.user WHERE user='puntdejoc' AND host='localhost'" | grep -q 1; then
  echo "La cuenta puntdejoc@localhost ya existe: se mantiene su contraseña."
  echo "Si no la recuerdas: sudo mariadb -e \"DROP USER 'puntdejoc'@'localhost'\" y vuelve a ejecutar."
  NUEVA=0
else
  read -rsp "Contraseña nueva para puntdejoc: " PW; echo
  [ -n "$PW" ] || { echo "La contraseña no puede estar vacía."; exit 1; }
  # @pw se pasa por stdin, no por la línea de órdenes (no queda en ps ni en el historial)
  ESC=${PW//\\/\\\\}
  ESC=${ESC//\'/\'\'}
  printf "SET @pw = '%s';\nSOURCE sql/03-cuenta.sql;\n" "$ESC" | sudo mariadb
  unset ESC
  NUEVA=1
fi

paso "4. Variables de entorno para PHP-FPM ($FPM_CONF)"
if [ "$NUEVA" = 1 ] || ! sudo test -f "$FPM_CONF"; then
  if [ "$NUEVA" = 0 ]; then read -rsp "Contraseña actual de puntdejoc: " PW; echo; fi
  # Entre comillas para que ; # = de la contraseña no rompan el .ini
  [[ "$PW" != *'"'* ]] || { echo "Usa una contraseña sin comillas dobles."; exit 1; }
  sudo install -m 600 -o root -g root /dev/null "$FPM_CONF"
  sudo tee "$FPM_CONF" >/dev/null <<EOF
; Punto de Juego: conexión del portal a MariaDB (no está en Git)
[www]
env[DB_HOST] = localhost
env[DB_PORT] = 3306
env[DB_USER] = puntdejoc
env[DB_PASSWORD] = "$PW"
env[ALLOW_LAB_WRITE] = 0
EOF
fi
unset PW
sudo php-fpm8.5 -t
sudo systemctl reload php8.5-fpm

paso "5. Despliegue: public/ -> $WEB y src/ -> $SRC"
sudo install -d -m 755 "$SRC"
sudo install -m 644 src/funciones.php "$SRC/"
sudo install -m 644 public/index.php public/resultats.php "$WEB/"

paso "6. Comprobación"
curl -s -o /dev/null -w "index.php: %{http_code}\n" http://localhost/index.php
curl -s -o /dev/null -w "resultats.php: %{http_code} (403 = escritura desactivada)\n" http://localhost/resultats.php
curl -s http://localhost/ | grep -o '<caption>[0-9]* de [0-9]*[^<]*' || true
