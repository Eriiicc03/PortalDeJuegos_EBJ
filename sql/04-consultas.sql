-- Punto de Juego · Consultas del encargo
-- Solo lectura: se pueden ejecutar con la cuenta web.
--   mariadb -u puntdejoc -p puntdejoc < sql/04-consultas.sql

USE puntdejoc;

-- 1. Participantes del torneo 2
SELECT i.id, i.alias, a.titol AS torneo
FROM inscripcions i
JOIN activitats a ON a.id = i.activitat_id
WHERE i.activitat_id = 2
ORDER BY i.id;

-- 2. Partidas pendientes (resultat IS NULL; "= NULL" nunca es cierto)
SELECT p.id AS partida, r.numero AS ronda,
       ia.alias AS participante_a, ib.alias AS participante_b
FROM partides p
JOIN rondes r ON r.id = p.ronda_id
JOIN inscripcions ia ON ia.id = p.participant_a
JOIN inscripcions ib ON ib.id = p.participant_b
WHERE p.resultat IS NULL
ORDER BY r.numero, p.id;

-- 3. Actividades con inscripción abierta
SELECT a.id, a.titol, a.tipus, j.nom AS juego, a.data, a.lloc, a.places
FROM activitats a
JOIN jocs j ON j.id = a.joc_id
WHERE a.estat = 'oberta'
ORDER BY a.data;
