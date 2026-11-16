-- Cuenta para la aplicación Java (Tomcat). Ejecutar con: sudo mariadb < sql/07-cuenta-java.sql
-- Sustituir CAMBIAR por una contraseña propia antes de ejecutar y no guardarla en Git.
CREATE USER IF NOT EXISTS 'tornejos'@'localhost' IDENTIFIED BY 'CAMBIAR';
GRANT SELECT ON puntdejoc.* TO 'tornejos'@'localhost';
GRANT UPDATE (resultat) ON puntdejoc.partides TO 'tornejos'@'localhost';
