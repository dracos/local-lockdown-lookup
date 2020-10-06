<?php

if (php_sapi_name() !== 'cli') { exit; }

require 'utils.php';

$base = 'https://visual.parliament.uk/research/visualisations/coronavirus-restrictions-map/';
print "Fetching $base\n";
$html = file_get_contents($base);
preg_match('#<script src="([^h][^"]*)">#', $html, $m);
print "Fetching $m[1]\n";
$js = file_get_contents("$base$m[1]");
preg_match('#"([^"]*\.json)"#', $js, $m);
print "Fetching $m[1]\n";
$data = file_get_contents("$base$m[1]");
$data = preg_replace('#"geometry":{[^}]*},#', '', $data); # Yes, I KNOW
$data = json_decode($data, 1);

$areas = mapit_call('areas/COI,CTY,DIS,LBO,LGD,MTD,UTA');

foreach ($data['features'] as $feature) {
    $props = $feature['properties'];
    #if (!$props['url_local']) { continue; }
    $name = $props['Category'];
    $name = str_replace('St. ', 'St ', $name);
    $name = str_replace('County ', '', $name);
    $name = str_replace('upon Tyne', '', $name);
    $match = false;
    foreach ($areas as $id => $area) {
        if (preg_match("#^$name#", $area['name'])) {
            $match = $area;
        }
    }
    if ($match) {
        $parliament[$match['id']] = $props;
    } else {
        $parliament[$name] = $props;
    }
}

ksort($parliament);

$fp = fopen($dir . '/cache/parliamentN.php', 'w');
fwrite($fp, "<?php\n");
fwrite($fp, '$parliament = ');
fwrite($fp, var_export($parliament, true));
fwrite($fp, ";\n");
fclose($fp);

passthru('diff cache/parliament*');
