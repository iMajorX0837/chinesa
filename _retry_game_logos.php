<?php
$base = 'https://77qw7.com/api/frontend/game-logo/style1/en/';
$dir = 'C:/xampp/htdocs/uploads/game-logos';
$tries = [
    'PP_BookofTutMegaways' => ['PP_BookofTutMegaways', 'PP_BookOfTutMegaways'],
    'slot-rubyplay_MadHitMarlinBonanza' => ['RUBYPLAY_MadHitMarlinBonanza', 'POPOK_MadHitMarlinBonanza'],
    'ONE_API_WG_FortuneTiger' => ['WG_FortuneTiger', 'PG_FortuneTiger'],
    'ONE_API_WG_FortuneOx' => ['WG_FortuneOx', 'PG_FortuneOx'],
    'ONE_API_WG_FortuneRabbit' => ['WG_FortuneRabbit', 'PG_FortuneRabbit'],
    'ONE_API_WG_BlackMythWukong' => ['WG_BlackMythWukong', 'PG_BlackMythWukong'],
    'ONE_API_WG_FishingMaster' => ['WG_FishingMaster', 'PG_FishingMaster'],
    'ONE_API_WG_AnimalKingdom' => ['WG_AnimalKingdom', 'PG_AnimalKingdom'],
    'ONE_API_WG_FestivaloftheSaints' => ['WG_FestivaloftheSaints', 'PG_FestivaloftheSaints'],
    'ONE_API_WG_SambaDance' => ['WG_SambaDance', 'PG_SambaDance'],
    'ONE_API_WG_TreasureMarmosets' => ['WG_TreasureMarmosets', 'PG_TreasureMarmosets'],
    'ONE_API_WG_DragonvsTiger' => ['WG_DragonvsTiger', 'PG_DragonvsTiger'],
    'ONE_API_WG_LuckyDog' => ['WG_LuckyDog', 'PG_LuckyDog'],
    'ONE_API_WG_MrTurtle' => ['WG_MrTurtle', 'PG_MrTurtle'],
    'ONE_API_WG_LeopardofGold' => ['WG_LeopardofGold', 'PG_LeopardofGold'],
    'ONE_API_WG_FortuneToucan' => ['WG_FortuneToucan', 'PG_FortuneToucan'],
    'ONE_API_WG_Maisfortunaeriqueza' => ['WG_Maisfortunaeriqueza', 'PG_Maisfortunaeriqueza'],
    'ONE_API_WG_MargemdaAgua' => ['WG_MargemdaAgua', 'PG_MargemdaAgua'],
    'ONE_API_WG_MahjongWays2' => ['WG_MahjongWays2', 'PG_MahjongWays2'],
    'ONE_API_WG_MahjongWays' => ['WG_MahjongWays', 'PG_MahjongWays'],
    'ONE_API_WG_DragonsTreasure2' => ['WG_DragonsTreasure2', 'PG_DragonsTreasure2'],
    'ONE_API_WG_DragonsTreasure' => ['WG_DragonsTreasure', 'PG_DragonsTreasure'],
    'ONE_API_WG_SuperMaquinaDeFrutas' => ['WG_SuperMaquinaDeFrutas', 'PG_SuperMaquinaDeFrutas'],
];
function imgok($b) {
    return $b && strlen($b) > 32 && (strncmp($b, "\x89PNG", 4) === 0 || strncmp($b, "\xFF\xD8\xFF", 3) === 0 || strncmp($b, 'RIFF', 4) === 0);
}
$ok = 0;
$fail = 0;
foreach ($tries as $saveAs => $alts) {
    $dest = $dir . '/' . preg_replace('/[^a-zA-Z0-9._-]/', '', $saveAs);
    if (is_file($dest) && filesize($dest) > 32) {
        echo "skip $saveAs\n";
        continue;
    }
    $got = false;
    foreach ($alts as $flag) {
        $src = $dir . '/' . preg_replace('/[^a-zA-Z0-9._-]/', '', $flag);
        if (is_file($src) && filesize($src) > 32) {
            copy($src, $dest);
            echo "copy $flag -> $saveAs\n";
            $got = true;
            $ok++;
            break;
        }
        $ch = curl_init($base . rawurlencode($flag) . '.jpg');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_USERAGENT => 'Mozilla/5.0']);
        $b = curl_exec($ch);
        $st = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($st < 400 && imgok($b)) {
            file_put_contents($dest, $b);
            echo "dl $flag -> $saveAs\n";
            $got = true;
            $ok++;
            break;
        }
    }
    if (!$got) {
        echo "fail $saveAs\n";
        $fail++;
    }
}
echo "retry ok=$ok fail=$fail\n";
