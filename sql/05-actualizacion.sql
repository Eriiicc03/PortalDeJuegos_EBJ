-- Punto de Juego · Actualizar el resultado de la partida 3
-- Necesita la cuenta administradora (la cuenta web solo tiene SELECT):
--   sudo mariadb < sql/05-actualizacion.sql
--
-- Partida 3 = ronda 2, Aina (A) contra Cris (B). Registramos victoria de Cris.
-- Clasificación esperada en el navegador:
--   antes:   1 Aina 3 · 2 Cris 1 · 2 Dani 1 · 4 Biel 0
--   después: 1 Cris 4 · 2 Aina 3 · 3 Dani 1 · 4 Biel 0

USE puntdejoc;

-- Antes
SELECT p.id, r.numero AS ronda, ia.alias AS a, ib.alias AS b, p.resultat
FROM partides p
JOIN rondes r ON r.id = p.ronda_id
JOIN inscripcions ia ON ia.id = p.participant_a
JOIN inscripcions ib ON ib.id = p.participant_b
WHERE p.id = 3;

-- WHERE por clave primaria: solo puede tocar una fila
UPDATE partides SET resultat = 'B' WHERE id = 3;

SELECT ROW_COUNT() AS filas_modificadas;

-- Después
SELECT p.id, r.numero AS ronda, ia.alias AS a, ib.alias AS b, p.resultat
FROM partides p
JOIN rondes r ON r.id = p.ronda_id
JOIN inscripcions ia ON ia.id = p.participant_a
JOIN inscripcions ib ON ib.id = p.participant_b
WHERE p.id = 3;

-- Para devolverla a pendiente:
-- UPDATE partides SET resultat = NULL WHERE id = 3;
