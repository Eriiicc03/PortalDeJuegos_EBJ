-- Punto de Juego · Cuenta de la aplicación web (solo lectura)
-- La contraseña NO se guarda en Git: se pasa en la variable @pw al ejecutar.
--
--   read -rsp "Contraseña para puntdejoc: " PW; echo
--   sudo mariadb -e "SET @pw='$PW'; SOURCE sql/03-cuenta.sql;"
--
-- 'localhost' cubre la conexión por socket Unix (DB_HOST=localhost en FPM).
-- Solo SELECT y solo sobre puntdejoc: si alguien explota el portal,
-- no puede modificar datos ni leer otras bases.

SET @crear = CONCAT('CREATE USER ''puntdejoc''@''localhost'' IDENTIFIED BY ', QUOTE(@pw));
PREPARE sentencia FROM @crear;
EXECUTE sentencia;
DEALLOCATE PREPARE sentencia;

GRANT SELECT ON puntdejoc.* TO 'puntdejoc'@'localhost';

SHOW GRANTS FOR 'puntdejoc'@'localhost';
