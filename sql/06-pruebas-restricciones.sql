-- Punto de Juego · Pruebas de restricciones
-- Varias sentencias DEBEN fallar, así que se ejecuta con --force para que
-- el cliente siga después de cada error, y -v para ver cada sentencia:
--   sudo mariadb --force -v puntdejoc < sql/06-pruebas-restricciones.sql
-- Al final los datos quedan igual que antes (las pruebas que sí entran
-- se deshacen con ROLLBACK).

USE puntdejoc;

-- ===== 1. Restricciones que el esquema SÍ garantiza =====

-- 1a. FK inexistente: la inscripción 99 no existe.
-- Esperado: ERROR 1452 Cannot add or update a child row: a foreign key constraint fails
INSERT INTO partides (id, ronda_id, participant_a, participant_b, resultat)
VALUES (90, 1, 99, 2, NULL);

-- 1b. Partida contra uno mismo: participant_a = participant_b.
-- Esperado: ERROR 4025 CONSTRAINT `CONSTRAINT_1` failed for `puntdejoc`.`partides`
INSERT INTO partides (id, ronda_id, participant_a, participant_b, resultat)
VALUES (91, 1, 1, 1, NULL);

-- 1c. Ronda inexistente. Esperado: ERROR 1452
INSERT INTO partides (id, ronda_id, participant_a, participant_b, resultat)
VALUES (92, 99, 1, 2, NULL);

-- 1d. Resultado fuera del ENUM. Esperado: ERROR 1265 Data truncated (modo estricto)
UPDATE partides SET resultat = 'X' WHERE id = 4;

-- 1e. Alias repetido en la misma actividad. Esperado: ERROR 1062 Duplicate entry
INSERT INTO inscripcions (id, activitat_id, alias) VALUES (90, 2, 'Aina');

-- 1f. Plazas no positivas. Esperado: ERROR 4025 CONSTRAINT ... failed
INSERT INTO activitats (id, joc_id, titol, tipus, estat, data, lloc, places)
VALUES (90, 1, 'Prueba', 'trobada', 'borrador', '2026-12-01', 'Prueba', 0);

-- 1g. Sin borrado en cascada: no se puede borrar una inscripción con partidas.
-- Esperado: ERROR 1451 Cannot delete or update a parent row
DELETE FROM inscripcions WHERE id = 1;

-- ===== 2. Reglas que el esquema NO garantiza =====
-- Estas sentencias ENTRAN sin error aunque rompen las reglas del torneo.
-- Se hacen dentro de una transacción y se deshacen al final.

START TRANSACTION;

-- 2a. Participante inscrito en OTRA actividad: la inscripción 90 es del
-- torneo 3 (damas), pero se la mete en una ronda del torneo 2.
INSERT INTO inscripcions (id, activitat_id, alias) VALUES (90, 3, 'Eva');
INSERT INTO partides (id, ronda_id, participant_a, participant_b, resultat)
VALUES (93, 1, 1, 90, NULL);

-- 2b. Mismo participante dos veces en la misma ronda: Aina (1) ya juega
-- la partida 1 de la ronda 1 y se le añade otra en esa misma ronda.
INSERT INTO partides (id, ronda_id, participant_a, participant_b, resultat)
VALUES (94, 1, 1, 3, NULL);

-- 2c. Partida repetida con los participantes invertidos (B contra A).
INSERT INTO partides (id, ronda_id, participant_a, participant_b, resultat)
VALUES (95, 1, 2, 1, NULL);

-- Se ven las tres partidas inválidas que la base ha aceptado
SELECT p.id, p.ronda_id, ia.alias AS a, ia.activitat_id AS actividad_a,
       ib.alias AS b, ib.activitat_id AS actividad_b, r.activitat_id AS actividad_ronda
FROM partides p
JOIN rondes r ON r.id = p.ronda_id
JOIN inscripcions ia ON ia.id = p.participant_a
JOIN inscripcions ib ON ib.id = p.participant_b
WHERE p.id >= 93;

ROLLBACK;

-- Comprobación final: vuelven a ser 4 inscripciones y 6 partidas
SELECT (SELECT COUNT(*) FROM inscripcions) AS inscripciones,
       (SELECT COUNT(*) FROM partides) AS partidas;
