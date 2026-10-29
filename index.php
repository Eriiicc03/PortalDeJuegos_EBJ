<?php
// Datos del portal: todavía no hay base de datos, todo vive en el código.
date_default_timezone_set('Europe/Madrid');

$activitats = [
  ['titol' => 'Tarde de juegos', 'tipus' => 'Encuentro', 'data' => '2026-11-12', 'lloc' => 'Local de la asociación', 'places' => 16, 'torneig' => false],
  ['titol' => 'Liga de demostración', 'tipus' => 'Torneo', 'data' => '2026-11-19', 'lloc' => 'Local de la asociación', 'places' => 4, 'torneig' => true],
];

$participants = ['Aina', 'Biel', 'Cris', 'Dani'];

// Resultado: 'A' gana A, 'B' gana B, 'X' empate
$ronda1 = [
  ['a' => 'Aina', 'b' => 'Biel', 'resultat' => 'A'],
  ['a' => 'Cris', 'b' => 'Dani', 'resultat' => 'X'],
];

const PUNTS = ['victoria' => 3, 'empat' => 1, 'derrota' => 0];
const MESOS = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

function h(string $text): string
{
  return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function data_llarga(string $data): string
{
  $d = new DateTimeImmutable($data);
  return $d->format('j') . ' ' . MESOS[(int) $d->format('n')] . ' ' . $d->format('Y');
}

function compte_enrere(string $data): string
{
  $avui = new DateTimeImmutable('today');
  $dies = (int) $avui->diff(new DateTimeImmutable($data))->format('%r%a');
  if ($dies > 1) return "Faltan $dies días";
  if ($dies === 1) return '¡Mañana!';
  if ($dies === 0) return '¡Hoy!';
  return 'Finalizada';
}

function text_resultat(string $resultat): string
{
  return match ($resultat) {
    'A' => 'Victoria A',
    'B' => 'Victoria B',
    default => 'Empate'
  };
}

// Clasificación calculada a partir de los resultados; empates comparten posición
function classificacio(array $participants, array $partides): array
{
  $punts = array_fill_keys($participants, 0);
  foreach ($partides as $p) {
    if ($p['resultat'] === 'X') {
      $punts[$p['a']] += PUNTS['empat'];
      $punts[$p['b']] += PUNTS['empat'];
    } else {
      $guanyador = $p['resultat'] === 'A' ? $p['a'] : $p['b'];
      $punts[$guanyador] += PUNTS['victoria'];
    }
  }
  arsort($punts);
  $taula = [];
  $posicio = 0;
  $anterior = null;
  $i = 0;
  foreach ($punts as $alias => $p) {
    $i++;
    if ($p !== $anterior) $posicio = $i;
    $anterior = $p;
    $taula[] = ['posicio' => $posicio, 'alias' => $alias, 'punts' => $p];
  }
  return $taula;
}

$classificacio = classificacio($participants, $ronda1);
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Punt de Joc</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lilita+One&family=Nunito:wght@400;700;800&display=swap" rel="stylesheet">
  <style>
    /* ===== Punt de Joc · estilo tablero ===== */
    :root {
      --rojo: #ef4444;
      --azul: #3b82f6;
      --amarillo: #facc15;
      --verde: #22c55e;
      --tinta: #1e1b4b;
      --papel: #fff8ec;
      --papel-2: #fdeccf;
      --texto: #2b2340;
      --suave: #6b6385;
      --radio: 18px;
      --sombra: 0 6px 0 rgba(0, 0, 0, .25), 0 18px 40px rgba(0, 0, 0, .25);
    }

    * {
      box-sizing: border-box
    }

    html {
      scroll-behavior: smooth
    }

    main>section[id] {
      scroll-margin-top: 6rem
    }

    main>section+section {
      margin-top: 4rem
    }

    body {
      margin: 0;
      font-family: "Nunito", system-ui, sans-serif;
      line-height: 1.6;
      color: var(--texto);
      background-color: var(--tinta);
      background-image:
        radial-gradient(circle at 15% 10%, rgba(239, 68, 68, .25), transparent 35%),
        radial-gradient(circle at 85% 30%, rgba(59, 130, 246, .25), transparent 35%),
        radial-gradient(circle at 20% 90%, rgba(34, 197, 94, .2), transparent 35%),
        linear-gradient(45deg, rgba(255, 255, 255, .03) 25%, transparent 25%, transparent 75%, rgba(255, 255, 255, .03) 75%),
        linear-gradient(45deg, rgba(255, 255, 255, .03) 25%, transparent 25%, transparent 75%, rgba(255, 255, 255, .03) 75%);
      background-size: auto, auto, auto, 60px 60px, 60px 60px;
      background-position: 0 0, 0 0, 0 0, 0 0, 30px 30px;
      background-attachment: fixed;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    h1,
    h2,
    .logo {
      font-family: "Lilita One", "Nunito", sans-serif;
      font-weight: 400;
      letter-spacing: .5px;
      line-height: 1.15;
    }

    /* ===== Cabecera ===== */
    header {
      position: sticky;
      top: 0;
      z-index: 10;
      background: rgba(30, 27, 75, .85);
      backdrop-filter: blur(10px);
      border-bottom: 4px solid transparent;
      border-image: linear-gradient(90deg, var(--rojo) 0 25%, var(--azul) 25% 50%, var(--amarillo) 50% 75%, var(--verde) 75%) 1;
    }

    .barra {
      max-width: 65rem;
      margin: 0 auto;
      padding: .8rem 1rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .logo {
      display: flex;
      align-items: center;
      gap: .6rem;
      color: #fff;
      font-size: 1.6rem;
      text-decoration: none;
    }

    .logo-dado {
      width: 38px;
      height: 38px;
      border-radius: 9px;
      background: var(--papel);
      box-shadow: 0 4px 0 var(--rojo);
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      padding: 6px;
      gap: 2px;
      transform: rotate(-10deg);
      transition: transform .4s;
    }

    .logo:hover .logo-dado {
      transform: rotate(350deg)
    }

    .logo-dado i {
      border-radius: 50%
    }

    .logo-dado i.on {
      background: var(--tinta)
    }

    nav {
      display: flex;
      gap: .5rem;
      flex-wrap: wrap
    }

    nav a {
      color: #fff;
      text-decoration: none;
      font-weight: 800;
      padding: .45rem 1rem;
      border-radius: 999px;
      border: 2px solid rgba(255, 255, 255, .2);
      transition: background .2s, transform .2s, border-color .2s;
    }

    nav a:hover {
      background: rgba(255, 255, 255, .12);
      transform: translateY(-2px)
    }

    nav a[aria-current="page"] {
      background: var(--amarillo);
      border-color: var(--amarillo);
      color: var(--tinta);
      box-shadow: 0 4px 0 #b48f00;
    }

    /* ===== Contenido ===== */
    main {
      flex: 1;
      width: 100%;
      max-width: 65rem;
      margin: 2.5rem auto;
      padding: 0 1rem;
    }

    .panel {
      background: var(--papel);
      border-radius: var(--radio);
      padding: 2rem;
      box-shadow: var(--sombra);
      margin-bottom: 2rem;
    }

    h1 {
      font-size: clamp(2.2rem, 6vw, 3.6rem);
      margin: 0 0 .5rem;
      color: var(--tinta);
    }

    h2 {
      font-size: 1.7rem;
      margin: 2rem 0 1rem;
      color: var(--tinta);
      display: flex;
      align-items: center;
      gap: .6rem;
    }

    h2::before {
      content: "";
      width: 16px;
      height: 16px;
      border-radius: 50%;
      background: var(--rojo);
      box-shadow: 0 3px 0 rgba(0, 0, 0, .25);
      flex-shrink: 0;
    }

    .panel h2:first-child {
      margin-top: 0
    }

    a {
      color: var(--azul);
      font-weight: 700
    }

    /* ===== Portada ===== */
    .hero {
      display: grid;
      grid-template-columns: 1.4fr 1fr;
      gap: 2rem;
      align-items: center;
      background: linear-gradient(135deg, var(--papel) 60%, var(--papel-2));
    }

    .hero h1 {
      font-size: clamp(2.8rem, 8vw, 4.8rem);
      background: linear-gradient(90deg, var(--rojo), var(--azul) 40%, var(--verde) 70%, #d4a800);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }

    .hero .lema {
      font-size: 1.2rem;
      color: var(--suave);
      margin: 0 0 1.5rem
    }

    .boton {
      display: inline-block;
      background: var(--rojo);
      color: #fff;
      text-decoration: none;
      font-weight: 800;
      padding: .8rem 1.5rem;
      border-radius: 14px;
      border: 0;
      font-family: inherit;
      font-size: 1rem;
      cursor: pointer;
      box-shadow: 0 5px 0 #a51d1d;
      transition: transform .1s, box-shadow .1s;
    }

    .boton:hover {
      transform: translateY(-2px);
      box-shadow: 0 7px 0 #a51d1d
    }

    .boton:active {
      transform: translateY(4px);
      box-shadow: 0 1px 0 #a51d1d
    }

    .boton.azul {
      background: var(--azul);
      box-shadow: 0 5px 0 #1d4fa5
    }

    .boton.azul:hover {
      box-shadow: 0 7px 0 #1d4fa5
    }

    .boton.azul:active {
      box-shadow: 0 1px 0 #1d4fa5
    }

    /* Mesa de dados */
    .mesa {
      background: radial-gradient(circle, #15803d, #0f5132);
      border: 10px solid #7c4a1e;
      border-radius: 24px;
      padding: 1.5rem;
      text-align: center;
      box-shadow: inset 0 0 30px rgba(0, 0, 0, .5), 0 6px 0 #4a2a0e;
    }

    .dados {
      display: flex;
      justify-content: center;
      gap: 1.2rem;
      margin-bottom: 1rem
    }

    .dado {
      width: 72px;
      height: 72px;
      background: #fff;
      border-radius: 14px;
      padding: 10px;
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 4px;
      box-shadow: 0 6px 0 #c9c2b3, 0 10px 15px rgba(0, 0, 0, .4);
    }

    .dado:nth-child(2) {
      background: var(--amarillo);
      box-shadow: 0 6px 0 #b48f00, 0 10px 15px rgba(0, 0, 0, .4)
    }

    .dado i {
      border-radius: 50%
    }

    .dado i.on {
      background: var(--tinta)
    }

    .dado.rodando {
      animation: rodar .6s ease-out
    }

    @keyframes rodar {
      0% {
        transform: translateY(-30px) rotate(0)
      }

      60% {
        transform: translateY(5px) rotate(400deg)
      }

      100% {
        transform: translateY(0) rotate(360deg)
      }
    }

    .tirada {
      color: #fff;
      font-weight: 800;
      margin: .5rem 0 1rem;
      min-height: 1.6em
    }

    aside {
      margin-top: 1.5rem;
      background: #fff;
      border: 3px dashed var(--amarillo);
      border-radius: 14px;
      padding: .8rem 1.2rem;
      color: var(--suave);
      font-size: .95rem;
    }

    aside::before {
      content: "⚠️ "
    }

    .colores {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1rem;
      margin-top: 1rem;
    }

    .ficha {
      border-radius: var(--radio);
      padding: 1.2rem;
      color: #fff;
      font-weight: 800;
      text-align: center;
      box-shadow: 0 5px 0 rgba(0, 0, 0, .25);
      transition: transform .2s;
    }

    .ficha:hover {
      transform: translateY(-6px) rotate(-2deg)
    }

    .ficha span {
      display: block;
      font-size: 2.2rem;
      line-height: 1.2
    }

    .ficha.rojo {
      background: var(--rojo)
    }

    .ficha.azul {
      background: var(--azul)
    }

    .ficha.amarillo {
      background: var(--amarillo);
      color: var(--tinta)
    }

    .ficha.verde {
      background: var(--verde)
    }

    /* ===== Actividades ===== */
    .titulo-pagina {
      color: #fff;
      text-align: center;
      margin-bottom: 2rem;
    }

    .titulo-pagina h1 {
      color: #fff;
      text-shadow: 0 5px 0 rgba(0, 0, 0, .35)
    }

    .titulo-pagina p {
      color: #c7c2ee;
      margin: 0
    }

    .tarjetas {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
      gap: 1.5rem;
    }

    article {
      background: var(--papel);
      border-radius: var(--radio);
      padding: 1.6rem;
      box-shadow: var(--sombra);
      border-top: 12px solid var(--azul);
      position: relative;
      transition: transform .25s;
    }

    article:hover {
      transform: translateY(-6px) rotate(-.5deg)
    }

    article.torneo {
      border-top-color: var(--rojo)
    }

    article h2 {
      margin-top: .6rem
    }

    article h2::before {
      background: var(--azul)
    }

    article.torneo h2::before {
      background: var(--rojo)
    }

    .etiqueta {
      display: inline-block;
      font-size: .8rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 1px;
      padding: .2rem .7rem;
      border-radius: 999px;
      background: var(--azul);
      color: #fff;
    }

    article.torneo .etiqueta {
      background: var(--rojo)
    }

    .datos {
      list-style: none;
      padding: 0;
      margin: 1rem 0 1.4rem;
      display: grid;
      gap: .5rem
    }

    .datos li {
      background: #fff;
      border-radius: 10px;
      padding: .45rem .8rem;
      display: flex;
      gap: .6rem;
      align-items: center;
    }

    .cuenta {
      position: absolute;
      top: 1.2rem;
      right: 1.2rem;
      background: var(--amarillo);
      color: var(--tinta);
      font-weight: 800;
      font-size: .85rem;
      padding: .25rem .7rem;
      border-radius: 999px;
      box-shadow: 0 3px 0 #b48f00;
    }

    .cuenta:empty {
      display: none
    }

    /* ===== Torneo ===== */
    .jugadores {
      list-style: none;
      padding: 0;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
      gap: 1rem;
    }

    .jugadores li {
      background: #fff;
      border-radius: 14px;
      padding: 1rem;
      text-align: center;
      font-weight: 800;
      font-size: 1.1rem;
      box-shadow: 0 4px 0 rgba(0, 0, 0, .12);
      transition: transform .2s;
    }

    .jugadores li:hover {
      transform: translateY(-4px)
    }

    .jugadores li::before {
      content: "";
      display: block;
      width: 44px;
      height: 52px;
      margin: 0 auto .5rem;
      background: var(--c, var(--rojo));
      /* silueta de meeple */
      clip-path: polygon(50% 0, 65% 6%, 68% 22%, 62% 32%, 100% 42%, 100% 55%, 72% 55%, 85% 100%, 55% 100%, 50% 82%, 45% 100%, 15% 100%, 28% 55%, 0 55%, 0 42%, 38% 32%, 32% 22%, 35% 6%);
    }

    .jugadores li:nth-child(1) {
      --c: var(--rojo)
    }

    .jugadores li:nth-child(2) {
      --c: var(--azul)
    }

    .jugadores li:nth-child(3) {
      --c: var(--amarillo)
    }

    .jugadores li:nth-child(4) {
      --c: var(--verde)
    }

    .tabla {
      overflow-x: auto
    }

    table {
      border-collapse: separate;
      border-spacing: 0;
      width: 100%;
      background: #fff;
      border-radius: 14px;
      overflow: hidden;
      box-shadow: 0 4px 0 rgba(0, 0, 0, .12);
    }

    caption {
      caption-side: top;
      text-align: left;
      font-weight: 800;
      color: var(--suave);
      padding-bottom: .5rem;
    }

    th,
    td {
      padding: .75rem 1rem;
      text-align: left
    }

    th {
      background: var(--tinta);
      color: #fff;
      font-weight: 800;
    }

    tbody tr:nth-child(even) td {
      background: #faf5ff
    }

    tbody tr:hover td {
      background: #fef3c7
    }

    .clasificacion td:first-child {
      font-weight: 800;
      width: 6rem
    }

    .clasificacion tbody tr.oro td {
      background: #fef9c3
    }

    .clasificacion .oro td:first-child::after {
      content: " 🥇"
    }

    .clasificacion .plata td:first-child::after {
      content: " 🥈"
    }

    .clasificacion td:last-child {
      font-family: "Lilita One", sans-serif;
      font-size: 1.2rem;
      color: var(--tinta)
    }

    .resultado {
      display: inline-block;
      padding: .15rem .7rem;
      border-radius: 999px;
      font-weight: 800;
      font-size: .9rem;
      background: var(--verde);
      color: #fff;
    }

    .resultado.empate {
      background: var(--amarillo);
      color: var(--tinta)
    }

    .reglas {
      display: flex;
      gap: .6rem;
      flex-wrap: wrap;
      margin: 1rem 0 0;
      padding: 0;
      list-style: none
    }

    .reglas li {
      background: #fff;
      border-radius: 10px;
      padding: .35rem .8rem;
      font-weight: 800;
      box-shadow: 0 3px 0 rgba(0, 0, 0, .1);
    }

    /* ===== Pie ===== */
    footer {
      text-align: center;
      color: #c7c2ee;
      padding: 1.5rem 1rem;
      font-weight: 700;
      background: rgba(0, 0, 0, .25);
      border-top: 4px solid transparent;
      border-image: linear-gradient(90deg, var(--verde) 0 25%, var(--amarillo) 25% 50%, var(--azul) 50% 75%, var(--rojo) 75%) 1;
    }

    /* ===== Formularios (base) ===== */
    label {
      display: block;
      margin: .7rem 0
    }

    input,
    select,
    button {
      font: inherit;
      padding: .3rem
    }

    pre {
      background: #f3f3f3;
      padding: 1rem;
      overflow: auto
    }

    :focus-visible {
      outline: 3px solid var(--amarillo);
      outline-offset: 3px
    }

    /* Animación de entrada */
    .aparece {
      opacity: 0;
      transform: translateY(25px);
      transition: opacity .6s, transform .6s
    }

    .aparece.visible {
      opacity: 1;
      transform: none
    }

    /* ===== Responsive ===== */
    @media (max-width:760px) {
      .hero {
        grid-template-columns: 1fr
      }

      .colores {
        grid-template-columns: repeat(2, 1fr)
      }

      .panel {
        padding: 1.4rem
      }

      .barra {
        justify-content: center
      }

      th,
      td {
        padding: .6rem
      }
    }

    @media (prefers-reduced-motion:reduce) {

      *,
      *::before,
      *::after {
        animation: none !important;
        transition: none !important;
        scroll-behavior: auto !important
      }

      .aparece {
        opacity: 1;
        transform: none
      }
    }

    @media print {

      header nav,
      .mesa {
        display: none
      }

      body {
        background: #fff;
        color: #000
      }

      .panel,
      article {
        box-shadow: none;
        border: 1px solid #bbb
      }
    }
  </style>
</head>

<body>
  <header>
    <div class="barra">
      <a class="logo" href="#inicio"><span class="logo-dado" data-valor="5" aria-hidden="true"></span>Punt de Joc</a>
      <nav>
        <a href="#inicio">Inicio</a>
        <a href="#actividades">Actividades</a>
        <a href="#torneo">Torneo</a>
      </nav>
    </div>
  </header>
  <main>
    <section id="inicio">
      <div class="panel hero">
        <div>
          <h1>Punt de Joc</h1>
          <p class="lema">Una asociación que organiza encuentros y torneos presenciales de juegos de mesa.</p>
          <a class="boton" href="#actividades">Consulta las actividades</a>
        </div>
        <div class="mesa">
          <div class="dados">
            <div class="dado" data-valor="6" role="img"></div>
            <div class="dado" data-valor="3" role="img"></div>
          </div>
          <p class="tirada" id="tirada" aria-live="polite">¿Te atreves a tirar?</p>
          <button class="boton azul" id="tirar" type="button">🎲 Tirar dados</button>
        </div>
      </div>
      <div class="panel">
        <h2>Nuestra comunidad</h2>
        <p>Este es un prototipo. Elegid el nombre, los juegos y la identidad de vuestra asociación.</p>
        <div class="colores">
          <div class="ficha rojo"><span>♟️</span>Estrategia</div>
          <div class="ficha azul"><span>🃏</span>Cartas</div>
          <div class="ficha amarillo"><span>🎲</span>Dados</div>
          <div class="ficha verde"><span>🧩</span>Cooperativos</div>
        </div>
        <aside>Los datos son ficticios. No hay inscripciones ni resultados persistentes en esta versión.</aside>
      </div>
    </section>

    <section id="actividades">
      <div class="titulo-pagina">
        <h1>Actividades</h1>
        <p>Coge tu ficha y apúntate a la próxima partida</p>
      </div>
      <div class="tarjetas">
        <?php foreach ($activitats as $activitat): ?>
          <article<?= $activitat['torneig'] ? ' class="torneo"' : '' ?>>
            <span class="etiqueta"><?= h($activitat['tipus']) ?></span>
            <span class="cuenta"><?= h(compte_enrere($activitat['data'])) ?></span>
            <h2><?= h($activitat['titol']) ?></h2>
            <ul class="datos">
              <li>📅 <?= h(data_llarga($activitat['data'])) ?></li>
              <li>📍 <?= h($activitat['lloc']) ?></li>
              <li>👥 <?= $activitat['places'] ?> plazas</li>
            </ul>
            <?php if ($activitat['torneig']): ?>
              <a class="boton" href="#torneo">Rondas y clasificación</a>
            <?php endif; ?>
            </article>
          <?php endforeach; ?>
      </div>
    </section>

    <section id="torneo">
      <div class="titulo-pagina">
        <h1>Liga de demostración</h1>
        <p>Formato didáctico: victoria <?= PUNTS['victoria'] ?> puntos, empate <?= PUNTS['empat'] ?>, derrota <?= PUNTS['derrota'] ?>. No es un reglamento oficial.</p>
      </div>
      <div class="panel">
        <h2>Participantes</h2>
        <ul class="jugadores">
          <?php foreach ($participants as $participant): ?>
            <li><?= h($participant) ?></li>
          <?php endforeach; ?>
        </ul>
        <ul class="reglas">
          <li>🏆 Victoria · <?= PUNTS['victoria'] ?></li>
          <li>🤝 Empate · <?= PUNTS['empat'] ?></li>
          <li>❌ Derrota · <?= PUNTS['derrota'] ?></li>
        </ul>
      </div>
      <div class="panel">
        <h2>Ronda 1</h2>
        <div class="tabla">
          <table>
            <caption>Resultados ficticios</caption>
            <thead>
              <tr>
                <th>Participante A</th>
                <th>Participante B</th>
                <th>Resultado</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($ronda1 as $partida): ?>
                <tr>
                  <td><?= h($partida['a']) ?></td>
                  <td><?= h($partida['b']) ?></td>
                  <td><span class="resultado<?= $partida['resultat'] === 'X' ? ' empate' : '' ?>"><?= text_resultat($partida['resultat']) ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="panel">
        <h2>Clasificación</h2>
        <div class="tabla">
          <table class="clasificacion">
            <caption>Después de la primera ronda</caption>
            <thead>
              <tr>
                <th>Posición</th>
                <th>Alias</th>
                <th>Puntos</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($classificacio as $fila): ?>
                <tr<?= match ($fila['posicio']) {
                      1 => ' class="oro"',
                      2 => ' class="plata"',
                      default => ''
                    } ?>>
                  <td><?= $fila['posicio'] ?></td>
                  <td><?= h($fila['alias']) ?></td>
                  <td><?= $fila['punts'] ?></td>
                  </tr>
                <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </main>
  <footer>DAW2 · AEA2 · RA1 · Respuesta generada en el servidor: <?= date('H:i:s') ?></footer>
  <script>
    // Posiciones de los puntos en una cuadrícula 3x3 para cada cara del dado
    const CARAS = {
      1: [5],
      2: [1, 9],
      3: [1, 5, 9],
      4: [1, 3, 7, 9],
      5: [1, 3, 5, 7, 9],
      6: [1, 3, 4, 6, 7, 9]
    };

    function pintarDado(dado, valor) {
      dado.innerHTML = "";
      for (let i = 1; i <= 9; i++) {
        const punto = document.createElement("i");
        if (CARAS[valor].includes(i)) punto.className = "on";
        dado.appendChild(punto);
      }
      dado.dataset.valor = valor;
      dado.setAttribute("aria-label", "Dado con valor " + valor);
    }

    // Dados: el del logo y los de la mesa de la portada
    document.querySelectorAll(".logo-dado, .dado").forEach(dado => {
      pintarDado(dado, Number(dado.dataset.valor) || 6);
    });

    const botonTirar = document.getElementById("tirar");
    if (botonTirar) {
      const dados = document.querySelectorAll(".dado");
      const resultado = document.getElementById("tirada");
      botonTirar.addEventListener("click", () => {
        let total = 0;
        dados.forEach(dado => {
          const valor = Math.floor(Math.random() * 6) + 1;
          total += valor;
          dado.classList.remove("rodando");
          void dado.offsetWidth; // reinicia la animación
          dado.classList.add("rodando");
          pintarDado(dado, valor);
        });
        resultado.textContent = total === 12 ? "¡Doble seis! Total: 12 🎉" : "Has sacado un " + total;
      });
    }

    if ("IntersectionObserver" in window) {
      // Marca en el menú la sección que se está viendo
      const enlaces = document.querySelectorAll("nav a");
      const espia = new IntersectionObserver(entradas => {
        entradas.forEach(entrada => {
          if (!entrada.isIntersecting) return;
          enlaces.forEach(enlace => {
            if (enlace.getAttribute("href") === "#" + entrada.target.id) enlace.setAttribute("aria-current", "page");
            else enlace.removeAttribute("aria-current");
          });
        });
      }, {
        rootMargin: "-45% 0px -50% 0px"
      });
      document.querySelectorAll("main > section[id]").forEach(seccion => espia.observe(seccion));

      // Animación de entrada al hacer scroll
      const observador = new IntersectionObserver(entradas => {
        entradas.forEach(entrada => {
          if (entrada.isIntersecting) {
            entrada.target.classList.add("visible");
            observador.unobserve(entrada.target);
          }
        });
      }, {
        threshold: 0.1
      });
      document.querySelectorAll(".panel, article, .titulo-pagina").forEach(el => {
        el.classList.add("aparece");
        observador.observe(el);
      });
    }
  </script>
</body>

</html>