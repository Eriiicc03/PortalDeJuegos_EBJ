<?php
require __DIR__ . '/../src/funciones.php';
// Esta función se habilita explícitamente solo en el laboratorio; no es autenticación.
if (getenv('ALLOW_LAB_WRITE') !== '1') { http_response_code(403); exit('Escritura desactivada. Solo disponible en el laboratorio local.'); }
session_set_cookie_params(['httponly'=>true,'samesite'=>'Strict']); session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$error='';
try {
    $pdo=db();
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        $token=$_POST['csrf']??''; $resultat=$_POST['resultat']??'';
        $id=filter_var($_POST['partida']??null,FILTER_VALIDATE_INT);
        if (!is_string($token) || !hash_equals($_SESSION['csrf'],$token) || !$id || !is_string($resultat) || !in_array($resultat,['A','B','E','pendent'],true)) {
            http_response_code(400); $error='Datos o sesión no válidos.';
        } else {
            $sql="UPDATE partides p JOIN rondes r ON r.id=p.ronda_id JOIN activitats a ON a.id=r.activitat_id SET p.resultat=? WHERE p.id=? AND a.estat='en_curs'";
            $stmt=$pdo->prepare($sql); $stmt->execute([$resultat==='pendent'?null:$resultat,$id]);
            header('Location: index.php',true,303); exit;
        }
    }
    $partides=$pdo->query("SELECT p.id,i.alias AS alias_a,j.alias AS alias_b,r.numero,p.resultat FROM partides p JOIN inscripcions i ON i.id=p.participant_a JOIN inscripcions j ON j.id=p.participant_b JOIN rondes r ON r.id=p.ronda_id JOIN activitats a ON a.id=r.activitat_id WHERE a.estat='en_curs' ORDER BY r.numero,p.id")->fetchAll();
} catch (PDOException $e) { error_log($e->getMessage()); http_response_code(503); exit('Base de datos no disponible; consulta los logs.'); }
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Resultados</title><link rel="stylesheet" href="style.css"></head><body><h1>Registrar o corregir un resultado</h1><p>Laboratorio: no publicar esta funcionalidad en Internet. También puedes devolver una partida a pendiente.</p><p><?= h($error) ?></p><form method="post"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><label>Partida<select name="partida" required><?php foreach ($partides as $p): ?><option value="<?= (int)$p['id'] ?>">R<?= (int)$p['numero'] ?>: <?= h($p['alias_a']) ?> / <?= h($p['alias_b']) ?> (<?= h($p['resultat']??'pendiente') ?>)</option><?php endforeach; ?></select></label><label>Resultado<select name="resultat"><option value="A">Victoria del primero</option><option value="B">Victoria del segundo</option><option value="E">Empate</option><option value="pendent">Pendiente</option></select></label><button>Guardar resultado</button></form><a href="index.php">Volver</a></body></html>
