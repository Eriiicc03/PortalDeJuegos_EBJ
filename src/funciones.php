<?php
declare(strict_types=1);
function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function db(): PDO {
    return new PDO('mysql:host=' . (getenv('DB_HOST') ?: '127.0.0.1') . ';port=' . (getenv('DB_PORT') ?: '3306') . ';dbname=puntdejoc;charset=utf8mb4', getenv('DB_USER') ?: 'puntdejoc', getenv('DB_PASSWORD') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
}
function classificacio(array $participants, array $partides): array {
    $rows = [];
    foreach ($participants as $p) $rows[(int)$p['id']] = ['alias'=>$p['alias'], 'jugades'=>0, 'victories'=>0, 'empats'=>0, 'derrotes'=>0, 'punts'=>0];
    foreach ($partides as $m) {
        if ($m['resultat'] === null) continue;
        $a=(int)$m['participant_a']; $b=(int)$m['participant_b'];
        foreach ([$a,$b] as $id) $rows[$id]['jugades']++;
        if ($m['resultat']==='E') {
            foreach ([$a,$b] as $id) { $rows[$id]['empats']++; $rows[$id]['punts']++; }
        } else {
            $win=$m['resultat']==='A'?$a:$b; $lose=$win===$a?$b:$a;
            $rows[$win]['victories']++; $rows[$win]['punts']+=3; $rows[$lose]['derrotes']++;
        }
    }
    $rows=array_values($rows);
    usort($rows, fn($a,$b)=>($b['punts']<=>$a['punts']) ?: strcmp($a['alias'],$b['alias']));
    $last=null; $position=0;
    foreach ($rows as $i=>&$row) { if ($last!==$row['punts']) $position=$i+1; $row['posicio']=$position; $last=$row['punts']; }
    unset($row);
    return $rows;
}
